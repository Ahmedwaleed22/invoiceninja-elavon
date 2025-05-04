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
use App\Models\ClientGatewayToken;
use App\Models\GatewayType;
use App\Models\PaymentHash;
use App\Models\PaymentType;
use App\Models\SystemLog;
use App\PaymentDrivers\ElavonPaymentDriver;

class TokenBilling
{
    protected $elavon;

    public function __construct(ElavonPaymentDriver $elavon)
    {
        $this->elavon = $elavon;
    }

    /**
     * Process payment using token
     *
     * @param ClientGatewayToken $token
     * @param PaymentHash $payment_hash
     * @return mixed
     */
    public function tokenBilling(ClientGatewayToken $token, PaymentHash $payment_hash)
    {
        // Make API request to process payment with saved token
        $response = $this->processTokenPayment($token, $payment_hash);
        
        if ($response['success']) {
            $payment_data = [
                'payment_method' => $token->gateway_type_id,
                'payment_type' => PaymentType::CREDIT_CARD_OTHER,
                'amount' => $payment_hash->data->amount_with_fee,
                'transaction_reference' => $response['transaction_id'],
                'gateway_type_id' => GatewayType::CREDIT_CARD,
            ];
            
            $payment = $this->elavon->createPayment($payment_data, $payment_hash);
            
            SystemLog::create([
                'client_id' => $this->elavon->client->id,
                'company_id' => $this->elavon->client->company_id,
                'log' => 'Token billing successful. Transaction ID: '.$response['transaction_id'],
                'category_id' => SystemLog::CATEGORY_GATEWAY_RESPONSE,
                'event_id' => SystemLog::EVENT_GATEWAY_SUCCESS,
                'type_id' => $this->elavon::SYSTEM_LOG_TYPE,
            ]);
            
            return [
                'transaction_reference' => $response['transaction_id'],
                'amount' => $payment_hash->data->amount_with_fee,
                'status' => 'successful',
                'success' => true,
            ];
        } else {
            SystemLog::create([
                'client_id' => $this->elavon->client->id,
                'company_id' => $this->elavon->client->company_id,
                'log' => 'Token billing failed: '.$response['error'],
                'category_id' => SystemLog::CATEGORY_GATEWAY_RESPONSE,
                'event_id' => SystemLog::EVENT_GATEWAY_FAILURE,
                'type_id' => $this->elavon::SYSTEM_LOG_TYPE,
            ]);
            
            throw new PaymentFailed($response['error']);
        }
    }

    /**
     * Process payment using a token via Elavon XML API
     *
     * @param ClientGatewayToken $token
     * @param PaymentHash $payment_hash
     * @return array
     */
    private function processTokenPayment(ClientGatewayToken $token, PaymentHash $payment_hash)
    {
        $invoices = $payment_hash->invoices();
        $total = array_sum(array_column($invoices, 'amount'));
        
        // The endpoint for token payments is different from the hosted payment endpoint
        $api_url = $this->elavon->getApiUrl('/VirtualMerchant/processxml.do');
        
        // Create XML request
        $xml_request = "<txn>
  <ssl_transaction_type>ccsale</ssl_transaction_type>
  <ssl_account_id>{$this->elavon->getMerchantId()}</ssl_account_id>
  <ssl_user_id>{$this->elavon->getMerchantUserId()}</ssl_user_id>
  <ssl_pin>{$this->elavon->getMerchantPinCode()}</ssl_pin>
  <ssl_token>{$token->token}</ssl_token>
  <ssl_amount>{$total}</ssl_amount>
  <ssl_merchant_initiated_unscheduled>Y</ssl_merchant_initiated_unscheduled>
  <ssl_partial_auth_indicator>0</ssl_partial_auth_indicator>
  <ssl_invoice_number>{$payment_hash->hash}</ssl_invoice_number>
</txn>";
        
        // Initialize cURL session for token payment
        $ch = curl_init($api_url);
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'xmldata='.urlencode($xml_request));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // Execute the request
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        
        // Close cURL session
        curl_close($ch);
        
        if ($response === false) {
            return [
                'success' => false,
                'error' => $curl_error,
            ];
        }
        
        // Process XML response
        $xml_response = @simplexml_load_string($response);
        
        if ($xml_response && isset($xml_response->ssl_result) && $xml_response->ssl_result == 0) {
            return [
                'success' => true,
                'transaction_id' => (string)$xml_response->ssl_txn_id,
                'authorization_code' => (string)$xml_response->ssl_approval_code,
            ];
        } else {
            $error_message = 'Unknown error processing payment';
            
            if ($xml_response) {
                if (isset($xml_response->errorCode)) {
                    $error_message = (string)$xml_response->errorMessage;
                } elseif (isset($xml_response->ssl_result)) {
                    $error_message = (string)$xml_response->ssl_result_message;
                }
            }
            
            return [
                'success' => false,
                'error' => $error_message,
            ];
        }
    }
} 