<x-layouts::auth.split title="Masuk" heading="Selamat datang kembali." description="Masuk untuk melanjutkan langkah Anda.">
    <form method="POST" action="{{ route('login.store') }}" class="ht-auth-form">
        @csrf

        <flux:input
            name="email"
            label="Alamat email"
            :value="old('email')"
            type="email"
            required
            autocomplete="email"
            placeholder="nama@email.com"
        />

        <flux:input
            name="password"
            label="Kata sandi"
            type="password"
            required
            autocomplete="current-password"
            placeholder="Masukkan kata sandi"
            viewable
        />

        <div class="ht-auth-options">
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            @if (Route::has('password.request'))
                <a class="ht-auth-link" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
            @endif
        </div>

        <flux:button variant="primary" type="submit" icon:trailing="arrow-right" class="ht-auth-submit" data-test="login-button">
            Masuk
        </flux:button>
    </form>

    <x-slot:switch>
        Belum punya akun? <a class="ht-auth-link" href="{{ route('register') }}">Daftar sekarang</a>
    </x-slot:switch>
</x-layouts::auth.split>
