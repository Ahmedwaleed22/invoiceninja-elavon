<?php

/**
 * Lightweight Elavon connection test script
 * This doesn't load the entire Laravel framework and is more memory efficient
 */

// Basic error handling
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// // Elavon Demo credentials
// $merchant_id = '0023257';
// $user_id = 'apiuser';
// $pin = 'FFAX0H1596OQZ9JPCCPRCZ85H1T6ZYLMIX850ZDB7JDF8KF49I212KPWG44ZGMSR'; // Replace with your PIN if needed

// // Initialize cURL session
// $ch = curl_init('https://api.demo.convergepay.com/hosted-payments');

// // Prepare form data
// $post_data = [
//     'ssl_transaction_type' => 'ccsale',
//     'ssl_merchant_id' => $merchant_id,
//     'ssl_user_id' => $user_id,
//     'ssl_pin' => $pin,
//     'ssl_card_number' => '4111111111111111',
//     'ssl_exp_date' => '1230',
//     'ssl_amount' => '1.00',
//     'ssl_cvv2cvc2' => '123',
//     'ssl_avs_address' => '123 Test St',
//     'ssl_avs_zip' => '12345',
//     'ssl_invoice_number' => time(),
//     'ssl_description' => 'Test Transaction',
//     'ssl_first_name' => 'Test',
//     'ssl_last_name' => 'User',
//     'ssl_result_format' => 'HTML',
//     'ssl_test_mode' => 'TRUE',
//     'ssl_error_url' => 'http://localhost:9000/test_elavon.php',
//     'ssl_receipt_link_url' => 'http://localhost:9000/test_elavon.php'
// ];

// // Set cURL options
// curl_setopt($ch, CURLOPT_POST, 1);
// curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_HTTPHEADER, [
//     'Content-Type: application/x-www-form-urlencoded'
// ]);
// curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
// curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1.2');
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

// // Execute the request
// $response = curl_exec($ch);
// $curl_info = curl_getinfo($ch);
// $curl_error = curl_error($ch);

// // Close cURL session
// curl_close($ch);

// // Start output
// echo "<html><body style='font-family: monospace;'>";
// echo "<h1>Elavon Connection Test</h1>";

// // For debugging, let's see what we're sending
// echo "<h2>Request Data:</h2>";
// echo "<pre>" . print_r($post_data, true) . "</pre>";

// // Check if the request was successful
// if ($response === false) {
//     echo "<div style='color: red;'><strong>ERROR:</strong> cURL Error: " . htmlspecialchars($curl_error) . "</div>";
//     exit;
// }

// // Try to parse as JSON first
// $json_response = json_decode($response);
// $is_json = json_last_error() === JSON_ERROR_NONE;

// // Display the response
// if ($is_json) {
//     if (isset($json_response->ssl_token)) {
//         echo "<div style='color: green; font-weight: bold;'>CONNECTION SUCCESSFUL!</div><br>";
//         echo "<strong>Response Details:</strong><br>";
//         echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
//         echo "<tr><th>Field</th><th>Value</th></tr>";

//         foreach ($json_response as $key => $value) {
//             echo "<tr><td>" . htmlspecialchars($key) . "</td><td>" . htmlspecialchars($value) . "</td></tr>";
//         }

//         echo "</table>";

//         echo "<br><div style='color: green; font-weight: bold;'>✓ Your Elavon Converge API connection is working correctly!</div>";
//     } else {
//         echo "<div style='color: red;'><strong>API REQUEST FAILED</strong></div><br>";
//         echo "Error details:<br>";
//         echo "<pre>" . htmlspecialchars(json_encode($json_response, JSON_PRETTY_PRINT)) . "</pre>";
//     }
// } else {
//     // Try XML parsing as fallback
//     $xml_response = @simplexml_load_string($response);

//     if ($xml_response) {
//         if (isset($xml_response->ssl_result) && $xml_response->ssl_result == 0) {
//             echo "<div style='color: green; font-weight: bold;'>CONNECTION SUCCESSFUL!</div><br>";
//             echo "<strong>Response Details:</strong><br>";
//             echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
//             echo "<tr><th>Field</th><th>Value</th></tr>";
//             echo "<tr><td>Result</td><td>" . htmlspecialchars($xml_response->ssl_result) . "</td></tr>";
//             echo "<tr><td>Message</td><td>" . htmlspecialchars($xml_response->ssl_result_message) . "</td></tr>";
//             echo "<tr><td>Transaction ID</td><td>" . htmlspecialchars($xml_response->ssl_txn_id) . "</td></tr>";
//             echo "<tr><td>Auth Code</td><td>" . htmlspecialchars($xml_response->ssl_approval_code) . "</td></tr>";
//             echo "</table>";

//             echo "<br><div style='color: green; font-weight: bold;'>✓ Your Elavon Converge API connection is working correctly!</div>";
//         } else {
//             echo "<div style='color: red;'><strong>API REQUEST FAILED</strong></div><br>";
//             if (isset($xml_response->errorCode)) {
//                 echo "Error code: " . htmlspecialchars($xml_response->errorCode) . "<br>";
//                 echo "Error name: " . htmlspecialchars($xml_response->errorName) . "<br>";
//                 echo "Error message: " . htmlspecialchars($xml_response->errorMessage) . "<br>";
//             } else if (isset($xml_response->ssl_result)) {
//                 echo "Error code: " . htmlspecialchars($xml_response->ssl_result) . "<br>";
//                 echo "Error message: " . htmlspecialchars($xml_response->ssl_result_message) . "<br>";
//             } else {
//                 echo "Unexpected response format:<br>";
//                 echo "<pre>" . htmlspecialchars($response) . "</pre>";
//             }
//         }
//     } else {
//         echo "<div style='color: orange;'><strong>RAW RESPONSE:</strong> (Not XML or JSON)</div>";

