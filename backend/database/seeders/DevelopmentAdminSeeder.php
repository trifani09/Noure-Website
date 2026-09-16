<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;
use RuntimeException;

class DevelopmentAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'development'])) {
            throw new LogicException('DevelopmentAdminSeeder may only run in a local or development environment.');
        }

        $name = env('DEV_ADMIN_NAME');
        $email = env('DEV_ADMIN_EMAIL');
        $password = env('DEV_ADMIN_PASSWORD');

        if (! is_string($name) || trim($name) === ''
            || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! is_string($password) || strlen($password) < 8) {
            throw new RuntimeException('Set DEV_ADMIN_NAME, a valid DEV_ADMIN_EMAIL, and DEV_ADMIN_PASSWORD of at least 8 characters.');
        }

        User::query()->updateOrCreate(
            ['email' => strtolower(trim($email))],
            ['name' => trim($name), 'password' => Hash::make($password), 'email_verified_at' => now()],
        );

        $this->command?->info('Development admin account created or updated.');
    }
}
