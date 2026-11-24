<?php

namespace App\Http\Controllers;

use App\Enums\MarketChatIntent;
use App\Models\MarketPrice;
use App\Services\GeminiAssistant;
use App\Services\PihpsBaliPrices;
use App\Support\MarketTrend;
use App\Support\PriceCommodities;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/** @phpstan-import-type Stats from MarketTrend */
final class MarketChatController extends Controller
{
    public function __invoke(Request $request, GeminiAssistant $assistant): JsonResponse
    {
        $request->validate([
            'question' => ['required', 'string', 'max:300'],
            'commodity' => ['sometimes', 'required', Rule::in([PriceCommodities::ALL_OPTION, ...array_keys(PriceCommodities::ALL)])],
            'period' => ['sometimes', 'required', 'integer', Rule::in(PriceCommodities::PERIODS)],
        ], [
            'question.required' => 'Tulis pertanyaan tentang harga pasar.',
            'question.max' => 'Pertanyaan maksimal 300 karakter.',
        ]);

        $question = $request->string('question')->toString();
        $commodity = $request->string('commodity', PriceCommodities::ALL_OPTION)->toString();
        $period = $request->integer('period', PriceCommodities::PERIODS[0]);
        $quick = MarketChatIntent::fromQuickQuestion($question, $period);
        $query = $quick === null
            ? $assistant->classifyMarketQuestion($question, $commodity, $period)
            : [
                'intent' => $quick,
                'commodity' => in_array($quick, [MarketChatIntent::Maximum, MarketChatIntent::Minimum], true)
                    ? PriceCommodities::ALL_OPTION : $commodity,
                'periods' => [$period],
                'date' => null,
            ];

        if ($query === null) {
            return response()->json(['message' => 'Chatbot belum bisa menjawab. Coba lagi atau gunakan pertanyaan cepat.'], 503);
        }

        if ($query['date'] !== null || $query['intent'] === MarketChatIntent::OnDate) {
            return $this->answerOnDate($query['intent'], $query['commodity'], $query['date']);
        }

        return match ($query['intent']) {
            MarketChatIntent::OtherFilter => $this->reply('Sebutkan satu komoditas atau minta perbandingan semua komoditas.'),
            MarketChatIntent::Unclear, MarketChatIntent::Unsupported => $this->reply(
                'Saya bisa menjawab tentang harga PIHPS Bali. Coba “Komoditas apa yang paling mahal dalam 14 hari?” atau “Harga terakhir cabai rawit berapa?”.',
            ),
            default => $this->answer($query['intent'], $query['commodity'], $query['periods']),
        };
    }

    private function answerOnDate(MarketChatIntent $intent, string $commodity, ?string $date): JsonResponse
    {
        if ($date === null) {
            return $this->reply('Sebutkan satu tanggal yang valid, misalnya 20 November 2026.');
        }

        $formatted = CarbonImmutable::parse($date, 'Asia/Makassar')->settings(['locale' => 'id'])->translatedFormat('j F Y');
        if ($date > CarbonImmutable::today('Asia/Makassar')->toDateString()) {
            return $this->reply("Tanggal {$formatted} belum tiba. Pilih hari ini atau tanggal sebelumnya.");
        }

        $commodities = $commodity === PriceCommodities::ALL_OPTION ? array_keys(PriceCommodities::ALL) : [$commodity];
        $prices = MarketPrice::query()->pihpsBali($commodities)->where('observed_on', $date)
            ->where('price_per_kg', '>', 0)->pluck('price_per_kg', 'commodity');
        $best = match ($intent) {
            MarketChatIntent::Maximum => $prices->max(),
            MarketChatIntent::Minimum => $prices->min(),
            default => null,
        };
        $available = [];
        $missing = [];
        foreach ($commodities as $slug) {
            $name = PriceCommodities::ALL[$slug]['name'];
            $price = $prices->get($slug);
            if ($price === null) {
                $missing[] = $name;
            } elseif ($best === null || (int) $price === (int) $best) {
                $available[] = $name.' '.MarketTrend::rupiah((int) $price).' per kg';
            }
        }

        $subject = match ($intent) {
            MarketChatIntent::Maximum => 'Harga tertinggi',
            MarketChatIntent::Minimum => 'Harga terendah',
            default => 'Harga',
        };
        $text = $available === []
            ? "Belum ada pengamatan PIHPS pada {$formatted} untuk ".implode(', ', $missing).'.'
            : "{$subject} PIHPS pada {$formatted} adalah ".implode(', ', $available).'.';
        if ($available !== [] && $missing !== []) {
            $text .= ' Belum ada pengamatan untuk '.implode(', ', $missing).'.';
        }

        return $this->reply($text, [
            'commodity' => $commodity,
            'commodity_name' => PriceCommodities::ALL[$commodity]['name'] ?? 'Semua komoditas',
            'start' => $date,
            'end' => $date,
            'count' => $prices->count(),
            'source_url' => PihpsBaliPrices::SOURCE_URL,
        ]);
    }

