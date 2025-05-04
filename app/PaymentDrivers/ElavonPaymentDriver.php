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

namespace App\PaymentDrivers;

use App\Models\ClientGatewayToken;
use App\Models\GatewayType;
use App\Models\Payment;
use App\Models\PaymentHash;
use App\Models\SystemLog;
use App\PaymentDrivers\Elavon\CreditCard;
use App\PaymentDrivers\Elavon\TokenBilling;
use App\Utils\Traits\MakesHash;

class ElavonPaymentDriver extends BaseDriver
{
    use MakesHash;

    public $refundable = true;

    public $token_billing = true;

    public $can_authorise_credit_card = true;

    public $payment_method;

    public static $methods = [
        GatewayType::CREDIT_CARD => CreditCard::class,
    ];

    public const SYSTEM_LOG_TYPE = SystemLog::TYPE_ELAVON;

    public function init(): self
    {
        return $this;
    }

    public function gatewayTypes(): array
    {
        $types = [];
        $types[] = GatewayType::CREDIT_CARD;
        
        return $types;
    }

    public function setPaymentMethod($payment_method_id)
    {
        $class = self::$methods[$payment_method_id];
        $this->payment_method = new $class($this);

        return $this;
    }

    public function authorizeView(array $data)
    {
        return $this->payment_method->authorizeView($data);
    }

    public function authorizeResponse($request)
    {
        return $this->payment_method->authorizeResponse($request);
    }

    public function processPaymentView(array $data)
    {
        return $this->payment_method->paymentView($data);
    }

    public function processPaymentResponse($request)
    {
        return $this->payment_method->paymentResponse($request);
    }

    /**
     * Process a refund request
     *
     * @param Payment $payment
     * @param float $amount
     * @param bool $return_client_response
     * @return mixed
     */
    public function refund(Payment $payment, $amount, $return_client_response = false)
    {
        // Refund implementation would go here
        // For now, we'll return an error indicating refunds not implemented yet
        
        if ($return_client_response) {
            return [
                'status' => 'error',
                'error' => 'Refunds not implemented for Elavon payment gateway yet'
            ];
        }
        
        return false;
    }

    /**
     * Tokenized payment
     *
     * @param ClientGatewayToken $cgt
     * @param PaymentHash $payment_hash
     * @return mixed
     */
    public function tokenBilling(ClientGatewayToken $cgt, PaymentHash $payment_hash)
    {
        $token_billing = new TokenBilling($this);
        return $token_billing->tokenBilling($cgt, $payment_hash);
    }

    /**
     * Get Elavon API URL based on mode
     *
     * @return string
     */
    public function getApiUrl($endpoint = null): string
    {
        $is_test_mode = (bool) $this->company_gateway->getConfigField('testMode');
        
        $base_url = $is_test_mode 
            ? 'https://api.demo.convergepay.com'
            : 'https://api.convergepay.com';
            
        return $base_url . ($endpoint ? $endpoint : '');
    }

    /**
     * Get merchant ID from config
     */
    public function getMerchantId()
    {
        return $this->company_gateway->getConfigField('merchantID');
    }

    /**
     * Get merchant user ID from config
     */
    public function getMerchantUserId()
    {
        return $this->company_gateway->getConfigField('merchantUserID');
    }

    /**
     * Get merchant PIN code from config
     */
    public function getMerchantPinCode()
    {
        return $this->company_gateway->getConfigField('merchantPinCode');
    }

    /**
     * Get vendor ID from config
     */
    public function getVendorId()
    {
        return $this->company_gateway->getConfigField('vendorID');
    }
} 