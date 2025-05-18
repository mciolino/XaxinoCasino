<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@xaxino.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'kyc_status' => 'verified',
            'status' => 'active',
        ]);
        
        $this->command->info('Admin user created: admin@xaxino.com / admin123');
    }
}
