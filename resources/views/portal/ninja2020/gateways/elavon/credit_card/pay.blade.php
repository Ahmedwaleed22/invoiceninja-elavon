@extends('portal.ninja2020.layout.payments', ['gateway_title' => 'Credit card', 'card_title' => 'Credit Card'])

@section('gateway_head')
    <meta name="elavon-api-url" content="{{ $api_url }}">
    <script src="https://api.demo.convergepay.com/hosted-payments/Checkout.js"></script>
@endsection

@section('gateway_content')
    <form action="{{ route('client.payments.response') }}" method="post" id="server-response">
        @csrf
        <input type="hidden" name="company_gateway_id" value="{{ $company_gateway->id }}">
        <input type="hidden" name="payment_method_id" value="{{ GatewayType::CREDIT_CARD }}">
        <input type="hidden" name="payment_hash" value="{{ $payment_hash->hash }}">
        <input type="hidden" name="transaction_id">
        <input type="hidden" name="token">
        <input type="hidden" name="card_brand">
        <input type="hidden" name="last_4">
        <input type="hidden" name="expire_month">
        <input type="hidden" name="expire_year">
        <input type="hidden" name="store_card" value="false">
    </form>

    <div class="alert alert-failure mb-4" hidden id="errors"></div>

    @include('portal.ninja2020.gateways.includes.payment_details')

    @component('portal.ninja2020.components.general.card-element', ['title' => ctrans('texts.pay_with')])
        @if(count($tokens) > 0)
            @foreach($tokens as $token)
                <label class="mr-4">
                    <input
                        type="radio"
                        data-token="{{ $token->token }}"
                        name="payment-type"
                        class="form-radio cursor-pointer toggle-payment-with-token"/>
                    <span class="ml-1 cursor-pointer">**** {{ $token->meta?->last4 }}</span>
                </label>
            @endforeach
        @endisset

        <label>
            <input
                type="radio"
                id="toggle-payment-with-credit-card"
                class="form-radio cursor-pointer"
                name="payment-type"
                checked/>
            <span class="ml-1 cursor-pointer">{{ __('texts.new_card') }}</span>
        </label>
    @endcomponent

    <div id="new-card-form">
        @component('portal.ninja2020.components.general.card-element', ['title' => ctrans('texts.payment_details')])
            <div class="bg-white px-4 py-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="card_number" class="block text-sm font-medium text-gray-700">{{ ctrans('texts.card_number') }}</label>
                        <input type="text" id="card_number" name="card_number" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="{{ ctrans('texts.card_number') }}">
                    </div>

                    <div>
                        <label for="card_holders_name" class="block text-sm font-medium text-gray-700">{{ ctrans('texts.name_on_card') }}</label>
                        <input type="text" id="card_holders_name" name="card_holders_name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="{{ ctrans('texts.name_on_card') }}">
                    </div>

                    <div>
                        <label for="expiry" class="block text-sm font-medium text-gray-700">{{ ctrans('texts.expiry_date') }}</label>
                        <input type="text" id="expiry" name="expiry" placeholder="MM/YY" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="cvv" class="block text-sm font-medium text-gray-700">{{ ctrans('texts.cvv') }}</label>
                        <input type="text" id="cvv" name="cvv" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="{{ ctrans('texts.cvv') }}">
                    </div>
                </div>
            </div>
        @endcomponent

        @include('portal.ninja2020.gateways.includes.save_card')
    </div>

    <div class="bg-white px-4 py-5 flex justify-end">
        <button type="button" class="button button-primary bg-primary" id="pay-now-button">{{ ctrans('texts.pay_now') }}</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Elavon's Converge API
            const payNowButton = document.getElementById('pay-now-button');
            const errorElement = document.getElementById('errors');
            const newCardForm = document.getElementById('new-card-form');
            const toggleSaveCard = document.getElementById('toggle-save-card');
            
            // Setup toggle functionality
            const tokenRadios = document.querySelectorAll('.toggle-payment-with-token');
            const newCardRadio = document.getElementById('toggle-payment-with-credit-card');

            function togglePaymentInputs() {
                const useToken = !newCardRadio.checked;
                newCardForm.style.display = useToken ? 'none' : 'block';
            }

            tokenRadios.forEach(radio => {
                radio.addEventListener('change', togglePaymentInputs);
            });

            newCardRadio.addEventListener('change', togglePaymentInputs);
            togglePaymentInputs();
            
            // Function to process the payment
            payNowButton.addEventListener('click', function() {
                // Check if using token or new card
                const selectedTokenRadio = document.querySelector('.toggle-payment-with-token:checked');
                
                if (selectedTokenRadio) {
                    // Using saved card (token)
                    const token = selectedTokenRadio.dataset.token;
                    
                    // Submit the form with token
                    document.querySelector('input[name="token"]').value = token;
                    document.getElementById('server-response').submit();
                    return;
                }
                
                // Using new card
                // Get form values
                const cardNumber = document.getElementById('card_number').value;
                const cardHoldersName = document.getElementById('card_holders_name').value;
                const expiry = document.getElementById('expiry').value;
                const cvv = document.getElementById('cvv').value;
                
                // Basic validation
                if (!cardNumber || !cardHoldersName || !expiry || !cvv) {
                    errorElement.innerText = "{{ ctrans('texts.all_fields_required') }}";
                    errorElement.hidden = false;
                    return;
                }
                
                // Format expiry (MM/YY -> MMYY)
                const expiryFormatted = expiry.replace('/', '');
                
                // Get store card setting
                const storeCard = toggleSaveCard && toggleSaveCard.checked ? 'true' : 'false';
                document.querySelector('input[name="store_card"]').value = storeCard;
                
                // Use the transaction token provided by the controller
                const token = "{{ $transaction_token }}";
                
                // Process the card with the token
                var paymentData = {
                    ssl_txn_auth_token: token,
                    ssl_card_number: cardNumber,
                    ssl_exp_date: expiryFormatted,
                    ssl_cvv2cvc2: cvv,
                    ssl_first_name: cardHoldersName,
                    ssl_get_token: storeCard,
                    ssl_add_token: storeCard
                };
                
                var callback = {
                    onError: function(error) {
                        errorElement.innerText = error;
                        errorElement.hidden = false;
                    },
                    onDeclined: function(response) {
                        errorElement.innerText = response.ssl_result_message || "{{ ctrans('texts.payment_declined') }}";
                        errorElement.hidden = false;
                    },
                    onApproval: function(response) {
                        // Set the form values for submission
                        document.querySelector('input[name="transaction_id"]').value = response.ssl_txn_id;
                        
                        if (storeCard === 'true') {
                            document.querySelector('input[name="token"]').value = response.ssl_token;
                            document.querySelector('input[name="card_brand"]').value = response.ssl_card_type || 'Card';
                            document.querySelector('input[name="last_4"]').value = response.ssl_card_number.slice(-4);
                            
                            // Extract expiry data
                            const expiry = response.ssl_exp_date || expiryFormatted;
                            document.querySelector('input[name="expire_month"]').value = expiry.substring(0, 2);
                            document.querySelector('input[name="expire_year"]').value = expiry.substring(2);
                        }
                        
                        // Submit the form
                        document.getElementById('server-response').submit();
                    }
                };
                
                // Process through Elavon's Checkout.js
                ConvergeEmbeddedPayment.pay(paymentData, callback);
            });
        });
    </script>
@endsection 