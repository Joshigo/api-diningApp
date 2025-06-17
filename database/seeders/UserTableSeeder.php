<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'role_id' => 1,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
        User::create([
            'name' => 'john doe',
            'username' => 'manager',
            'role_id' => 2,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
    }
}
