<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Promotions\PromotionCodeService;
use Illuminate\Http\Request;

class PromotionCodeController extends Controller
{
    public function sticker(PromotionCodeService $promotions)
    {
        $promotion = $promotions->stickerPromotion();

        return response()->json([
            'data' => $promotion ? [
                'code' => $promotion->code,
                'name' => $promotion->name,
                'discount_percentage' => $promotion->discount_percentage,
                'audience' => $promotion->audience->value,
                'audience_label' => $promotion->audience->label(),
                'rule' => $promotion->rule->value,
                'rule_label' => $promotion->rule->label(),
            ] : null,
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function validate(Request $request, PromotionCodeService $promotions)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);
        $promotion = $promotions->validate(
            $request->user()->customer,
            $validated['code'],
        );

        return response()->json([
            'data' => [
                'code' => $promotion->code,
                'name' => $promotion->name,
                'discount_percentage' => $promotion->discount_percentage,
                'audience' => $promotion->audience->value,
                'audience_label' => $promotion->audience->label(),
                'rule' => $promotion->rule->value,
                'rule_label' => $promotion->rule->label(),
            ],
            'message' => 'Codice sconto applicato correttamente.',
        ]);
    }
}
