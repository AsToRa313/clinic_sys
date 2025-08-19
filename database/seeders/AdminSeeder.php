<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use  Illuminate\Support\Facades\Hash;
class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
                'first_name' =>'admin',
                'last_name' =>'admin',
                'email'      =>'admin@gmail.com',
                'phone'      => '+9631111111',
                'role'       => 'admin',
                'gender'     => 'male',
                'password'   => Hash::make('admin123'),
                
            ]);
    }
}