    /** @param non-empty-list<int> $periods */
    private function answer(MarketChatIntent $intent, string $commodity, array $periods): JsonResponse
    {
        $isMarketWide = $commodity === PriceCommodities::ALL_OPTION;
        if (! $isMarketWide && count($periods) === 1) {
            $stats = MarketTrend::forCommodity($commodity, $periods[0]);
            $reference = Arr::only($stats, ['commodity', 'start', 'end', 'count', 'source_url']) + [
                'commodity_name' => PriceCommodities::ALL[$commodity]['name'],
                'observed_on' => $this->observationDate($intent, $stats),
            ];

            return $this->reply($this->answerText($intent, $stats), $reference);
        }

        $commodities = $isMarketWide ? array_keys(PriceCommodities::ALL) : [$commodity];
        $extreme = match ($intent) {
            MarketChatIntent::Maximum => 'maximum',
            MarketChatIntent::Minimum => 'minimum',
            default => null,
        };
        $results = [];
        foreach (MarketTrend::forPeriods($commodities, $periods) as $days => $series) {
            $available = array_filter($series, fn (array $stats): bool => $stats['count'] > 0);
            $missing = array_diff_key($series, $available);
            $ranked = $isMarketWide && $extreme !== null ? array_column($available, $extreme) : [];
            if ($ranked !== []) {
                $best = $extreme === 'maximum' ? max($ranked) : min($ranked);
                $available = array_filter($available, fn (array $stats): bool => $stats[$extreme] === $best);
            }

            $items = [];
            foreach ($available as $slug => $stats) {
                $items[] = [
                    'commodity' => $slug,
                    'commodity_name' => PriceCommodities::ALL[$slug]['name'],
                    'text' => $this->answerText($intent, $stats),
                    'observed_on' => $this->observationDate($intent, $stats),
                ];
            }
            $window = $series[$commodities[0]];
            $results[] = [
                'period' => $days,
                'start' => $window['start'],
                'end' => $window['end'],
                'items' => $items,
                'missing' => array_map(fn (string $slug): string => PriceCommodities::ALL[$slug]['name'], array_keys($missing)),
            ];
        }

        $heading = match (true) {
            $isMarketWide && $extreme === 'maximum' => 'Komoditas termahal berdasarkan harga tertinggi yang tercatat.',
            $isMarketWide && $extreme === 'minimum' => 'Komoditas termurah berdasarkan harga terendah yang tercatat.',
            default => 'Berikut hasil dari pengamatan PIHPS.',
        };

        return response()->json(['text' => $heading, 'reference' => null, 'results' => $results])
            ->header('Cache-Control', 'private, no-store');
    }

    /** @param Stats $stats */
    private function observationDate(MarketChatIntent $intent, array $stats): ?string
    {
        return match ($intent) {
            MarketChatIntent::Minimum => $stats['minimum_date'],
            MarketChatIntent::Maximum => $stats['maximum_date'],
            default => null,
        };
    }

    /** @param Stats $stats */
    private function answerText(MarketChatIntent $intent, array $stats): string
    {
        $latest = $stats['latest_price'];
        $latestDate = $stats['last_observation'];
        $minimum = $stats['minimum'];
        $maximum = $stats['maximum'];
        $average = $stats['average'];
        if ($latest === null || $latestDate === null || $minimum === null || $maximum === null || $average === null) {
            return 'Belum ada pengamatan PIHPS untuk komoditas dan periode ini.';
        }

        if ($intent === MarketChatIntent::Summary || $intent === MarketChatIntent::Change) {
            if ($stats['change'] === null) {
                return 'Perlu minimal dua pengamatan untuk melihat tren. Saat ini baru ada satu pengamatan dalam periode ini.';
            }
            $sections = MarketTrend::summarySections($stats['change'], $minimum, $maximum, $average);

            return $intent === MarketChatIntent::Change ? $sections['change'] : implode(' ', $sections);
        }

        return match ($intent) {
            MarketChatIntent::Latest => 'Harga terakhir '.MarketTrend::rupiah($latest).' per kg pada '
                .CarbonImmutable::parse($latestDate)->settings(['locale' => 'id'])->translatedFormat('j F Y').'.',
            MarketChatIntent::Average => 'Harga rata-rata '.MarketTrend::rupiah($average).' per kg.',
            MarketChatIntent::Minimum => 'Harga terendah '.MarketTrend::rupiah($minimum).' per kg.',
            MarketChatIntent::Maximum => 'Harga tertinggi '.MarketTrend::rupiah($maximum).' per kg.',
            MarketChatIntent::Range => 'Harga berkisar antara '.MarketTrend::rupiah($minimum).' dan '.MarketTrend::rupiah($maximum).' per kg.',
            default => throw new \LogicException('Unexpected market chat intent.'),
        };

    }

    /** @param array<string, int|string|null>|null $reference */
    private function reply(string $text, ?array $reference = null): JsonResponse
    {
        return response()->json(['text' => $text, 'reference' => $reference])
            ->header('Cache-Control', 'private, no-store');
    }
}
