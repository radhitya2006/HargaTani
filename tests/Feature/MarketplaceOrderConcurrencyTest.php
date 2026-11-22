<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\MarketplaceOrders;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class MarketplaceOrderConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_two_concurrent_acceptances_cannot_allocate_the_same_stock(): void
    {
        $farmer = User::factory()->create(['role' => Role::Petani]);
        $product = Product::forceCreate([
            'farmer_id' => $farmer->id,
            'name' => 'Panen terakhir',
            'commodity' => 'bawang-merah',
            'description' => 'Stok untuk satu pesanan.',
            'photo_path' => 'products/test.jpg',
            'price_per_kg' => 25000,
            'stock_kg' => '1.00',
            'harvested_on' => today()->toDateString(),
            'district' => 'Tabanan',
            'pickup_location' => 'Pasar Tabanan',
            'status' => ProductStatus::Active,
        ]);
        $service = app(MarketplaceOrders::class);
        $orders = [
            $service->place(User::factory()->create(), $product, '1', 25000, (string) Str::uuid(), null),
            $service->place(User::factory()->create(), $product, '1', 25000, (string) Str::uuid(), null),
        ];
        $db = DB::connection();
        $this->assertSame('hargatani_testing', $db->getDatabaseName());
        $workers = array_map(fn (Order $order) => $this->acceptWorker($order), $orders);

        try {
            // Keep both independent MySQL sessions at the stock row before releasing them together.
            $db->transaction(function () use ($product, $workers): void {
                Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                foreach ($workers as $worker) {
                    $worker->start();
                }
                foreach ($workers as $worker) {
                    $this->assertTrue(
                        $worker->waitUntil(fn ($type, $output) => $type === Process::OUT && str_contains($output, 'locking stock')),
                        $worker->getErrorOutput(),
                    );
                }
            });

            $results = [];
            foreach ($workers as $worker) {
                $this->assertSame(0, $worker->wait(), $worker->getErrorOutput().$worker->getOutput());
                $output = $worker->getOutput();
                $results[] = trim(substr($output, strpos($output, "\n") + 1));
            }
            $this->assertEqualsCanonicalizing(['accepted', 'insufficient stock'], $results);
            $this->assertSame('0.00', $product->refresh()->stock_kg);
            $this->assertSame(1, Order::query()->where('status', OrderStatus::Accepted)->count());
            $this->assertSame(1, Order::query()->where('status', OrderStatus::Pending)->count());
            $this->assertDatabaseCount('order_status_changes', 3);
        } finally {
            foreach ($workers as $worker) {
                $worker->stop(0);
            }
        }
    }

    private function acceptWorker(Order $order): Process
    {
        $code = <<<'PHP'
            require 'vendor/autoload.php';
            $app = require 'bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $db = Illuminate\Support\Facades\DB::connection();
            if ($db->getDatabaseName() !== 'hargatani_testing') {
                throw new RuntimeException('Expected the isolated testing database.');
            }
            $db->beforeExecuting(static function (string $query): void {
                if (str_contains($query, 'from `products`') && str_contains($query, 'for update')) {
                    echo "locking stock\n";
                    flush();
                }
            });
            $order = App\Models\Order::query()->findOrFail($argv[1]);
            try {
                $app->make(App\Services\MarketplaceOrders::class)
                    ->transition($order->farmer, $order, App\Enums\OrderStatus::Accepted, null);
                echo "accepted\n";
            } catch (Illuminate\Validation\ValidationException $error) {
                if (! isset($error->errors()['quantity_kg'])) {
                    throw $error;
                }
                echo "insufficient stock\n";
            }
            PHP;

        return new Process(
            [PHP_BINARY, '-r', $code, (string) $order->id], base_path(),
            ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'hargatani_testing', 'DB_URL' => ''],
            timeout: 15,
        );
    }
}
