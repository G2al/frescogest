<?php

namespace App\Services\Documents;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class DeliveryDocumentPdfService
{
    public function stream(Collection $documents, string $filename)
    {
        return Pdf::loadView('pdf.delivery-documents', [
            'documents' => $documents,
            'logo' => $this->logoData(),
        ])->setPaper('a4')->stream($filename);
    }

    private function logoData(): ?array
    {
        $path = public_path('assets/images/new-logo-primary.png');

        if (! is_file($path)) {
            return null;
        }

        $contents = (string) file_get_contents($path);
        $contents = $this->cropTransparentLogo($contents) ?? $contents;
        $size = getimagesizefromstring($contents);

        if ($size === false) {
            return null;
        }

        $scale = min(340 / $size[0], 85 / $size[1]);

        return [
            'data' => 'data:'.($size['mime'] ?? 'image/png').';base64,'.base64_encode($contents),
            'width' => max(1, (int) round($size[0] * $scale)),
            'height' => max(1, (int) round($size[1] * $scale)),
        ];
    }

    private function cropTransparentLogo(string $contents): ?string
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
                if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) >= 120) {
                    continue;
                }

                $bounds = [min($bounds[0], $x), min($bounds[1], $y), max($bounds[2], $x), max($bounds[3], $y)];
            }
        }

        if ($bounds[2] < $bounds[0] || $bounds[3] < $bounds[1]) {
            imagedestroy($image);

            return null;
        }

        $padding = max(4, (int) round(max($width, $height) * .01));
        $x = max(0, $bounds[0] - $padding);
        $y = max(0, $bounds[1] - $padding);
        $cropped = imagecrop($image, [
            'x' => $x,
            'y' => $y,
            'width' => min($width - $x, $bounds[2] - $bounds[0] + 1 + ($padding * 2)),
            'height' => min($height - $y, $bounds[3] - $bounds[1] + 1 + ($padding * 2)),
        ]);
        imagedestroy($image);

        if ($cropped === false) {
            return null;
        }

        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        ob_start();
        imagepng($cropped);
        $result = ob_get_clean();
        imagedestroy($cropped);

        return is_string($result) ? $result : null;
    }
}
