<?php
// UsersTableSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@lazimapplication.com',
            'password' => Hash::make('password1'),
        ]);

        DB::table('users')->insert([
            'name' => 'User',
            'email' => 'user2@lazimapplication.com',
            'password' => Hash::make('password2'),
        ]);

        // Add more user data as needed
    }
}
