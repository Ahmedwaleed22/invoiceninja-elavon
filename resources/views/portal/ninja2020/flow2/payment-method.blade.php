{{-- <div class="flex flex-col p-4 rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden px-4 py-5 bg-white sm:gap-4 sm:px-6"
    x-data="{ isLoading: @entangle('isLoading') }">

    <p class="font-semibold tracking-tight group flex items-center gap-2 text-lg">{{ ctrans('texts.payment_methods') }}
    </p>

    <svg id="spinner" wire:loading class="animate-spin h-5 w-5 text-primary"
        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
        </path>
    </svg>

    @unless ($isLoading)
        <div class="my-3 flex flex-col space-y-3">
            @foreach ($methods as $index => $method)
                <button wire:loading.remove
                    class="flex px-4 py-3 border rounded-lg lg:-mb-1 hover:shadow-sm transition duration-300"
                    wire:click="handleSelect('{{ $method['company_gateway_id'] }}', '{{ $method['gateway_type_id'] }}', '{{ $amount }}')">
                    <span>{{ $method['label'] }}</span>
                </button>
            @endforeach
        </div>
    @endunless 

    @script
    <script>
        Livewire.on('loadingCompleted', () => {
            isLoading = false;
        });

        Livewire.on('singlePaymentMethodFound', (event) => {
            $wire.dispatch('payment-method-selected', { company_gateway_id: event.company_gateway_id, gateway_type_id: event.gateway_type_id, amount: event.amount })
        });

        const buttons = document.querySelectorAll('.payment-method');

        buttons.forEach(button => {
            button.addEventListener('click', (event) => {
                // Hide all buttons except the clicked one
                buttons.forEach(btn => {
                    if (btn !== event.currentTarget) {
                        btn.style.display = 'none';
                    } else {
                        // Disable the clicked button
                        btn.disabled = true;

                        // Show the spinner by removing the 'hidden' class
                        const spinner = btn.querySelector('svg');
                        if (spinner) {
                            spinner.classList.remove('hidden');
                        }

                        const span = btn.querySelector('span');
                        if (span) {
                            span.style.display = 'none';
                        }
                    }
                });
            });
        });
    </script>
    @endscript
</div> --}}

