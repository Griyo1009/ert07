<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::insert([
            [
                'NIK' => '1234567890123456',
                'nama_lengkap' => 'Admin Utama',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'status_akun' => '1',
                'role' => 1,
                'foto_profil' => null,
                'email' => 'admin@example.com',
            ],
            [
                'NIK' => '3201123456789012',
                'nama_lengkap' => 'Warga Biasa',
                'username' => 'warga1',
                'password' => Hash::make('password'),
                'status_akun' => '1', 
                'role' => 0,
                'foto_profil' => null,
                'email' => 'warga@example.com',
            ],
        ]);
    }
}
