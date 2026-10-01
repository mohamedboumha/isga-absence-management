<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {

    public function run() : void {
        User::updateOrCreate(
            ['email' => 'admin@isga.ma'],
            [
                'name'              => 'Admin',
                'prenom'            => 'Super',
                'password'          => 'admin',
                'role'              => 'super_admin',
                'actif'             => true,
                'email_verified_at' => now(),
            ]
        );
        $this->call(StructureIsgaSeeder::class);
    }
}
