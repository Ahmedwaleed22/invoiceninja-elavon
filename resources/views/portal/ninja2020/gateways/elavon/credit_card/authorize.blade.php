@extends('portal.ninja2020.layout.payments', ['gateway_title' => 'Credit card', 'card_title' => 'Credit Card'])

@section('gateway_head')
    <meta name="elavon-api-url" content="{{ $api_url }}">
    <script src="https://api.demo.convergepay.com/hosted-payments/Checkout.js"></script>
@endsection

@section('gateway_content')
    <form action="{{ route('client.payment_methods.store', ['method' => GatewayType::CREDIT_CARD]) }}" method="post" id="server-response">
        @csrf
        <input type="hidden" name="company_gateway_id" value="{{ $company_gateway->id }}">
        <input type="hidden" name="payment_method_id" value="{{ GatewayType::CREDIT_CARD }}">
        <input type="hidden" name="token">
        <input type="hidden" name="card_brand">
        <input type="hidden" name="last_4">
        <input type="hidden" name="expire_month">
        <input type="hidden" name="expire_year">
    </form>

    <div class="alert alert-failure mb-4" hidden id="errors"></div>

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

    <div class="bg-white px-4 py-5 flex justify-end">
        <button type="button" class="button button-primary bg-primary" id="add-card-button">{{ ctrans('texts.add_payment_method') }}</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Elavon's Converge API
            // First we need to get a transaction token
            const apiUrl = document.querySelector('meta[name="elavon-api-url"]').content;
            const addCardButton = document.getElementById('add-card-button');
            const errorElement = document.getElementById('errors');
            
            // Function to process the payment and get a token
            addCardButton.addEventListener('click', function() {
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
                
                // Prepare data for the token generation
                var tokenRequest = {
                    ssl_amount: "1.00" // Dummy amount for token generation
                };
                
                // First get a transaction token from the server
                fetch('/client/payment_methods/get_elavon_token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(tokenRequest)
                })
                .then(response => response.text())
                .then(token => {
                    // Process the card with the token
                    var paymentData = {
                        ssl_txn_auth_token: token,
                        ssl_card_number: cardNumber,
                        ssl_exp_date: expiryFormatted,
                        ssl_cvv2cvc2: cvv,
                        ssl_first_name: cardHoldersName,
                        ssl_get_token: "Y",
                        ssl_add_token: "Y"
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
                            document.querySelector('input[name="token"]').value = response.ssl_token;
                            document.querySelector('input[name="card_brand"]').value = response.ssl_card_type || 'Card';
                            document.querySelector('input[name="last_4"]').value = response.ssl_card_number.slice(-4);
                            
                            // Extract expiry data
                            const expiry = response.ssl_exp_date || expiry;
                            document.querySelector('input[name="expire_month"]').value = expiry.substring(0, 2);
                            document.querySelector('input[name="expire_year"]').value = expiry.substring(2);
                            
                            // Submit the form
                            document.getElementById('server-response').submit();
                        }
                    };
                    
                    // Process through Elavon's Checkout.js
                    ConvergeEmbeddedPayment.pay(paymentData, callback);
                })
                .catch(error => {
                    errorElement.innerText = "{{ ctrans('texts.error_processing_payment') }}";
                    errorElement.hidden = false;
                    console.error('Error:', error);
                });
            });
        });
    </script>
@endsection 