<div class="flex flex-col p-4 rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden px-4 py-5 bg-white sm:gap-4 sm:px-6"
    x-data="{ isLoading: @entangle('isLoading') }">

    <script src="https://api.demo.convergepay.com/hosted-payments/Checkout.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script>
        function initiateCheckoutJS() {
            var tokenRequest = {
                ssl_amount: document.getElementById('ssl_amount').value
            };
            $.post("RequestSample.php", tokenRequest, function(data) {
                document.getElementById('token').value = data;
                transactionToken = data;
            });
            return false;
        }

        function pay() {
            var token = document.getElementById('token').value;
            var card = document.getElementById('card').value;
            var exp = document.getElementById('exp').value;
            var cvv = document.getElementById('cvv').value;
            var country = document.getElementById('country').value;
            var email = document.getElementById('email').value;
            var gettoken = document.getElementById('gettoken').value;
            var addtoken = document.getElementById('addtoken').value;
            var invoice = document.getElementById('invoice').value;
            var firstname = document.getElementById('name').value;
            var lastname = document.getElementById('lastname').value;
            var address1 = document.getElementById('address1').value;
            var address2 = document.getElementById('address2').value;
            var city = document.getElementById('city').value;
            var state = document.getElementById('state').value;
            var zip = document.getElementById('zip').value;
            var paymentData = {
                ssl_txn_auth_token: token,
                ssl_card_number: card,
                ssl_exp_date: exp,
                ssl_get_token: gettoken,
                ssl_add_token: addtoken,
                ssl_invoice_number: invoice,
                ssl_first_name: firstname,
                ssl_last_name: lastname,
                ssl_cvv2cvc2: cvv,
                ssl_avs_address: address1,
                ssl_address2: address2,
                ssl_city: city,
                ssl_state: state,
                ssl_avs_zip: zip,
                Monthly_Recurring: "N",
                ssl_country: country,
                ssl_email: email,
                ssl_get_token: "Y",
                ssl_add_token: "Y"
            };
            var callback = {
                onError: function(error) {
                    showResult("error", error);
                },
                onDeclined: function(response) {
                    console.log("Result Message=" + response['ssl_result_message']);
                    showResult("declined", JSON.stringify(response));
                },
                onApproval: function(response) {
                    console.log("Approval Code=" + response['ssl_approval_code']);
                    showResult("approval", JSON.stringify(response));
                }
            };
            ConvergeEmbeddedPayment.pay(paymentData, callback);
            return false;
        }

        function showResult(status, msg) {
            document.getElementById('txn_status').innerHTML = "<b>" + status + "</b>";
            document.getElementById('txn_response').innerHTML = msg;
        }
    </script>

    <p class="font-semibold tracking-tight group flex items-center gap-2 text-lg">{{ ctrans('texts.payment_methods') }}
    </p>

    <svg id="spinner" wire:loading class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg"
        fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
        </path>
    </svg>

    @unless ($isLoading)
        <div class="space-y-6">
            <form onsubmit="return pay();" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Customer Information Section -->
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">{{ ctrans('texts.customer_information') }}</h3>
                </div>

                <div class="flex w-full col-span-2">
                    <div class="flex-1 mr-2">
                        <label for="name"
                            class="block text-sm font-medium text-gray-700 mb-1">{{ ctrans('texts.first_name') }}</label>
                        <input value="{{ auth()->user()->first_name }}" type="text" id="name" name="ssl_first_name"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
                    </div>

                    <div class="flex-1">
                        <label for="lastname"
                            class="block text-sm font-medium text-gray-700 mb-1">{{ ctrans('texts.last_name') }}</label>
                        <input value="{{ auth()->user()->last_name }}" type="text" id="lastname" name="ssl_last_name"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label for="email"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ ctrans('texts.email') }}</label>
                    <input value="{{ auth()->user()->email }}" type="email" id="email" name="ssl_email"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <!-- Billing Address Section -->
                <div class="col-span-1 md:col-span-2 mt-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">{{ ctrans('texts.billing_address') }}</h3>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label for="address1"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ ctrans('texts.address1') }}</label>
                    <input type="text" id="address1" name="ssl_avs_address"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label for="address2"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ ctrans('texts.address2') }}</label>
                    <input type="text" id="address2" name="ssl_address2"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <div class="col-span-1">
                    <label for="city"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ ctrans('texts.city') }}</label>
                    <input type="text" id="city" name="ssl_city" value="Atlanta"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <div class="col-span-1">
                    <label for="state"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ ctrans('texts.state') }}</label>
                    <input type="text" id="state" name="ssl_state" value="GA"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <div class="col-span-1">
                    <label for="country"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ ctrans('texts.country') }}</label>
                    <input type="text" id="country" name="ssl_country"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <div class="col-span-1">
                    <label for="zip"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ ctrans('texts.zip') }}</label>
                    <input type="text" id="zip" name="ssl_avs_zip" value="30003"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <input type="hidden" id="ssl_amount" name="ssl_amount" value="{{ $amount }}">

                <!-- Hidden fields -->
                <input id="token" type="hidden" name="token" value="<?php echo $elavon_session_token; ?>">

                <!-- Payment Details Section -->
                <div class="col-span-1 md:col-span-2 mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">{{ ctrans('texts.card_details') }}</h3>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label for="card"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ ctrans('texts.card_number') }}</label>
                    <input id="card" type="text" name="card" value="4124939999999990"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary" />
                </div>

                <div class="col-span-1">
                    <label for="exp"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ ctrans('texts.expiry_date') }}</label>
                    <input id="exp" type="text" name="exp" value="1230"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary"
                        placeholder="MMYY">
                </div>

                <div class="col-span-1">
                    <label for="cvv"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ ctrans('texts.cvv') }}</label>
                    <input id="cvv" type="text" name="cvv" value="123"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <!-- Hidden fields -->
                <input id="gettoken" type="hidden" name="gettoken" value="y">
                <input id="addtoken" type="hidden" name="addtoken" value="y">
                <input id="invoice" type="hidden" name="invoice" value="INV123">

                <div class="col-span-1 md:col-span-2 mt-4">
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        {{ ctrans('texts.process_payment') }}
                    </button>
                </div>

                <!-- Transaction Status Section -->
                <div class="col-span-1 md:col-span-2 mt-8 pt-6 border-t border-gray-200">
                    <div class="mb-2">
                        <span class="text-sm font-medium text-gray-700">{{ ctrans('texts.transaction_status') }}:</span>
                        <div id="txn_status" class="mt-1 text-sm text-gray-900 font-semibold"></div>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-700">{{ ctrans('texts.transaction_response') }}:</span>
                        <div id="txn_response"
                            class="mt-1 text-sm text-gray-900 overflow-auto max-h-40 border border-gray-200 rounded-md p-2 bg-gray-50">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    @endunless

    @script
        <script>
            Livewire.on('loadingCompleted', () => {
                isLoading = false;
            });

            Livewire.on('singlePaymentMethodFound', (event) => {
                $wire.dispatch('payment-method-selected', {
                    company_gateway_id: event.company_gateway_id,
                    gateway_type_id: event.gateway_type_id,
                    amount: event.amount
                })
            });

            const buttons = document.querySelectorAll('.payment-method');

            buttons.forEach(button => {
                button.addEventListener('click', (event) => {
                    // Hide all buttons except the clicked one
                    buttons.forEach(btn => {
                        if (btn !== event.currentTarget) {
                            btn.style.display = 'none';
                        } else {
                            // Disable the clicked button
                            btn.disabled = true;

                            // Show the spinner by removing the 'hidden' class
                            const spinner = btn.querySelector('svg');
                            if (spinner) {
                                spinner.classList.remove('hidden');
                            }

                            const span = btn.querySelector('span');
                            if (span) {
                                span.style.display = 'none';
                            }
                        }
                    });
                });
            });
        </script>
    @endscript
</div>
