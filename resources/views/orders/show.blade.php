@use('App\Enums\OrderStatus')

<x-layouts::app :title="'Pesanan #'.$order->id">
    <div class="max-w-form space-y-5">
        <flux:button variant="ghost" icon="arrow-left" :href="route('orders.index')">
            {{ $isFarmer ? 'Pesanan Masuk' : 'Pesanan Saya' }}
        </flux:button>
        <x-ui.page-heading :title="'Pesanan #'.$order->id">
            <x-slot:actions>
                @include('orders.status', ['status' => $order->status])
            </x-slot:actions>
        </x-ui.page-heading>

        @if (session('status'))
            <p role="status" class="rounded-control bg-primary-soft p-4 text-success">{{ session('status') }}</p>
        @endif
        @if ($errors->hasAny(['quantity_kg', 'status']))
            <p role="alert" class="rounded-control bg-warning-soft p-4 text-warning">{{ $errors->first(['quantity_kg', 'status']) }}</p>
        @endif

        <x-ui.panel>
            <h2 class="text-xl font-semibold break-words">{{ $order->product->name }}</h2>
            <dl class="mt-4 grid grid-cols-2 gap-4 break-words text-sm">
                <div>
                    <dt class="text-ink-muted">{{ $isFarmer ? 'Pembeli' : 'Petani' }}</dt>
                    <dd class="mt-1 font-semibold">{{ $party?->name ?? 'Akun dihapus' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Jumlah pesanan</dt>
                    <dd class="mt-1 font-semibold tabular-nums">{{ str_replace('.', ',', $order->quantity_kg) }} kg</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Harga saat dipesan</dt>
                    <dd class="mt-1 font-semibold tabular-nums">Rp{{ number_format($order->price_per_kg, 0, ',', '.') }}/kg</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Total pesanan</dt>
                    <dd class="mt-1 font-semibold tabular-nums">{{ $order->formattedTotal() }}</dd>
                </div>
                @if (filled($order->notes))
                    <div class="col-span-2">
                        <dt class="text-ink-muted">Catatan pembeli</dt>
                        <dd class="mt-1 whitespace-pre-line">{{ $order->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.panel>

        @if ($canViewContact)
            <x-ui.panel>
                <h2 class="text-lg font-semibold">Kontak dan pengambilan</h2>
                <p class="mt-2 break-words">{{ $party?->transaction_contact ?: 'Kontak transaksi belum diisi.' }}</p>
                <p class="mt-2 break-words text-sm text-ink-muted">{{ $order->product->pickup_location }}, {{ $order->product->district }}, Bali</p>
                <p class="mt-2 text-sm text-ink-muted">Sepakati pembayaran dan pengambilan langsung dengan {{ $isFarmer ? 'pembeli' : 'petani' }}.</p>
                @unless (auth()->user()->transaction_contact)
                    <flux:button size="sm" class="mt-3" :href="route('profile.edit')">Isi kontak transaksi Anda</flux:button>
                @endunless
            </x-ui.panel>
        @endif

        @if ($order->status === OrderStatus::Pending)
            <x-ui.panel>
                <h2 class="text-lg font-semibold">{{ $isFarmer ? 'Tanggapi pesanan' : 'Menunggu tanggapan petani' }}</h2>
                <p class="mt-2 text-sm text-ink-muted">
                    @if ($isFarmer)
                        Stok tersedia {{ str_replace('.', ',', $order->product->stock_kg) }} kg. Stok dialokasikan saat pesanan diterima.
                    @else
                        Pesanan dapat dibatalkan selama masih menunggu.
                    @endif
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    @if ($isFarmer)
                        <form method="POST" action="{{ route('orders.update', [$order, OrderStatus::Accepted]) }}">
                            @csrf
                            @method('PATCH')
                            <flux:button type="submit" variant="primary">Terima pesanan</flux:button>
                        </form>
                        <form method="POST" action="{{ route('orders.update', [$order, OrderStatus::Rejected]) }}">
                            @csrf
                            @method('PATCH')
                            <flux:button type="submit">Tolak pesanan</flux:button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('orders.update', [$order, OrderStatus::Cancelled]) }}">
                            @csrf
                            @method('PATCH')
                            <flux:button type="submit" variant="danger">Batalkan pesanan</flux:button>
                        </form>
                    @endif
                </div>
            </x-ui.panel>
        @elseif ($order->status === OrderStatus::Accepted)
            <x-ui.panel>
                @if ($isFarmer)
                    <h2 class="text-lg font-semibold">Batalkan pesanan</h2>
                    <form method="POST" action="{{ route('orders.update', [$order, OrderStatus::Cancelled]) }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PATCH')
                        <flux:textarea name="reason" label="Alasan pembatalan" rows="3" maxlength="500" required>{{ is_string(old('reason')) ? old('reason') : '' }}</flux:textarea>
                        <flux:button type="submit" variant="danger">Batalkan pesanan</flux:button>
                    </form>
                @else
                    <h2 class="text-lg font-semibold">Konfirmasi penerimaan</h2>
                    <p class="mt-2 text-sm text-ink-muted">Tandai selesai setelah Anda menerima barang.</p>
                    <form method="POST" action="{{ route('orders.update', [$order, OrderStatus::Completed]) }}" class="mt-4">
                        @csrf
                        @method('PATCH')
                        <flux:button type="submit" variant="primary">Tandai selesai</flux:button>
                    </form>
                @endif
            </x-ui.panel>
        @endif

        <x-ui.panel>
            <h2 class="text-lg font-semibold">Riwayat pesanan</h2>
            <ol class="mt-2 divide-y divide-line">
                @foreach ($order->statusChanges as $change)
                    <li class="space-y-1 py-3 text-sm">
                        <p class="font-semibold break-words">{{ $change->status->label() }} · {{ $change->actor?->name ?? 'Akun dihapus' }}</p>
                        <time class="text-ink-muted" datetime="{{ $change->created_at->toIso8601String() }}">
                            {{ $change->created_at->translatedFormat('j F Y, H.i') }} WITA
                        </time>
                        @if ($change->reason)
                            <p class="break-words whitespace-pre-line">{{ $change->reason }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </x-ui.panel>
    </div>
</x-layouts::app>
