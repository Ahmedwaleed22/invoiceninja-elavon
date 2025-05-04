<?php

use App\Models\Gateway;
use App\Models\GatewayType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $gateway = new Gateway();
        $gateway->id = 60; // Incrementing the ID from the last one seen (59 for Forte)
        $gateway->name = 'Elavon';
        $gateway->key = 'fgq5pu3hmzb4nywau7otgr96s8kvdcxj'; // Randomly generated key
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Gateway::where('provider', 'Elavon')->delete();
    }
}; 