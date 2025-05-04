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

namespace App\PaymentDrivers\Elavon;

use App\Exceptions\PaymentFailed;
use App\Http\Requests\ClientPortal\Payments\PaymentResponseRequest;
use App\Models\ClientGatewayToken;
use App\Models\GatewayType;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\SystemLog;
use App\PaymentDrivers\Common\MethodInterface;
use App\PaymentDrivers\ElavonPaymentDriver;
use App\Utils\Traits\MakesHash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CreditCard implements MethodInterface
{
    use MakesHash;

    protected $elavon;

    public function __construct(ElavonPaymentDriver $elavon)
    {
        $this->elavon = $elavon;
    }

    /**
     * Authorization page for the gateway method.
     *
     * @param array $data
     * @return mixed
     */
    public function authorizeView(array $data)
    {
        $data['gateway'] = $this->elavon;
        $data['company_gateway'] = $this->elavon->company_gateway;
        $data['client'] = $this->elavon->client;
        $data['currency'] = $this->elavon->client->getCurrencyCode();
        
        // Get existing tokens for this client/payment method
        $tokens = ClientGatewayToken::where([
            'client_id' => $this->elavon->client->id,
            'company_id' => $this->elavon->client->company_id,
            'gateway_type_id' => GatewayType::CREDIT_CARD,
        ])->get();
        
        $data['tokens'] = $tokens;
        $data['api_url'] = $this->elavon->getApiUrl();
        
        return render('gateways.elavon.credit_card.authorize', $data);
    }

    /**
     * Process the response from the authorization page.
     *
     * @param Request $request
     * @return mixed
     */
    public function authorizeResponse(Request $request)
    {
        // This is typically invoked when a credit card is added via the client portal
        // We save the token for future use
        
        if (!$request->token) {
            return redirect()->route('client.payment_methods.index')
                ->with('error', ctrans('texts.payment_method_error'));
        }
        
        $data = [
            'token' => $request->token,
            'payment_method_id' => GatewayType::CREDIT_CARD,
            'payment_meta' => [
                'brand' => $request->card_brand,
                'last4' => $request->last_4,
                'exp_month' => $request->expire_month,
                'exp_year' => $request->expire_year,
                'type' => $request->payment_type,
            ],
        ];
        
        $payment_meta = new \stdClass;
        $payment_meta->brand = $request->card_brand;
        $payment_meta->last4 = $request->last_4;
        $payment_meta->exp_month = $request->expire_month;
        $payment_meta->exp_year = $request->expire_year;
        $payment_meta->type = GatewayType::CREDIT_CARD;
        
        $data = [
            'payment_meta' => $payment_meta,
            'token' => $request->token,
            'payment_method_id' => GatewayType::CREDIT_CARD,
        ];
        
        // Add the token to the client's saved tokens
        $this->elavon->storeGatewayToken($data);
        
        return redirect()
            ->route('client.payment_methods.index')
            ->with('success', ctrans('texts.payment_method_added_success'));
    }

    /**
     * Payment page for the gateway method.
     *
     * @param array $data
     * @return mixed
     */
    public function paymentView(array $data)
    {
        $data['gateway'] = $this->elavon;
        $data['company_gateway'] = $this->elavon->company_gateway;
        $data['client'] = $this->elavon->client;
        $data['payment_hash'] = $data['payment_hash'] ?? null;
        $data['api_url'] = $this->elavon->getApiUrl();
        $data['currency'] = $this->elavon->client->getCurrencyCode();
        
        // Get client's saved payment methods
        $tokens = ClientGatewayToken::where([
            'client_id' => $this->elavon->client->id,
            'company_id' => $this->elavon->client->company_id,
            'gateway_type_id' => GatewayType::CREDIT_CARD,
        ])->get();
        
        $data['tokens'] = $tokens;
        
        // Generate a transaction token
        $transaction_token = $this->getTransactionToken($data);
        $data['transaction_token'] = $transaction_token;
        
        return render('gateways.elavon.credit_card.pay', $data);
    }

    /**
     * Generate a transaction token from Elavon
     */
    private function getTransactionToken($data)
    {
        // Extract data from payment hash
        $payment_hash = $data['payment_hash'];
        $invoices = $payment_hash->invoices();
        $total = array_sum(array_column($invoices, 'amount'));
        
        // Set up API call to get token
        $url = $this->elavon->getApiUrl('/hosted-payments/transaction_token');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        // Build API request
        $post_data = [
            'ssl_merchant_id' => $this->elavon->getMerchantId(),
            'ssl_user_id' => $this->elavon->getMerchantUserId(),
            'ssl_pin' => $this->elavon->getMerchantPinCode(),
            'ssl_vendor_id' => $this->elavon->getVendorId(),
            'ssl_invoice_number' => $payment_hash->hash,
            'ssl_transaction_type' => 'ccsale',
            'ssl_verify' => 'N',
            'ssl_get_token' => 'Y',
            'ssl_add_token' => 'Y',
            'ssl_amount' => $total,
        ];
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }

    /**
     * Process the payment response.
     *
     * @param PaymentResponseRequest $request
     * @return mixed
     */
    public function paymentResponse(PaymentResponseRequest $request)
    {
        // Get payment details from the request
        $payment_hash = $request->getPaymentHash();
        
        if (!$payment_hash) {
            throw new PaymentFailed('Invalid payment hash', 400);
        }
        
        // Prepare payment data
        $payment_data = [
            'payment_method' => $request->payment_method_id,
            'payment_type' => PaymentType::CREDIT_CARD_OTHER,
            'amount' => $payment_hash->data->amount_with_fee,
            'transaction_reference' => $request->transaction_id,
            'gateway_type_id' => GatewayType::CREDIT_CARD,
        ];
        
        // If customer wants to save the card
        if ($request->shouldStoreToken() && $request->token) {
            $payment_meta = new \stdClass;
            $payment_meta->brand = (string) $request->card_brand;
            $payment_meta->last4 = (string) $request->last_4;
            $payment_meta->exp_month = (string) $request->expire_month;
            $payment_meta->exp_year = (string) $request->expire_year;
            $payment_meta->type = GatewayType::CREDIT_CARD;
            
            $token_data = [
                'token' => $request->token,
                'payment_method_id' => GatewayType::CREDIT_CARD,
                'payment_meta' => $payment_meta,
            ];
            
            // Save the token for this client
            $this->elavon->storeGatewayToken($token_data);
        }
        
        $payment = $this->elavon->createPayment($payment_data, $payment_hash);
        
        // Log the successful payment
        SystemLog::create([
            'client_id' => $this->elavon->client->id,
            'company_id' => $this->elavon->client->company_id,
            'log' => json_encode($request->all()),
            'category_id' => SystemLog::CATEGORY_GATEWAY_RESPONSE,
            'event_id' => SystemLog::EVENT_GATEWAY_SUCCESS,
            'type_id' => $this->elavon::SYSTEM_LOG_TYPE,
        ]);
        
        return redirect()->route('client.payments.show', ['payment' => $this->encodePrimaryKey($payment->id)]);
    }
} 