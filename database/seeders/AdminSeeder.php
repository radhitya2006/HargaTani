<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('seeding.admin_email');
        $password = config('seeding.admin_password');
        if (! $email || ! $password) {
            throw new RuntimeException('Isi ADMIN_EMAIL dan ADMIN_PASSWORD di .env sebelum menjalankan seeder.');
        }

        Validator::make(['email' => $email, 'password' => $password], [
            'email' => ['required', 'email'],
            'password' => ['required', Password::min(12)],
        ])->validate();

        $existing = User::where('email', $email)->first();
        if ($existing) {
            if ($existing->role !== Role::Admin) {
                throw new RuntimeException('Email admin sudah dipakai akun non-admin. Akun tersebut tidak diubah.');
            }

            return; // Re-running seeds must not reset a password or reactivate an account.
        }

        $user = new User;
        $user->name = config('seeding.admin_name');
        $user->email = $email;
        $user->password = $password;
        $user->role = Role::Admin;
        $user->is_active = true;
        $user->save();
    }
}
