<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PihpsBaliPrices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class MarketChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'gemini-test']);
        Http::preventStrayRequests();
        $this->travelTo(now('Asia/Makassar')->setDate(2026, 11, 24)->startOfDay());
    }

    public function test_home_hosts_the_panel_and_guests_are_returned_to_it_after_login(): void
    {
        $open = route('ai.market-chat.open');
        $homePanel = route('home').'#tanya-harga-pasar';
        $this->get(route('home'))->assertOk()->assertSee('Tanya Harga Pasar')->assertSee('Masuk untuk bertanya')
            ->assertSee($open)->assertDontSee('data-market-chat');
        $this->get($open)->assertRedirect(route('login'))->assertSessionHas('url.intended', $open);
        $user = User::factory()->create();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect($open);
        $this->get($open)->assertRedirect($homePanel);

        $this->get(route('home'))->assertOk()->assertSee('<details id="tanya-harga-pasar"', escape: false)->assertSee('data-market-chat')
            ->assertDontSee('name="commodity"', escape: false)->assertDontSee('name="period"', escape: false)
            ->assertSee('Komoditas termahal')->assertSee('Komoditas termurah');
        $this->get(route('prices.index'))->assertOk()->assertSee($homePanel)->assertDontSee('data-market-chat');
    }

    public function test_question_without_optional_scope_defaults_to_all_commodities_and_fourteen_days(): void
    {
        $this->addPrice('2026-11-10', 99000, 'cabai-rawit-merah');
        $this->addPrice('2026-11-11', 64000, 'cabai-merah-besar');
        $this->addPrice('2026-11-24', 40000);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->candidate(['intent' => 'maximum']))]);
        $this->actingAs(User::factory()->create())->postJson(route('ai.market-chat'), ['question' => 'Harga tertingginya?'])->assertOk()
            ->assertJsonPath('results.0.period', 14)
            ->assertJsonPath('results.0.items.0.commodity', 'cabai-merah-besar')
            ->assertJsonPath('results.0.items.0.text', 'Harga tertinggi Rp64.000 per kg.');
        Http::assertSent(function (Request $request): bool {
            $context = json_decode($request['contents'][0]['parts'][0]['text'], true, flags: JSON_THROW_ON_ERROR);

            return $context['selected_commodity'] === 'all' && $context['selected_period_days'] === 14;
        });
    }

    public function test_follow_up_answer_uses_supplied_commodity_and_period(): void
    {
        $this->addPrice('2026-08-26', 100000);
        $this->addPrice('2026-08-27', 58000);
        $this->addPrice('2026-10-29', 47000);
        $this->addPrice('2026-11-24', 36000);
        $this->addPrice('2026-11-25', 90000);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->candidate(['intent' => 'maximum']))]);
        $this->actingAs(User::factory()->create());
        foreach ([
            14 => ['2026-11-11', 1, 'Rp36.000'],
            30 => ['2026-10-26', 2, 'Rp47.000'],
            90 => ['2026-08-27', 3, 'Rp58.000'],
        ] as $period => [$start, $count, $maximum]) {
            $this->postJson(route('ai.market-chat'), $this->payload('tertingginya?', $period))->assertOk()
                ->assertJsonPath('text', "Harga tertinggi {$maximum} per kg.")
                ->assertJsonPath('reference.start', $start)->assertJsonPath('reference.end', '2026-11-24')
                ->assertJsonPath('reference.count', $count)->assertJsonPath('reference.source_url', PihpsBaliPrices::SOURCE_URL);
        }
        Http::assertSentCount(3);
        Http::assertSent(function (Request $request): bool {
            $context = json_decode($request['contents'][0]['parts'][0]['text'], true, flags: JSON_THROW_ON_ERROR);

            return $context['question'] === 'tertingginya?' && $context['selected_commodity'] === 'bawang-merah'
                && $context['selected_period_days'] === 90;
        });
    }

    public function test_compares_three_commodities_across_periods_in_one_price_query(): void
    {
        $this->addPrice('2026-11-24', 40000);
        $this->addPrice('2026-11-24', 0, 'cabai-merah-besar');
        $this->addPrice('2026-10-26', 50000, 'cabai-merah-besar');
        $this->addPrice('2026-08-27', 70000, 'cabai-rawit-merah');
        $this->addPrice('2026-10-25', 70000, 'cabai-rawit-merah');
        $this->addPrice('2026-08-26', 100000, 'cabai-rawit-merah');
        $this->addPrice('2026-11-25', 100000, 'cabai-rawit-merah');
        $this->addPrice('2026-11-23', 120000, 'cabai-rawit-merah', 'Jawa Timur');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->candidate([
            'intent' => 'maximum', 'commodity' => 'all', 'periods' => [90, 14, 30],
        ]))]);
        $this->actingAs(User::factory()->create());

        DB::enableQueryLog();
        try {
            $response = $this->postJson(route('ai.market-chat'), $this->payload('Komoditas apa yang paling mahal untuk 14, 30, dan 90 hari?'))
                ->assertOk()->assertJsonPath('reference', null);
            $priceQueries = array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], 'from `market_prices`'));
            $this->assertCount(1, $priceQueries);
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $response->assertJsonPath('results.0.period', 14)->assertJsonPath('results.0.items.0.commodity', 'bawang-merah')
            ->assertJsonPath('results.0.items.0.text', 'Harga tertinggi Rp40.000 per kg.')
            ->assertJsonPath('results.0.missing', ['Cabai Merah Besar', 'Cabai Rawit Merah'])
            ->assertJsonPath('results.1.period', 30)->assertJsonPath('results.1.items.0.commodity', 'cabai-merah-besar')
            ->assertJsonPath('results.1.items.0.text', 'Harga tertinggi Rp50.000 per kg.')
            ->assertJsonPath('results.2.period', 90)->assertJsonPath('results.2.start', '2026-08-27')
            ->assertJsonPath('results.2.items.0.commodity', 'cabai-rawit-merah')
            ->assertJsonPath('results.2.items.0.text', 'Harga tertinggi Rp70.000 per kg.')
            ->assertJsonPath('results.2.items.0.observed_on', '2026-10-25');
        Http::assertSentCount(1);
    }

    public function test_ties_and_missing_data_are_visible_for_market_wide_quick_questions(): void
    {
        Http::fake();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('ai.market-chat'), $this->payload('Komoditas termahal'))->assertOk()
            ->assertJsonCount(3, 'results.0.missing')->assertJsonCount(0, 'results.0.items');
        $this->addPrice('2026-11-24', 30000);
        $this->addPrice('2026-11-24', 30000, 'cabai-merah-besar');
        $this->addPrice('2026-11-24', 70000, 'cabai-rawit-merah');
        $this->postJson(route('ai.market-chat'), $this->payload('Komoditas termurah'))->assertOk()
            ->assertJsonCount(2, 'results.0.items')->assertJsonCount(0, 'results.0.missing')
            ->assertJsonPath('results.0.items.0.commodity', 'bawang-merah')
            ->assertJsonPath('results.0.items.1.commodity', 'cabai-merah-besar');
        Http::assertNothingSent();
    }

    public function test_named_commodity_and_periods_override_the_home_choices(): void
    {
        $this->addPrice('2026-11-24', 99000);
        $this->addPrice('2026-11-24', 42000, 'cabai-rawit-merah');
        $this->addPrice('2026-10-30', 48000, 'cabai-rawit-merah');
        $this->addPrice('2026-09-30', 57000, 'cabai-rawit-merah');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->candidate([
            'intent' => 'maximum', 'commodity' => 'cabai-rawit-merah', 'periods' => [14, 30, 90],
        ]))]);

        $this->actingAs(User::factory()->create())->postJson(
            route('ai.market-chat'), ['question' => 'Harga tertinggi cabai rawit merah dalam 14, 30, dan 90 hari?'],
        )->assertOk()->assertJsonCount(3, 'results')
            ->assertJsonPath('results.0.items.0.commodity', 'cabai-rawit-merah')
            ->assertJsonPath('results.0.items.0.text', 'Harga tertinggi Rp42.000 per kg.')
            ->assertJsonPath('results.1.items.0.text', 'Harga tertinggi Rp48.000 per kg.')
            ->assertJsonPath('results.2.items.0.text', 'Harga tertinggi Rp57.000 per kg.');
    }

    public function test_exact_date_uses_the_requested_commodity_and_accepts_dates_outside_the_active_period(): void
    {
        $this->addPrice('2026-11-20', 64400, 'cabai-rawit-merah');
        $this->addPrice('2026-09-20', 45000, 'cabai-rawit-merah');
        $this->addPrice('2026-11-20', 80000);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->candidate(['intent' => 'on_date', 'commodity' => 'cabai-rawit-merah', 'date' => '2026-11-20']))
            ->push($this->candidate(['intent' => 'on_date', 'commodity' => 'cabai-rawit-merah', 'date' => '2026-09-20']))]);
        $this->actingAs(User::factory()->create());
        $this->postJson(route('ai.market-chat'), $this->payload('harga cabai rawit di tanggal 20 november'))->assertOk()
            ->assertJsonPath('text', 'Harga PIHPS pada 20 November 2026 adalah Cabai Rawit Merah Rp64.400 per kg.')
            ->assertJsonPath('reference.start', '2026-11-20')->assertJsonPath('reference.end', '2026-11-20')
            ->assertJsonPath('reference.source_url', PihpsBaliPrices::SOURCE_URL);
        $this->postJson(route('ai.market-chat'), $this->payload('harga cabai rawit tanggal 20 September 2026'))->assertOk()
            ->assertJsonPath('text', 'Harga PIHPS pada 20 September 2026 adalah Cabai Rawit Merah Rp45.000 per kg.');
        Http::assertSent(function (Request $request): bool {
            $context = json_decode($request['contents'][0]['parts'][0]['text'], true, flags: JSON_THROW_ON_ERROR);

            return $context['today_wita'] === '2026-11-24';
        });
    }

    public function test_exact_date_reports_missing_future_and_invalid_dates(): void
    {
        $this->addPrice('2026-11-20', 64400, 'cabai-rawit-merah');
        $this->addPrice('2026-11-25', 70000, 'cabai-rawit-merah');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->candidate(['intent' => 'on_date', 'commodity' => 'cabai-rawit-merah', 'date' => '2026-11-21']))
            ->push($this->candidate(['intent' => 'on_date', 'commodity' => 'cabai-rawit-merah', 'date' => '2026-11-25']))
            ->push($this->candidate(['intent' => 'on_date', 'commodity' => 'cabai-rawit-merah', 'date' => '2026-02-31']))]);
        $this->actingAs(User::factory()->create());
        $this->postJson(route('ai.market-chat'), $this->payload('harga cabai rawit 21 November'))->assertOk()
            ->assertJsonPath('text', 'Belum ada pengamatan PIHPS pada 21 November 2026 untuk Cabai Rawit Merah.')
            ->assertJsonPath('reference.count', 0);
        $this->postJson(route('ai.market-chat'), $this->payload('harga cabai rawit 25 November'))->assertOk()
            ->assertJsonPath('text', 'Tanggal 25 November 2026 belum tiba. Pilih hari ini atau tanggal sebelumnya.');
        $this->postJson(route('ai.market-chat'), $this->payload('harga cabai rawit 31 Februari'))->assertOk()
            ->assertJsonPath('text', 'Sebutkan satu tanggal yang valid, misalnya 20 November 2026.');
    }

    public function test_today_ranking_ignores_the_active_period_and_reports_missing_todays_data(): void
    {
        $this->addPrice('2026-11-20', 92000, 'cabai-rawit-merah');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->candidate([
            'intent' => 'maximum', 'commodity' => 'all', 'periods' => [14],
        ]))]);
        $this->actingAs(User::factory()->create());
        $question = ['question' => 'harga komoditas termahal hari ini'];
        $this->postJson(route('ai.market-chat'), $question)->assertOk()
            ->assertJsonPath('reference.start', '2026-11-24')->assertJsonPath('reference.end', '2026-11-24')
            ->assertJsonPath('reference.count', 0)
            ->assertJsonPath('text', 'Belum ada pengamatan PIHPS pada 24 November 2026 untuk Bawang Merah Ukuran Sedang, Cabai Merah Besar, Cabai Rawit Merah.');

        $this->addPrice('2026-11-24', 48000, 'cabai-merah-besar');
        $this->addPrice('2026-11-24', 43000, 'cabai-rawit-merah');
        $this->postJson(route('ai.market-chat'), $question)->assertOk()
            ->assertJsonPath('text', 'Harga tertinggi PIHPS pada 24 November 2026 adalah Cabai Merah Besar Rp48.000 per kg. Belum ada pengamatan untuk Bawang Merah Ukuran Sedang.')
            ->assertJsonPath('reference.count', 2)->assertJsonMissingPath('results');
    }

    public function test_yesterday_ranking_keeps_ties_and_today_can_be_the_end_of_an_explicit_range(): void
    {
        $this->addPrice('2026-11-23', 28000);
        $this->addPrice('2026-11-23', 28000, 'cabai-merah-besar');
        $this->addPrice('2026-11-23', 70000, 'cabai-rawit-merah');
        $this->addPrice('2026-11-24', 20000);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->candidate(['intent' => 'minimum', 'commodity' => 'all', 'date' => '2026-11-24']))
            ->push($this->candidate(['intent' => 'maximum', 'commodity' => 'all', 'periods' => [14]]))]);
        $this->actingAs(User::factory()->create());
        $this->postJson(route('ai.market-chat'), $this->payload('Komoditas termurah kemarin?'))->assertOk()
            ->assertJsonPath('text', 'Harga terendah PIHPS pada 23 November 2026 adalah Bawang Merah Ukuran Sedang Rp28.000 per kg, Cabai Merah Besar Rp28.000 per kg.')
            ->assertJsonPath('reference.start', '2026-11-23');
        $this->postJson(route('ai.market-chat'), $this->payload('Komoditas termahal dalam 14 hari sampai hari ini?'))->assertOk()
            ->assertJsonPath('results.0.start', '2026-11-11')->assertJsonPath('results.0.end', '2026-11-24')
            ->assertJsonPath('results.0.items.0.commodity', 'cabai-rawit-merah');
    }

    public function test_quick_questions_use_prices_without_calling_gemini(): void
    {
        Http::fake();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('ai.market-chat'), $this->payload('Harga terakhir'))->assertOk()
            ->assertJsonPath('reference.count', 0)
            ->assertJsonPath('text', 'Belum ada pengamatan PIHPS untuk komoditas dan periode ini.');

        $this->addPrice('2026-11-20', 32000);
        $this->postJson(route('ai.market-chat'), $this->payload('Harga terakhir'))->assertOk()
            ->assertJsonPath('text', 'Harga terakhir Rp32.000 per kg pada 20 November 2026.');
        $this->postJson(route('ai.market-chat'), $this->payload('Naik atau turun?'))->assertOk()
            ->assertJsonPath('text', 'Perlu minimal dua pengamatan untuk melihat tren. Saat ini baru ada satu pengamatan dalam periode ini.');

        $this->addPrice('2026-11-24', 40000);
        $this->postJson(route('ai.market-chat'), $this->payload('Rata-rata berapa?'))->assertOk()
            ->assertJsonPath('text', 'Harga rata-rata Rp36.000 per kg.');
        $this->postJson(route('ai.market-chat'), $this->payload('Ringkas tren 14 hari'))->assertOk()
            ->assertJsonPath('text', 'Dari pengamatan pertama ke terakhir, harga naik Rp8.000 per kg. Harga berkisar antara Rp32.000 dan Rp40.000 per kg. Harga rata-rata Rp36.000 per kg.');
        Http::assertNothingSent();
    }

    public function test_chat_guides_other_filters_and_unsupported_questions(): void
    {
        $this->actingAs(User::factory()->create());
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->candidate(['intent' => 'other_filter']))
            ->push($this->candidate(['intent' => 'unsupported']))
            ->push($this->candidate(['intent' => 'unclear']))]);
        $this->postJson(route('ai.market-chat'), $this->payload('Bandingkan harga cabai rawit dan bawang merah saja.'))
            ->assertOk()->assertJsonPath('reference', null)
            ->assertJsonPath('text', 'Sebutkan satu komoditas atau minta perbandingan semua komoditas.');
        foreach (['Bagaimana membatalkan pesanan?', 'Berapa?'] as $question) {
            $this->postJson(route('ai.market-chat'), $this->payload($question))->assertOk()
                ->assertJsonPath('reference', null)
                ->assertJsonPath('text', 'Saya bisa menjawab tentang harga PIHPS Bali. Coba “Komoditas apa yang paling mahal dalam 14 hari?” atau “Harga terakhir cabai rawit berapa?”.');
        }
    }

    public function test_chat_handles_invalid_provider_output_and_times_out_without_retry(): void
    {
        $this->actingAs(User::factory()->create());
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->candidate(['intent' => 'Rp999 dilaporkan']))
            ->push($this->candidate(['intent' => 'maximum', 'text' => 'Rp999']))
            ->push($this->candidate(['intent' => 'maximum', 'commodity' => 'tomat', 'periods' => [14]]))
            ->push($this->candidate(['intent' => 'maximum', 'periods' => [60]]))
            ->pushFailedConnection('Timed out')]);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('ai.market-chat'), $this->payload('tertingginya?'))
                ->assertServiceUnavailable()
                ->assertJsonPath('message', 'Chatbot belum bisa menjawab. Coba lagi atau gunakan pertanyaan cepat.');
        }
        Http::assertSentCount(5);
    }

    public function test_chat_requires_session_csrf_and_supported_filter_values(): void
    {
        Http::fake();
        $this->postJson(route('ai.market-chat'), $this->payload('Harga terakhir'))->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson(route('ai.market-chat'), [
            'question' => str_repeat('a', 301), 'commodity' => 'tomat', 'period' => 60,
        ])->assertJsonValidationErrors(['question', 'commodity', 'period']);
        $this->app['env'] = 'local';
        try {
            $this->postJson(route('ai.market-chat'), $this->payload('Harga terakhir'))->assertStatus(419);
            $this->withSession(['_token' => 'test-csrf-token'])->postJson(
                route('ai.market-chat'), $this->payload('Harga terakhir'), ['X-CSRF-TOKEN' => 'test-csrf-token'],
            )->assertOk();
        } finally {
            $this->app['env'] = 'testing';
        }
        Http::assertNothingSent();
    }

    public function test_rate_limit_is_shared_with_the_summary_endpoint(): void
    {
        Http::fake();
        $this->actingAs(User::factory()->create());
        for ($i = 0; $i < 9; $i++) {
            $this->postJson(route('ai.market-chat'), $this->payload('Harga terakhir'))->assertOk();
        }
        $this->postJson(route('ai.assist'), ['commodity' => 'bawang-merah'])->assertUnprocessable();
        $this->postJson(route('ai.market-chat'), $this->payload('Harga terakhir'))->assertTooManyRequests();
        $this->postJson(route('ai.assist'), ['commodity' => 'bawang-merah'])->assertTooManyRequests();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('ai.market-chat'), $this->payload('Harga terakhir'))->assertOk();
        Http::assertNothingSent();
    }

    /** @return array{question: string, commodity: string, period: int} */
    private function payload(string $question, int $period = 14): array
    {
        return ['question' => $question, 'commodity' => 'bawang-merah', 'period' => $period];
    }

    /** @param array<string, string|list<int>> $modelOutput */
    private function candidate(array $modelOutput): array
    {
        return ['candidates' => [[
            'finishReason' => 'STOP',
            'content' => ['parts' => [['text' => json_encode($modelOutput, JSON_THROW_ON_ERROR)]]],
        ]]];
    }

    private function addPrice(string $date, int $price, string $commodity = 'bawang-merah', string $province = PihpsBaliPrices::PROVINCE): void
    {
        DB::table('market_prices')->insert([
            'commodity' => $commodity, 'province' => $province,
            'market_type' => PihpsBaliPrices::MARKET_TYPE, 'observed_on' => $date,
            'price_per_kg' => $price, 'source_url' => PihpsBaliPrices::SOURCE_URL, 'imported_at' => now(),
        ]);
    }
}
