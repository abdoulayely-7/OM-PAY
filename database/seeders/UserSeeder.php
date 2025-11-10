<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un admin
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@om-pay.com',
            'telephone' => '+221771234567',
            'role' => 'admin',
        ]);

        // Créer un distributeur
        User::factory()->create([
            'name' => 'Distributeur User',
            'email' => 'distributeur@om-pay.com',
            'telephone' => '+221772345678',
            'role' => 'distributeur',
        ]);

        // Créer des clients
        User::factory(10)->create([
            'role' => 'client',
        ]);
    }
}
