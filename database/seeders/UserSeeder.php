<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::insert([
            'name' => "admin",
            'email' => "admin@mail.com",
            'password' => bcrypt("123456"),
            'status' => "active",
            'role' => "admin"
        ]);

        User::insert([
            'name' => "user",
            'email' => "user@mail.com",
            'password' => bcrypt("123456"),
            'status' => "active",
            'role' => "user"
        ]);
    }
}
