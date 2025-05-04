<?php

/**
 * Lightweight Elavon connection test script
 * This doesn't load the entire Laravel framework and is more memory efficient
 */

// Basic error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Elavon Demo credentials
$merchant_id = '0023257';
$user_id = 'apiuser';
$pin = 'FFAX0H1596OQZ9JPCCPRCZ85H1T6ZYLMIX850ZDB7JDF8KF49I212KPWG44ZGMSR'; // Replace with your PIN if needed

// Initialize cURL session
$ch = curl_init('https://api.demo.convergepay.com/VirtualMerchantDemo/processxml.do');

// Create XML request - note the use of ssl_account_id instead of ssl_merchant_id
$xml_request = "<txn>
  <ssl_transaction_type>ccsale</ssl_transaction_type>
  <ssl_account_id>{$merchant_id}</ssl_account_id>
  <ssl_user_id>{$user_id}</ssl_user_id>
  <ssl_pin>{$pin}</ssl_pin>
  <ssl_token>4459079401220002</ssl_token>
  <ssl_exp_date>1228</ssl_exp_date>
  <ssl_ps2000_data>W7551005971889572232A</ssl_ps2000_data>
  <ssl_approval_code>489529</ssl_approval_code>
  <ssl_entry_mode>12</ssl_entry_mode>
  <ssl_merchant_initiated_unscheduled>Y</ssl_merchant_initiated_unscheduled>
  <ssl_partial_auth_indicator>0</ssl_partial_auth_indicator>
  <ssl_amount>1.00</ssl_amount>
  <ssl_first_name>Kay</ssl_first_name>
  <ssl_last_name>Barton</ssl_last_name>
  <ssl_avs_address>41 East Nobel Extension</ssl_avs_address>
  <ssl_state>NY</ssl_state>
  <ssl_avs_zip>42355</ssl_avs_zip>
  <ssl_country>USA</ssl_country>
  <ssl_email>mimexepur@mailinator.com</ssl_email>
  <ssl_invoice_number>INV129</ssl_invoice_number>
  <Monthly_Recurring>N</Monthly_Recurring>
</txn>";

// Set cURL options
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'xmldata=' . $xml_request);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1.2');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

// Execute the request
$response = curl_exec($ch);
$curl_info = curl_getinfo($ch);
$curl_error = curl_error($ch);

// Close cURL session
curl_close($ch);

// Start output
echo "<html><body style='font-family: monospace;'>";
echo "<h1>Elavon Connection Test</h1>";

// For debugging, let's see what we're sending
echo "<h2>XML Request Data:</h2>";
echo "<pre>" . htmlspecialchars($xml_request) . "</pre>";

// Check if the request was successful
if ($response === false) {
    echo "<div style='color: red;'><strong>ERROR:</strong> cURL Error: " . htmlspecialchars($curl_error) . "</div>";
    exit;
}

// Try to parse as JSON first
$json_response = json_decode($response);
$is_json = json_last_error() === JSON_ERROR_NONE;

// Display the response
if ($is_json) {
    if (isset($json_response->ssl_token)) {
        echo "<div style='color: green; font-weight: bold;'>CONNECTION SUCCESSFUL!</div><br>";
        echo "<strong>Response Details:</strong><br>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";

        foreach ($json_response as $key => $value) {
            echo "<tr><td>" . htmlspecialchars($key) . "</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }

        echo "</table>";

        echo "<br><div style='color: green; font-weight: bold;'>✓ Your Elavon Converge API connection is working correctly!</div>";
    } else {
        echo "<div style='color: red;'><strong>API REQUEST FAILED</strong></div><br>";
        echo "Error details:<br>";
        echo "<pre>" . htmlspecialchars(json_encode($json_response, JSON_PRETTY_PRINT)) . "</pre>";
    }
} else {
    // Try XML parsing as fallback
    $xml_response = @simplexml_load_string($response);

    if ($xml_response) {
        if (isset($xml_response->ssl_result) && $xml_response->ssl_result == 0) {
            echo "<div style='color: green; font-weight: bold;'>CONNECTION SUCCESSFUL!</div><br>";
            echo "<strong>Response Details:</strong><br>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>Result</td><td>" . htmlspecialchars($xml_response->ssl_result) . "</td></tr>";
            echo "<tr><td>Message</td><td>" . htmlspecialchars($xml_response->ssl_result_message) . "</td></tr>";
            echo "<tr><td>Transaction ID</td><td>" . htmlspecialchars($xml_response->ssl_txn_id) . "</td></tr>";
            echo "<tr><td>Auth Code</td><td>" . htmlspecialchars($xml_response->ssl_approval_code) . "</td></tr>";
            echo "</table>";

            echo "<br><div style='color: green; font-weight: bold;'>✓ Your Elavon Converge API connection is working correctly!</div>";
        } else {
            echo "<div style='color: red;'><strong>API REQUEST FAILED</strong></div><br>";
            if (isset($xml_response->errorCode)) {
                echo "Error code: " . htmlspecialchars($xml_response->errorCode) . "<br>";
                echo "Error name: " . htmlspecialchars($xml_response->errorName) . "<br>";
                echo "Error message: " . htmlspecialchars($xml_response->errorMessage) . "<br>";
            } else if (isset($xml_response->ssl_result)) {
                echo "Error code: " . htmlspecialchars($xml_response->ssl_result) . "<br>";
                echo "Error message: " . htmlspecialchars($xml_response->ssl_result_message) . "<br>";
            } else {
                echo "Unexpected response format:<br>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        }
    } else {
        echo "<div style='color: orange;'><strong>RAW RESPONSE:</strong> (Not XML or JSON)</div>";

        // Check if we have a successful transaction number in the HTML response
        if (strpos($response, 'APPROVED') !== false || strpos($response, 'Transaction Successful') !== false) {
            echo "<div style='color: green; font-weight: bold;'>CONNECTION SUCCESSFUL!</div><br>";
            echo "<div>The response appears to contain an approval message.</div>";
        } else {
            echo "<div style='color: red;'><strong>API REQUEST FAILED OR RETURNED NON-STANDARD FORMAT</strong></div><br>";
        }

        // Just output the raw response
        echo "<pre style='max-height: 400px; overflow: auto;'>" . htmlspecialchars($response) . "</pre>";
    }
}

// HTTP request details for debugging
echo "<br><h2>HTTP Request Details:</h2>";
echo "<pre>";
echo "Status Code: " . $curl_info['http_code'] . "\n";
echo "Content Type: " . $curl_info['content_type'] . "\n";
echo "Total Time: " . $curl_info['total_time'] . " seconds\n";
echo "</pre>";

echo "</body></html>"; 