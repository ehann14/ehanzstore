<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Gunakan environment variables untuk keamanan kredensial awal
        $email = env('SUPER_ADMIN_EMAIL', 'admin@ehanzstore.id');
        $password = env('SUPER_ADMIN_PASSWORD', 'EhanzStore2026!');

        if (!User::where('email', $email)->exists()) {
            User::create([
                'name' => 'Super Administrator',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'super_admin',
                'is_active' => true,
            ]);
        }
    }
}