<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    use WithoutModelEvents;

    public function run() : void {
        User::updateOrCreate(
            ['email' => 'admin@isga.ma'],
            [
                'name'              => 'Admin',
                'prenom'            => 'Super',
                'password'          => 'password',
                'role'              => 'super_admin',
                'actif'             => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
