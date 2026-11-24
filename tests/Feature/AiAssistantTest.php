<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Services\PihpsBaliPrices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'gemini-test']);
        Http::preventStrayRequests();
        $this->travelTo(now('Asia/Makassar')->setDate(2026, 11, 24)->startOfDay());
    }

    public function test_summary_uses_the_last_fourteen_calendar_days_and_displays_without_an_editor(): void
    {
        $this->seedPrices();
        $this->fakeSections(['change', 'range', 'average']);
        $this->get(route('prices.index'))->assertOk()->assertDontSee('data-market-chat');
        $this->actingAs(User::factory()->create())->get(route('prices.index'))->assertOk()
            ->assertSee('Ringkasan tren 14 hari terakhir')->assertSee('PIHPS Bank Indonesia')
            ->assertSee('Naik Rp8.000/kg')->assertDontSee('Catatan AI Saya')->assertDontSee('textarea');
        $response = $this->postJson(route('ai.assist'), ['commodity' => 'bawang-merah'])
            ->assertOk()->assertJsonPath('reference.count', 2)->assertJsonPath('reference.start', '2026-11-11')
            ->assertJsonPath('reference.end', '2026-11-24')->assertJsonPath('reference.average', 36000)
            ->assertJsonPath('reference.change', 8000)->assertJsonPath('reference.source_url', PihpsBaliPrices::SOURCE_URL)
            ->assertJsonPath('ai_generated', true)->assertJsonMissingPath('draft_id');
        $this->assertSame(
            'Dari pengamatan pertama ke terakhir, harga naik Rp8.000 per kg. Harga berkisar antara Rp32.000 dan Rp40.000 per kg. Harga rata-rata Rp36.000 per kg.',
            $response->json('text'),
        );
        $this->assertDatabaseCount('ai_notes', 0);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-goog-api-key', 'test-key')
            && str_ends_with($request->url(), '/gemini-test:generateContent'));
    }

    public function test_summary_and_its_statistics_follow_the_selected_thirty_or_ninety_day_period(): void
    {
        $this->seedPrices();
        foreach (['2026-08-26' => 80000, '2026-08-27' => 50000, '2026-10-25' => 46000, '2026-10-26' => 30000] as $date => $price) {
            $this->addPrice($date, $price);
        }

        $this->fakeSections(['change', 'range', 'average']);
        $this->actingAs(User::factory()->create());
        foreach ([
            30 => ['2026-10-26', 4, 50500, 10000, 'Naik Rp10.000', 'naik'],
            90 => ['2026-08-27', 6, 49667, -10000, 'Turun Rp10.000', 'turun'],
        ] as $period => [$start, $count, $average, $change, $label, $direction]) {
            $this->get(route('prices.index', ['periode' => $period]))->assertOk()
                ->assertSee("Ringkasan tren {$period} hari terakhir")
                ->assertSee($label);

            $response = $this->postJson(route('ai.assist'), ['commodity' => 'bawang-merah', 'period' => $period])
                ->assertOk()->assertJsonPath('reference.start', $start)->assertJsonPath('reference.end', '2026-11-24')
                ->assertJsonPath('reference.count', $count)->assertJsonPath('reference.average', $average)
                ->assertJsonPath('reference.change', $change);
            $this->assertStringContainsString("harga {$direction} Rp10.000 per kg", $response->json('text'));
        }

        Http::assertSentCount(2);
    }

    public function test_insufficient_data_keeps_available_statistics_and_skips_gemini(): void
    {
        $this->addPrice('2026-11-24', 32000);
        Http::fake();
        $this->actingAs(User::factory()->create())->get(route('prices.index'))->assertOk()
            ->assertSee('Data belum cukup untuk membuat ringkasan.')->assertSee('Rp32.000')->assertDontSee('Ringkas tren 14 hari');
        $this->postJson(route('ai.assist'), ['commodity' => 'bawang-merah'])
            ->assertUnprocessable()->assertJsonPath('reference.count', 1)->assertJsonPath('reference.average', 32000);
        Http::assertNothingSent();
    }

    public function test_invalid_provider_output_and_quota_return_safe_fallback(): void
    {
        $this->seedPrices();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->candidate(['change', 'range', 'Rp99.999']))
            ->push($this->candidate(['change']))
            ->push([], 429)
            ->push($this->candidate([]))
            ->push(['candidates' => [['finishReason' => 'SAFETY']]])]);
        $this->actingAs(User::factory()->create());
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('ai.assist'), ['commodity' => 'bawang-merah'])->assertServiceUnavailable()
                ->assertJsonPath('message', 'Ringkasan AI sedang tidak tersedia. Statistik harga tetap bisa dilihat di atas.');
        }
        Http::assertSentCount(5);
    }

    public function test_connection_failure_is_not_retried_and_missing_configuration_keeps_statistics(): void
    {
        $this->seedPrices();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::failedConnection('Timed out')]);
        $this->actingAs(User::factory()->create());
        $this->postJson(route('ai.assist'), ['commodity' => 'bawang-merah'])->assertServiceUnavailable();
        Http::assertSentCount(1);
        config(['services.gemini.key' => null]);
        $this->get(route('home'))->assertOk()->assertSee('Chatbot belum tersedia.')->assertDontSee('data-market-chat');
        $this->get(route('prices.index'))->assertOk()->assertSee('Rp36.000');
    }

    public function test_summary_requires_session_csrf_and_a_valid_commodity(): void
    {
        Http::fake();
        $this->postJson(route('ai.assist'), ['commodity' => 'bawang-merah'])->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('ai.assist'), ['commodity' => 'tomat'])->assertJsonValidationErrors(['commodity']);
        $this->postJson(route('ai.assist'), ['commodity' => 'bawang-merah', 'period' => 60])->assertJsonValidationErrors(['period']);
        $this->app['env'] = 'local';
        try {
            $this->postJson(route('ai.assist'), ['commodity' => 'bawang-merah'])->assertStatus(419);
            $this->withSession(['_token' => 'test-csrf-token'])
                ->postJson(route('ai.assist'), [], ['X-CSRF-TOKEN' => 'test-csrf-token'])
                ->assertJsonValidationErrors(['commodity']);
        } finally {
            $this->app['env'] = 'testing';
        }
        Http::assertNothingSent();
    }

    public function test_product_and_calculator_work_without_ai_and_notes_routes_are_gone(): void
    {
        Http::fake();
        $this->actingAs(User::factory()->create(['role' => Role::Petani]));
        $this->get(route('petani.products.create'))->assertOk()->assertSee('Deskripsi produk')->assertDontSee('Buat draf dengan AI');
        $this->post(route('calculator.calculate'), [
            'komoditas' => 'bawang-merah', 'biaya_panen' => '100000', 'jumlah_kg' => '4,00', 'tambahan_keuntungan' => '12,5',
        ])->assertOk()->assertSee('Rp28.125')->assertDontSee('Jelaskan harga usulan');
        $this->get('/catatan-ai')->assertNotFound();
        $this->postJson('/api/ai/notes')->assertNotFound();
        foreach (['description', 'price_advice'] as $task) {
            $this->postJson(route('ai.assist'), ['task' => $task, 'commodity' => 'bawang-merah'])
                ->assertJsonValidationErrors(['task']);
        }
        Http::assertNothingSent();
    }

    public function test_rate_limit_is_per_account(): void
    {
        $this->seedPrices();
        $this->fakeSections(['change', 'range']);
        $this->actingAs(User::factory()->create());
        $payload = ['task' => 'trend_summary', 'commodity' => 'bawang-merah'];
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('ai.assist'), $payload)->assertOk();
        }
        $this->postJson(route('ai.assist'), $payload)->assertTooManyRequests();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('ai.assist'), $payload)->assertOk();
        Http::assertSentCount(11);
    }

    private function seedPrices(): void
    {
        foreach (['2026-11-10' => 100000, '2026-11-11' => 32000, '2026-11-24' => 40000, '2026-11-25' => 99000] as $date => $price) {
            $this->addPrice($date, $price);
        }
    }

    /** @param  list<string>  $sections */
    private function fakeSections(array $sections): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->candidate($sections))]);
    }

    /** @param  list<string>  $sections */
    private function candidate(array $sections): array
    {
        return ['candidates' => [[
            'finishReason' => 'STOP',
            'content' => ['parts' => [['text' => json_encode(['sections' => $sections], JSON_THROW_ON_ERROR)]]],
        ]]];
    }

    private function addPrice(string $date, int $price): void
    {
        DB::table('market_prices')->insert([
            'commodity' => 'bawang-merah', 'province' => PihpsBaliPrices::PROVINCE,
            'market_type' => PihpsBaliPrices::MARKET_TYPE, 'observed_on' => $date,
            'price_per_kg' => $price, 'source_url' => PihpsBaliPrices::SOURCE_URL, 'imported_at' => now(),
        ]);
    }
}
