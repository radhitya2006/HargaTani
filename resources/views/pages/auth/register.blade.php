<x-layouts::auth.split title="Daftar" heading="Buat akun HargaTani." description="Bergabung sebagai pembeli atau petani untuk memulai.">
    <form method="POST" action="{{ route('register.store') }}" class="ht-auth-form">
        @csrf

        <flux:input
            name="name"
            label="Nama lengkap"
            :value="old('name')"
            type="text"
            required
            autocomplete="name"
            placeholder="Masukkan nama lengkap"
        />

        <flux:input
            name="email"
            label="Alamat email"
            :value="old('email')"
            type="email"
            required
            autocomplete="email"
            placeholder="nama@email.com"
        />

        <flux:select name="role" label="Daftar sebagai" required>
            <option value="pembeli" @selected(old('role', 'pembeli') === 'pembeli')>Pembeli</option>
            <option value="petani" @selected(old('role') === 'petani')>Petani</option>
        </flux:select>

        <flux:input
            name="password"
            label="Kata sandi"
            type="password"
            required
            autocomplete="new-password"
            placeholder="Buat kata sandi"
            passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
            viewable
        />

        <flux:input
            name="password_confirmation"
            label="Konfirmasi kata sandi"
            type="password"
            required
            autocomplete="new-password"
            placeholder="Ulangi kata sandi"
            passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
            viewable
        />

        <flux:button type="submit" variant="primary" icon:trailing="arrow-right" class="ht-auth-submit" data-test="register-user-button">
            Buat akun
        </flux:button>
    </form>

    <x-slot:switch>
        Sudah punya akun? <a class="ht-auth-link" href="{{ route('login') }}">Masuk</a>
    </x-slot:switch>
</x-layouts::auth.split>
