<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class GatewayKeyFixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Fix Elavon gateway key if it's incorrect
        $elavon = Gateway::where('provider', 'Elavon')->first();

        if ($elavon) {
            $elavon->key = 'fgq5pu3hmzb4nywau7otgr96s8kvdcxj';
            $elavon->save();
            $this->command->info('Elavon gateway key has been fixed.');
        } else {
            $this->command->info('Elavon gateway not found.');
        }

        // Clear the cache to ensure the new key is used
        Cache::forget('gateways');
        Cache::flush();
        $this->command->info('Cache cleared.');
    }
} 