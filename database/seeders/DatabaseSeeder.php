<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@thuoc360.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        $this->call(CategorySeeder::class);
    }
}
