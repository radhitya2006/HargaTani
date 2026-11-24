<?php

namespace App\Http\Controllers;

use App\Services\GeminiAssistant;
use App\Support\MarketTrend;
use App\Support\PriceCommodities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AiAssistantController extends Controller
{
    public function __invoke(Request $request, GeminiAssistant $assistant): JsonResponse
    {
        $request->validate([
            'task' => ['sometimes', 'required', Rule::in(['trend_summary'])],
            'commodity' => ['required', Rule::in(array_keys(PriceCommodities::ALL))],
            'period' => ['sometimes', 'required', 'integer', Rule::in(PriceCommodities::PERIODS)],
        ]);

        $commodity = $request->string('commodity')->toString();
        $days = $request->integer('period', PriceCommodities::PERIODS[0]);
        $stats = MarketTrend::forCommodity($commodity, $days);
        if ($stats['change'] === null || $stats['minimum'] === null || $stats['maximum'] === null || $stats['average'] === null) {
            return response()->json(['message' => 'Data belum cukup untuk membuat ringkasan.', 'reference' => $stats], 422);
        }

        $summary = $assistant->summarize(MarketTrend::summarySections(
            $stats['change'], $stats['minimum'], $stats['maximum'], $stats['average'],
        ));
        if ($summary === null) {
            return response()->json(['message' => 'Ringkasan AI sedang tidak tersedia. Statistik harga tetap bisa dilihat di atas.'], 503);
        }

        return response()->json([
            'task' => 'trend_summary',
            'text' => $summary,
            'ai_generated' => true,
            'reference' => $stats,
        ])->header('Cache-Control', 'private, no-store');
    }
}
