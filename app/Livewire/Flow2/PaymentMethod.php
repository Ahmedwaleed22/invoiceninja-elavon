<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2025. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Livewire\Flow2;

use App\Utils\Traits\WithSecureContext;
use Livewire\Component;
use App\Libraries\MultiDB;
use App\Models\CompanyGateway;
use App\Models\Gateway;

class PaymentMethod extends Component
{
    use WithSecureContext;

    // public $invoice;

    public $variables;

    public $methods = [];

    public $isLoading = true;

    public $amount = 0;

    public function placeholder()
    {
        return <<<'HTML'
        <div  class="flex items-center justify-center min-h-screen">
        <svg class="animate-spin h-10 w-10 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        </div>
        HTML;
    }

    public function handleSelect(string $company_gateway_id, string $gateway_type_id, string $amount)
    {
        $this->isLoading = true;

        $this->dispatch(
            event: 'payment-method-selected',
            company_gateway_id: $company_gateway_id,
            gateway_type_id: $gateway_type_id,
            amount: $amount,
        );
    }

    public function mount()
    {
        $_context = $this->getContext();
        $this->variables = $_context['variables'];
        $this->amount = array_sum(array_column($_context['payable_invoices'], 'amount'));

        MultiDB::setDb($_context['db']);

        $contact = $_context['contact'] ?? auth()->guard('contact')->user();

        $this->methods = $contact->client->service()->getPaymentMethods($this->amount);

        if (count($this->methods) == 1) {
            $this->dispatch('singlePaymentMethodFound', company_gateway_id: $this->methods[0]['company_gateway_id'], gateway_type_id: $this->methods[0]['gateway_type_id'], amount: $this->amount);
        } else {
            $this->isLoading = false;
            $this->dispatch('loadingCompleted');
        }
    }

    private function getElavonSessionToken($merchantID, $merchantUserID, $merchantPinCode, $vendorID, $isTestMode)
    {
        $url = $isTestMode ? "https://api.demo.convergepay.com/hosted-payments/transaction_token" : "https://api.convergepay.com/hosted-payments/transaction_token";


        $ch = curl_init();    // initialize curl handle
        curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
        curl_setopt($ch, CURLOPT_POST, true); // set POST method
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $amount = $this->amount;
        $invoiceNumber = "INV" . random_int(100, 100000);
        
        // Set up the post fields. If you want to add custom fields, you would add them in Converge, and add the field name in the curlopt_postfields string.
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            "ssl_merchant_id=$merchantID" .
                "&ssl_user_id=$merchantUserID" .
                "&ssl_pin=$merchantPinCode" .
                "&ssl_vendor_id=$vendorID" .
                "&ssl_invoice_number=$invoiceNumber" .
                "&ssl_transaction_type=ccsale" .
                "&ssl_verify=N" . //set to 'Y'if transaction type is ccgettoken, otherwise not needed
                "&ssl_get_token=Y" . //pass with 'Y' if you wish to tokenize the card as part of a ccsale, do not send if transactoin type set to ccgettoken
                "&ssl_add_token=Y" . // should always be Y if using card manager and either transaction type is set to 'Y' or if ssl_get_token is set to 'Y'.
                "&ssl_amount=$amount" //do not pass amount if using ccgettoken as the transaction type
        );


        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $result = curl_exec($ch); // run the curl procss
        curl_close($ch); // Close cURL

        // echo $result;  //shows the session token. 

        return $result;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        $gateway = Gateway::where('name', 'Elavon')->first();
        $companyGateway = CompanyGateway::where('gateway_key', $gateway->key)->first();
        $gatewayConfig = $companyGateway->getConfig();

        $merchantID = $gatewayConfig->merchantID;
        $merchantUserID = $gatewayConfig->merchantUserID;
        $merchantPinCode = $gatewayConfig->merchantPinCode;
        $vendorID = $gatewayConfig->vendorID;
        $testMode = $gatewayConfig->testMode;

        $sessionToken = $this->getElavonSessionToken($merchantID, $merchantUserID, $merchantPinCode, $vendorID, $testMode);

        return render('flow2.payment-method', [
            'methods' => $this->methods,
            'elavon_session_token' => $sessionToken,
            'amount' => $this->amount,
        ]);
    }

    public function exception($e, $stopPropagation)
    {
        app('sentry')->captureException($e);
        $stopPropagation();
    }
}
