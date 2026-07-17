<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeliveryDocumentController extends Controller
{
    public function __invoke(Request $request, Order $order): Response
    {
        $user = $request->user('admin');

        abort_unless($user?->active && $user->can_access_panel, 403);

        $order->loadMissing(['deliveryDocument', 'paymentMethod']);
        $document = $order->deliveryDocument;
        abort_if(! $document, 404);

        return Pdf::loadView('pdf.delivery-document', [
            'document' => $document,
            'order' => $order,
            'logo' => $this->logoData($document->sender_snapshot['logo_path'] ?? null),
        ])
            ->setPaper('a4')
            ->stream($document->document_number.'.pdf');
    }

    private function logoData(?string $logoPath): ?array
    {
        $paths = array_filter([
            $logoPath ? storage_path('app/public/'.$logoPath) : null,
            $logoPath ? public_path('storage/'.$logoPath) : null,
            public_path('assets/images/frescogest-logo.png'),
        ]);

        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }

            $contents = (string) file_get_contents($path);
            $preparedLogo = $this->cropTransparentLogo($contents);
            $contents = $preparedLogo['contents'] ?? $contents;
            $size = getimagesizefromstring($contents);

            if ($size === false) {
                continue;
            }

            [$width, $height] = $this->fitLogo($size[0], $size[1]);
            $mime = $size['mime'] ?? (mime_content_type($path) ?: 'image/png');

            return [
                'data' => 'data:'.$mime.';base64,'.base64_encode($contents),
                'width' => $width,
                'height' => $height,
            ];
        }

        return null;
    }

    private function fitLogo(int $sourceWidth, int $sourceHeight): array
    {
        $scale = min(340 / $sourceWidth, 85 / $sourceHeight);

        return [
            max(1, (int) round($sourceWidth * $scale)),
            max(1, (int) round($sourceHeight * $scale)),
        ];
    }

    private function cropTransparentLogo(string $contents): ?array
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagecrop')) {
            return null;
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $bounds = [$width, $height, -1, -1];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7F;

                if ($alpha >= 120) {
                    continue;
                }

                $bounds[0] = min($bounds[0], $x);
                $bounds[1] = min($bounds[1], $y);
                $bounds[2] = max($bounds[2], $x);
                $bounds[3] = max($bounds[3], $y);
            }
        }

        if ($bounds[2] < $bounds[0] || $bounds[3] < $bounds[1]) {
            imagedestroy($image);

            return null;
        }

        $padding = max(4, (int) round(max($width, $height) * 0.01));
        $x = max(0, $bounds[0] - $padding);
        $y = max(0, $bounds[1] - $padding);
        $cropWidth = min($width - $x, ($bounds[2] - $bounds[0] + 1) + ($padding * 2));
        $cropHeight = min($height - $y, ($bounds[3] - $bounds[1] + 1) + ($padding * 2));
        $cropped = imagecrop($image, [
            'x' => $x,
            'y' => $y,
            'width' => $cropWidth,
            'height' => $cropHeight,
        ]);
        imagedestroy($image);

        if ($cropped === false) {
            return null;
        }

        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        ob_start();
        imagepng($cropped);
        $croppedContents = ob_get_clean();
        imagedestroy($cropped);

        if (! is_string($croppedContents)) {
            return null;
        }

        return ['contents' => $croppedContents];
    }
}
