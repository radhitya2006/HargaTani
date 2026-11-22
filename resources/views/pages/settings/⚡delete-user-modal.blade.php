<?php

use App\Concerns\PasswordValidationRules;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Livewire\Actions\Logout;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        $user = DB::transaction(function (): User {
            $user = User::query()->whereKey(Auth::id())->lockForUpdate()->firstOrFail();
            $hasOpenOrders = Order::query()
                ->where(fn ($orders) => $orders->where('buyer_id', $user->id)->orWhere('farmer_id', $user->id))
                ->whereIn('status', [OrderStatus::Pending, OrderStatus::Accepted])->exists();
            if ($hasOpenOrders) {
                throw ValidationException::withMessages(['password' => 'Selesaikan pesanan yang masih aktif sebelum menghapus akun.']);
            }
            if ($user->role === Role::Petani) {
                $products = Product::query()->where('farmer_id', $user->id);
                (clone $products)->whereDoesntHave('orders')->delete();
                $products->update(['status' => ProductStatus::Archived]);
            }
            $user->delete();

            return $user;
        }, 3);

        $logout();
        if ($user->role === Role::Petani) {
            Storage::disk('local')->deleteDirectory(Product::photoDirectory($user->id));
        }

        $this->redirect('/', navigate: true);
    }
}; ?>

<flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Are you sure you want to delete your account?') }}</flux:heading>

            <flux:subheading>
                @if (auth()->user()->role === Role::Admin)
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                @else
                    Masukkan kata sandi untuk menghapus akun. Pihak terkait tetap dapat melihat riwayat pesanan. Nama akun dan kontak transaksi Anda dihapus.
                @endif
            </flux:subheading>
        </div>

        <flux:input wire:model="password" :label="__('Password')" type="password" viewable />

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                {{ __('Delete account') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
