<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;

class SystemWalletSeeder extends Seeder
{
    public function run(): void
    {
        Wallet::firstOrCreate(
            ['is_system' => true],
            [
                'amount' => 0,
                'patient_id' => null
            ]
        );
    }
}

