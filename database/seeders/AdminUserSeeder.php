<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'munsi@gmail.com',
            'password' => Hash::make('munsi@2025'),
            'role' => 'admin',
            'status' => 'active',
            'photo' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
