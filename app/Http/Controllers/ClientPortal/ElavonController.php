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

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\CompanyGateway;
use App\Models\Invoice;
use App\Models\PaymentHash;
use App\Models\GatewayType;
use App\PaymentDrivers\ElavonPaymentDriver;
use App\Utils\Traits\MakesHash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ElavonController extends Controller
{
    use MakesHash;

    /**
     * Get a transaction token from Elavon for client-side processing
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTransactionToken(Request $request)
    {
        $client = auth()->guard('contact')->user()->client;
        
        // Find the Elavon gateway for this client's company
        $gateway = CompanyGateway::where('company_id', $client->company_id)
            ->whereHas('gateway', function ($query) {
                $query->where('provider', 'Elavon');
            })
            ->first();
            
        if (!$gateway) {
            return response()->json(['error' => 'Elavon gateway not configured'], 400);
        }
        
        $driver = $gateway->driver($client);
        
        if (!$driver || !($driver instanceof ElavonPaymentDriver)) {
            return response()->json(['error' => 'Invalid gateway driver'], 400);
        }
        
        // Get the amount from the request, or default to 1.00 for token generation
        $amount = $request->ssl_amount ?? '1.00';
        
        // Generate a transaction token
        $url = $driver->getApiUrl('/hosted-payments/transaction_token');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        $post_data = [
            'ssl_merchant_id' => $driver->getMerchantId(),
            'ssl_user_id' => $driver->getMerchantUserId(),
            'ssl_pin' => $driver->getMerchantPinCode(),
            'ssl_vendor_id' => $driver->getVendorId(),
            'ssl_transaction_type' => 'ccsale',
            'ssl_verify' => 'N',
            'ssl_get_token' => 'Y',
            'ssl_add_token' => 'Y',
            'ssl_amount' => $amount,
        ];
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        if (!$result) {
            return response()->json(['error' => 'Failed to get transaction token'], 500);
        }
        
        return response($result)->header('Content-Type', 'text/plain');
    }

    /**
     * Initialize payment for an invoice
     *
     * @param Request $request
     * @param string $invoice_id Hashed invoice ID
     * @return \Illuminate\Http\Response
     */
    public function payNow(Request $request, $invoice_id)
    {
        $client = auth()->guard('contact')->user()->client;
        
        // Find the Elavon gateway for this client's company
        $gateway = CompanyGateway::where('company_id', $client->company_id)
            ->whereHas('gateway', function ($query) {
                $query->where('provider', 'Elavon');
            })
            ->first();
            
        if (!$gateway) {
            return back()->with('error', 'Elavon gateway not configured');
        }
        
        $driver = $gateway->driver($client);
        
        if (!$driver || !($driver instanceof ElavonPaymentDriver)) {
            return back()->with('error', 'Invalid gateway driver');
        }
        
        // Get the invoice
        $invoice = Invoice::whereHas('company', function ($query) use ($client) {
            $query->where('id', $client->company_id);
        })
        ->where('id', $this->decodePrimaryKey($invoice_id))
        ->where('client_id', $client->id)
        ->firstOrFail();
        
        // Create a payment hash
        $payment_hash = new PaymentHash();
        $payment_hash->hash = Str::random(32);
        $payment_hash->data = [
            'amount' => $invoice->balance,
            'amount_with_fee' => $invoice->balance,
            'invoices' => [
                ['invoice_id' => $invoice_id, 'amount' => $invoice->balance]
            ],
            'currency_code' => $client->getCurrencyCode(),
        ];
        $payment_hash->fee_total = 0;
        $payment_hash->fee_invoice_id = null;
        $payment_hash->save();
        
        // Redirect to the payment method's page
        return $driver
            ->setPaymentMethod(GatewayType::CREDIT_CARD)
            ->processPaymentView([
                'payment_hash' => $payment_hash,
                'gateway' => $gateway,
            ]);
    }

    /**
     * Initialize payment for multiple invoices
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function payBulkNow(Request $request)
    {
        $client = auth()->guard('contact')->user()->client;
        
        // Find the Elavon gateway for this client's company
        $gateway = CompanyGateway::where('company_id', $client->company_id)
            ->whereHas('gateway', function ($query) {
                $query->where('provider', 'Elavon');
            })
            ->first();
            
        if (!$gateway) {
            return back()->with('error', 'Elavon gateway not configured');
        }
        
        $driver = $gateway->driver($client);
        
        if (!$driver || !($driver instanceof ElavonPaymentDriver)) {
            return back()->with('error', 'Invalid gateway driver');
        }
        
        // Get the invoice IDs from the request
        $invoice_ids = $request->input('invoice_ids', []);
        if (empty($invoice_ids)) {
            return back()->with('error', 'No invoices selected');
        }
        
        // Get the invoices
        $invoices = Invoice::whereHas('company', function ($query) use ($client) {
            $query->where('id', $client->company_id);
        })
        ->whereIn('id', array_map([$this, 'decodePrimaryKey'], $invoice_ids))
        ->where('client_id', $client->id)
        ->get();
        
        if ($invoices->isEmpty()) {
            return back()->with('error', 'No valid invoices found');
        }
        
        // Calculate total amount
        $total_amount = $invoices->sum('balance');
        
        // Create payment hash data
        $payment_hash_data = [
            'amount' => $total_amount,
            'amount_with_fee' => $total_amount,
            'invoices' => [],
            'currency_code' => $client->getCurrencyCode(),
        ];
        
        // Add each invoice to the payment hash data
        foreach ($invoices as $invoice) {
            $payment_hash_data['invoices'][] = [
                'invoice_id' => $this->encodePrimaryKey($invoice->id),
                'amount' => $invoice->balance
            ];
        }
        
        // Create a payment hash
        $payment_hash = new PaymentHash();
        $payment_hash->hash = Str::random(32);
        $payment_hash->data = $payment_hash_data;
        $payment_hash->fee_total = 0;
        $payment_hash->fee_invoice_id = null;
        $payment_hash->save();
        
        // Redirect to the payment method's page
        return $driver
            ->setPaymentMethod(GatewayType::CREDIT_CARD)
            ->processPaymentView([
                'payment_hash' => $payment_hash,
                'gateway' => $gateway,
            ]);
    }
} 