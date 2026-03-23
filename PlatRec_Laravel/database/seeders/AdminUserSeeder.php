<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Cek dulu apakah admin udah ada, biar nggak duplicate
        $admin = User::where('email', 'admin@platrec.com')->first();
        
        if (!$admin) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@platrec.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('✅ Admin berhasil dibuat!');
        } else {
            $this->command->info('⚠️ Admin sudah ada...');
        }
    }
}