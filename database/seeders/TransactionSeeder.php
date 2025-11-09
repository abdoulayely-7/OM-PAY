<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $comptes = \App\Models\Compte::all();
        $marchands = \App\Models\Marchand::all();

        foreach ($comptes as $compte) {
            \App\Models\Transaction::factory(5)->create([
                'compte_id' => $compte->id,
                'merchant_id' => $marchands->random()->id,
            ]);
        }
    }
}
