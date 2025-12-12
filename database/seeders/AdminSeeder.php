<?php
// database/seeders/AdminSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'adminemail@gmail.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Admin',
                'email' => 'adminemail@gmail.com',
                'password' => Hash::make('admin123'), // Change this password!
                'role' => 'admin',
                'email_verified_at' => now(), // Admin is pre-verified
            ]
        );

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: adminemail@gmail.com');
        $this->command->info('Password: admin123');
        $this->command->warn('⚠️  Remember to change the password after first login!');
    }
}
