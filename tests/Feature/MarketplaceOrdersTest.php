<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

final class MarketplaceOrdersTest extends TestCase
{
    use RefreshDatabase;

    private User $farmer;

    private User $buyer;

    private Product $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->farmer = User::factory()->create([
            'role' => Role::Petani,
            'name' => 'Petani Tabanan',
            'transaction_contact' => 'farmer-contact@example.test',
        ]);
        $this->buyer = User::factory()->create([
            'name' => 'Pembeli Bali',
            'transaction_contact' => 'buyer-contact@example.test',
        ]);
        $this->listing = Product::forceCreate([
            'farmer_id' => $this->farmer->id,
            'name' => 'Bawang Pasar Tabanan',
            'commodity' => 'bawang-merah',
            'description' => 'Bawang hasil panen hari ini.',
            'photo_path' => 'products/'.$this->farmer->id.'/bawang.jpg',
            'price_per_kg' => 30001,
            'stock_kg' => '5.25',
            'harvested_on' => today()->toDateString(),
            'district' => 'Tabanan',
            'pickup_location' => 'Pasar Tabanan',
            'status' => ProductStatus::Active,
        ]);
    }

    public function test_buyer_and_farmer_can_complete_an_order_with_a_price_snapshot_and_private_contacts(): void
    {
        $create = route('orders.create', $this->listing);
        $this->get(route('catalog.show', $this->listing))->assertOk()
            ->assertSee('Pesan')->assertSee($create)->assertDontSee($this->farmer->transaction_contact);
        $this->get($create)->assertRedirect(route('login'));
        $this->assertSame($create, session('url.intended'));

        $this->actingAs($this->buyer)->get($create)->assertOk()->assertSee('Total pesanan')->assertSee('Kirim pesanan');
        $order = $this->place(['quantity_kg' => '2,50', 'notes' => 'Ambil pagi hari']);
        $show = route('orders.show', $order);
        $this->assertSame(75003, $order->total_price);
        $this->assertSame('5.25', $this->listing->refresh()->stock_kg);
        $this->get($show)->assertOk()->assertSee('Menunggu')->assertSee('Rp75.003')->assertSee('Ambil pagi hari')
            ->assertDontSee($this->farmer->transaction_contact);
        $this->get(route('orders.index'))->assertOk()->assertSee('Pesanan Saya')->assertSee($show);

        $this->actingAs($this->farmer)->get(route('orders.index'))->assertOk()->assertSee('Pesanan Masuk')->assertSee($show);
        $this->get($show)->assertOk()->assertSee('Terima pesanan')->assertDontSee($this->buyer->transaction_contact);
        $this->listing->update(['price_per_kg' => 35000]);
        $this->listing->forceFill(['status' => ProductStatus::Archived])->save();

        $accept = route('orders.update', [$order, OrderStatus::Accepted]);
        $this->patch($accept)->assertRedirect($show);
        $this->patch($accept)->assertRedirect($show);
        $this->assertSame('2.75', $this->listing->refresh()->stock_kg);
        $this->get($show)->assertOk()->assertSee($this->buyer->transaction_contact)
            ->assertSee('Rp30.001')->assertDontSee('Rp35.000');

        $this->actingAs($this->buyer)->get($show)->assertOk()->assertSee($this->farmer->transaction_contact)
            ->assertSee('Tandai selesai');
        $this->assertStringContainsString('no-store', $this->get($show)->headers->get('Cache-Control'));
        $this->from($show)->patch(route('orders.update', [$order, OrderStatus::Cancelled]))->assertSessionHasErrors('status');
        $complete = route('orders.update', [$order, OrderStatus::Completed]);
        $this->patch($complete)->assertRedirect($show);
        $this->patch($complete)->assertRedirect($show);
        $this->get($show)->assertOk()->assertSee('Selesai')->assertDontSee($this->farmer->transaction_contact);
        $this->assertSame('2.75', $this->listing->refresh()->stock_kg);
        $this->assertSame(
            [
                ['menunggu', $this->buyer->id],
                ['diterima', $this->farmer->id],
                ['selesai', $this->buyer->id],
            ],
            $order->statusChanges()->orderBy('id')->get()->map(fn ($change) => [$change->status->value, $change->actor_id])->all(),
        );
    }

    public function test_quantity_and_current_price_are_validated_and_submissions_are_idempotent(): void
    {
        $this->actingAs($this->buyer);
        $create = route('orders.create', $this->listing);
        $store = route('orders.store', $this->listing);

        foreach (['0', '5.26', '1.001', '1e0', ['1']] as $quantity) {
            $this->from($create)->post($store, $this->payload(['quantity_kg' => $quantity]))
                ->assertRedirect($create)->assertSessionHasErrors('quantity_kg');
        }
        $this->from($create)->post($store, $this->payload(['price_per_kg' => 1]))
            ->assertRedirect($create)->assertSessionHasErrors('order');
        $this->assertDatabaseCount('orders', 0);

        $payload = $this->payload(['total_price' => 1, 'farmer_id' => $this->buyer->id, 'status' => 'diterima']);
        $response = $this->post($store, $payload);
        $order = Order::query()->sole();
        $show = route('orders.show', $order);
        $response->assertRedirect($show);
        $this->listing->forceFill(['status' => ProductStatus::Archived])->save();
        $this->postJson($store, $payload)->assertOk()->assertExactJson(['redirect' => route('orders.show', $order, absolute: false)]);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_status_changes', 1);
        $this->assertSame($this->farmer->id, $order->farmer_id);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(30001, $order->total_price);
    }

    public function test_order_quantity_carries_through_login_and_the_listing_can_submit_an_order(): void
    {
        $details = route('catalog.show', $this->listing);
        $create = route('orders.create', [$this->listing, 'quantity_kg' => '2,50']);

        $this->get($details)->assertOk()
            ->assertSee('Rp30.001')->assertSee('Masuk sebagai pembeli')->assertDontSee('submission_token');
        $this->get($create)->assertRedirect(route('login'));
        $this->assertSame($create, session('url.intended'));

        $this->actingAs($this->buyer)->get($create)->assertOk()
            ->assertSee('value="2,5"', escape: false)->assertSee('Rp75.003');
        $this->get($details)->assertOk()->assertSee('submission_token')->assertSee('Total perkiraan');
        $this->from($details)->post(route('orders.store', $this->listing), $this->payload(['quantity_kg' => '2,50']))
            ->assertRedirect(route('orders.show', Order::query()->sole()));
    }

    public function test_order_actions_respect_roles_and_keep_stock_correct_on_rejection_and_cancellation(): void
    {
        $pending = $this->place();
        $cancelled = $this->place();
        $accepted = $this->place(['quantity_kg' => '5.25']);
        $this->patch(route('orders.update', [$pending, OrderStatus::Accepted]))->assertForbidden();
        $this->patch(route('orders.update', [$cancelled, OrderStatus::Cancelled]))->assertRedirect();
        $this->assertSame('5.25', $this->listing->refresh()->stock_kg);

        $other = User::factory()->create();
        $this->actingAs($other)->get(route('orders.show', $pending))->assertForbidden();
        $this->patch(route('orders.update', [$pending, OrderStatus::Cancelled]))->assertForbidden();
        $this->get(route('orders.index'))->assertOk()->assertSee('Anda belum membuat pesanan.');
        $this->actingAs(User::factory()->create(['role' => Role::Petani]))
            ->patch(route('orders.update', [$pending, OrderStatus::Accepted]))->assertForbidden();

        $this->actingAs($this->farmer)->post(route('orders.store', $this->listing), $this->payload())->assertForbidden();
        $show = route('orders.show', $accepted);
        $this->patch(route('orders.update', [$accepted, OrderStatus::Accepted]))->assertRedirect($show);
        $this->assertSame('0.00', $this->listing->refresh()->stock_kg);
        $this->get(route('catalog.show', $this->listing))->assertOk()->assertSee('Stok habis');
        $pendingPage = route('orders.show', $pending);
        $this->withCookie(config('session.cookie'), session()->getId())
            ->from($pendingPage)->patch(route('orders.update', [$pending, OrderStatus::Accepted]))
            ->assertRedirect($pendingPage)->assertSessionHasErrors('quantity_kg');
        $this->assertStringContainsString('Stok tersedia tidak mencukupi.', $this->get($pendingPage)->getContent());
        $this->assertSame(OrderStatus::Pending, $pending->refresh()->status);
        $this->patch(route('orders.update', [$pending, OrderStatus::Rejected]))->assertRedirect();
        $this->assertSame(OrderStatus::Rejected, $pending->refresh()->status);
        $this->assertSame('0.00', $this->listing->refresh()->stock_kg);

        $cancel = route('orders.update', [$accepted, OrderStatus::Cancelled]);
        $this->from($show)->patch($cancel)->assertSessionHasErrors('reason');
        $this->patch($cancel, ['reason' => 'Pengambilan perlu dijadwalkan ulang.'])->assertRedirect($show);
        $this->patch($cancel, ['reason' => 'Pengambilan perlu dijadwalkan ulang.'])->assertRedirect($show);
        $this->assertSame('5.25', $this->listing->refresh()->stock_kg);
        $this->assertSame(3, $accepted->statusChanges()->count());
        $this->get($show)->assertOk()->assertSee('Pengambilan perlu dijadwalkan ulang.')
            ->assertDontSee($this->buyer->transaction_contact);
    }

    public function test_archived_or_unavailable_listings_reject_new_orders(): void
    {
        $this->actingAs($this->buyer);
        $store = route('orders.store', $this->listing);
        $this->listing->forceFill(['status' => ProductStatus::Archived])->save();
        $this->get(route('orders.create', $this->listing))->assertNotFound();
        $this->post($store, $this->payload())->assertSessionHasErrors('order');

        $this->listing->forceFill(['status' => ProductStatus::Active])->save();
        $this->farmer->forceFill(['is_active' => false])->save();
        $this->post($store, $this->payload())->assertSessionHasErrors('order');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_buyer_can_set_a_transaction_contact_and_account_deletion_preserves_order_history(): void
    {
        $order = $this->place();
        Livewire::test('pages::settings.profile')
            ->set('transaction_contact', 'updated@example.test')
            ->call('updateProfileInformation')->assertHasNoErrors();
        $this->assertSame('updated@example.test', $this->buyer->refresh()->transaction_contact);

        Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser')->assertHasErrors('password');
        $this->assertAuthenticatedAs($this->buyer);
        $this->assertModelExists($order);

        $this->patch(route('orders.update', [$order, OrderStatus::Cancelled]))->assertRedirect();
        $this->actingAs($this->farmer);
        Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser')->assertHasNoErrors();
        $this->assertModelMissing($this->farmer);
        $this->assertSame(ProductStatus::Archived, $this->listing->refresh()->status);
        $this->assertNull($this->listing->farmer_id);
        $this->assertNull($order->refresh()->farmer_id);
        $this->actingAs($this->buyer)->get(route('orders.show', $order))->assertOk()->assertSee('Akun dihapus')
            ->assertDontSee('Petani Tabanan');
    }

    public function test_an_old_product_edit_cannot_accidentally_readd_allocated_stock(): void
    {
        $order = $this->place(['quantity_kg' => '3.00']);
        $this->actingAs($this->farmer)->patch(route('orders.update', [$order, OrderStatus::Accepted]))->assertRedirect();
        $edit = route('petani.products.edit', $this->listing);
        $update = route('petani.products.update', $this->listing);
        $fields = [
            'name' => 'Bawang Tabanan Pagi',
            'commodity' => 'bawang-merah',
            'description' => 'Bawang hasil panen hari ini.',
            'price_per_kg' => 30001,
            'harvested_on' => today()->toDateString(),
            'district' => 'Tabanan',
            'pickup_location' => 'Pasar Tabanan',
        ];

        $this->patch($update, $fields + ['stock_before' => '5.25', 'stock_kg' => '5.25'])->assertRedirect($edit)->assertSessionHasNoErrors();
        $this->assertSame('2.25', $this->listing->refresh()->stock_kg);
        $this->assertSame('Bawang Tabanan Pagi', $this->listing->name);
        $this->from($edit)->patch($update, $fields + ['stock_before' => '5.25', 'stock_kg' => '4.00'])
            ->assertSessionHasErrors('stock_conflict');
        $this->get($edit)->assertOk()->assertSee('name="stock_before" value="2.25"', escape: false)
            ->assertSee('Stok berubah sejak formulir dibuka.');
        $this->from($edit)->patch($update, $fields + ['stock_before' => '2.25', 'stock_kg' => '99999999.99'])
            ->assertSessionHasErrors('stock_kg');
        $this->assertSame('2.25', $this->listing->refresh()->stock_kg);
    }

    /** @param array<string, mixed> $overrides */
    private function place(array $overrides = []): Order
    {
        $payload = $this->payload($overrides);
        $this->actingAs($this->buyer)->post(route('orders.store', $this->listing), $payload)->assertSessionHasNoErrors();

        return Order::query()->where('submission_token', $payload['submission_token'])->sole();
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'quantity_kg' => '1',
            'price_per_kg' => 30001,
            'submission_token' => (string) Str::uuid(),
        ];
    }
}