//         // Check if we have a successful transaction number in the HTML response
//         if (strpos($response, 'APPROVED') !== false || strpos($response, 'Transaction Successful') !== false) {
//             echo "<div style='color: green; font-weight: bold;'>CONNECTION SUCCESSFUL!</div><br>";
//             echo "<div>The response appears to contain an approval message.</div>";
//         } else {
//             echo "<div style='color: red;'><strong>API REQUEST FAILED OR RETURNED NON-STANDARD FORMAT</strong></div><br>";
//         }

//         // Just output the raw response
//         echo "<pre style='max-height: 400px; overflow: auto;'>" . htmlspecialchars($response) . "</pre>";
//     }
// }

// // HTTP request details for debugging
// echo "<br><h2>HTTP Request Details:</h2>";
// echo "<pre>";
// echo "Status Code: " . $curl_info['http_code'] . "\n";
// echo "Content Type: " . $curl_info['content_type'] . "\n";
// echo "Total Time: " . $curl_info['total_time'] . " seconds\n";
// echo "</pre>";

// echo "</body></html>"; 

$merchantID = "0023257"; //Virtual Merchant Account ID
$merchantUserID = "apiuser"; //Virtual Merchant  User ID
$merchantPinCode = "FFAX0H1596OQZ9JPCCPRCZ85H1T6ZYLMIX850ZDB7JDF8KF49I212KPWG44ZGMSR"; //Converge PIN
$vendorID = "0023257"; //Vendor ID

$url = "https://api.demo.convergepay.com/hosted-payments/transaction_token"; // URL to Converge demo session token server
//$url = "https://api.convergepay.com/hosted-payments/transaction_token"; // URL to Converge production session token server

// Read the following querystring variables

$amount = '1.00'; //Post Tran Amount


$ch = curl_init();    // initialize curl handle
curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
curl_setopt($ch, CURLOPT_POST, true); // set POST method
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Set up the post fields. If you want to add custom fields, you would add them in Converge, and add the field name in the curlopt_postfields string.
curl_setopt(
  $ch,
  CURLOPT_POSTFIELDS,
  "ssl_merchant_id=$merchantID" .
    "&ssl_user_id=$merchantUserID" .
    "&ssl_pin=$merchantPinCode" .
    "&ssl_vendor_id=$vendorID" .
    // "&ssl_first_name=Samuel". //You can pass in values from your application and they will appear and pre-populate the HPP form
    // "&ssl_avs_address=7301 Chapman Hwy". //You can pass in values from your application and they will appear and pre-populate the HPP form
    // "&ssl_avs_zip=37920". //You can pass in values from your application and they will appear and pre-populate the HPP form
    "&ssl_invoice_number=Inv123" .
    //"&ssl_next_payment_date=03/03/2023". //used only if transaction type is ccrecurring
    //"&ssl_billing_cycle=MONTHLY".  //used only if transaction type is ccrecurring
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

?>

<!DOCTYPE html>
<html>

<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <title>Checkout.js Credit Card Demo</title>
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
</head>

<body>
  <br>
  First Name: <input type="text" id="name" name="ssl_first_name" size="25"> <br>
  Last Name: <input type="text" id="lastname" name="ssl_last_name" size="25"> <br>
  Email: <input type="text" id="email" name="ssl_email" size="25"> <br>
  Address 1: <input type="text" id="address1" name="ssl_avs_address" size="30"> <br>
  Address 2: <input type="text" id="address2" name="ssl_address2" size="30"> <br>
  City: <input type="text" id="city" name="ssl_city" size="30" value="Atlanta"> <br>
  State: <input type="text" id="state" name="ssl_state" size="2" value="GA"> <br>
  Country: <input type="text" id="country" name="ssl_country"> <br>
  Zip: <input type="text" id="zip" name="ssl_avs_zip" size="25" value="30003"> <br><br>
  Transaction Amount: <input type="text" id="ssl_amount" name="ssl_amount" value="25.00"> <br>
  <button onclick="return initiateCheckoutJS();">Click to Confirm Order</button> <br>
  <br>
  <br>
  <br>
  <input id="token" type="hidden" name="token" value="<?php echo $result; ?>"> <br>
  Card Number: <input id="card" type="text" name="card" value="4124939999999990" /> <br>
  Expiry Date: <input id="exp" type="text" name="exp" value="1230"> <br>
  CVV2: <input id="cvv" type="text" name="cvv" value="123"> <br>
  <input id="gettoken" type="hidden" name="gettoken" value="y">
  <input id="addtoken" type="hidden" name="addtoken" value="y">
  <input id="invoice" type="hidden" name="invoice" value="INV123">
  <button onclick="return pay();">Process Payment</button>
  <br>
  <br>
  <br>
  Transaction Status:<div id="txn_status"></div>
  <br>
  Transaction Response:<div id="txn_response"></div>
</body>

</html>