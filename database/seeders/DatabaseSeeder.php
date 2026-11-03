<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL') ?: 'admin@demo.test';
        $password = env('ADMIN_PASSWORD') ?: 'demo1234';

        if (app()->isProduction() && ! env('ADMIN_PASSWORD')) {
            throw new RuntimeException('Define ADMIN_EMAIL y ADMIN_PASSWORD en el .env antes de sembrar en producción.');
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Administración', 'password' => Hash::make($password)],
        );

        $this->call(DemoStoreSeeder::class);
    }
}
