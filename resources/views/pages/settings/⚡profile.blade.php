<?php

use App\Concerns\ProfileValidationRules;
use App\Enums\Role;
use App\Support\BaliDistricts;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';
    public ?string $district = null;
    public ?string $transaction_contact = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        if ($user->role === Role::Petani) {
            $this->district = $user->district;
        }
        if ($user->role !== Role::Admin) {
            $this->transaction_contact = $user->transaction_contact;
        }
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $this->authorize('update', $user);

        if (config('fortify.lowercase_usernames')) {
            $this->email = Str::lower($this->email);
        }

        $rules = $this->profileRules($user->id);
        if ($user->role === Role::Petani) {
            $rules['district'] = ['nullable', Rule::in(BaliDistricts::ALL)];
        }
        if ($user->role !== Role::Admin) {
            $rules['transaction_contact'] = ['nullable', 'string', 'max:120'];
        }

        $validated = $this->validate($rules);

        $user->fill($validated);
        if ($user->role === Role::Petani) {
            $user->district = filled($this->district) ? $this->district : null;
        }
        if ($user->role !== Role::Admin) {
            $user->transaction_contact = filled($this->transaction_contact) ? trim($this->transaction_contact) : null;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

            </div>

            @if (auth()->user()->role === \App\Enums\Role::Petani)
                <flux:select wire:model="district" label="Kabupaten/kota">
                    <option value="">Pilih kabupaten/kota</option>
                    @foreach (\App\Support\BaliDistricts::ALL as $districtOption)
                        <option value="{{ $districtOption }}">{{ $districtOption }}</option>
                    @endforeach
                </flux:select>
            @endif
            @if (auth()->user()->role !== \App\Enums\Role::Admin)
                <flux:input wire:model="transaction_contact" label="Kontak transaksi" type="text" maxlength="120"
                    description="Nomor telepon atau alamat email yang dibagikan kepada pihak terkait saat pesanan berstatus Diterima." />
            @endif

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

            </div>
        </form>

            <livewire:pages::settings.delete-user-form />
    </x-pages::settings.layout>
</section>
