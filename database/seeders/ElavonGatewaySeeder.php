<?php

namespace Database\Seeders;

use App\Models\Gateway;
use App\Models\GatewayType;
use Illuminate\Database\Seeder;

class ElavonGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if the gateway already exists
        if (Gateway::where('provider', 'Elavon')->exists()) {
            $this->command->info('Elavon gateway already exists.');
            return;
        }
        
        // Get the highest ID from gateways table
        $maxId = Gateway::max('id');
        $newId = $maxId + 1;
        
        $gateway = new Gateway();
        $gateway->id = $newId; // Use next available ID
        $gateway->name = 'Elavon';
        $gateway->key = 'fgq5pu3hmzb4nywau7otgr96s8kvdcxj'; // Use the key from the migration
        $gateway->provider = 'Elavon';
        $gateway->is_offsite = false;

        $configuration = new \stdClass;
        $configuration->merchantID = '';
        $configuration->merchantUserID = '';
        $configuration->merchantPinCode = '';
        $configuration->vendorID = '';
        $configuration->testMode = true;

        $gateway->fields = \json_encode($configuration);
        $gateway->visible = true;
        $gateway->site_url = 'https://www.elavon.com/';
        $gateway->default_gateway_type_id = GatewayType::CREDIT_CARD;
        $gateway->save();
        
        $this->command->info('Elavon gateway added successfully.');
    }
} 