<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Create Personal Super Admin
        User::create([
            'name' => 'Ray',
            'email' => 'raykingstonmusic@gmail.com',
            'email_verified_at' => now(),
            'password' => '$2y$12$kkUCdnYDJFCsPDWxraALiO6GxIji7bhcr8mcJRLr.U3GT8AYKuWku', // *_^edl/^
        ]);

        User::factory(2)->create();
    }
}
