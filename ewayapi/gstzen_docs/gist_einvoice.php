<?php
include('vendor/rmccue/requests/library/Requests.php');
Requests::register_autoloader();
$headers = array(
    'Content-Type' => 'application/json',
    'Token' => 'de3a3a01-273a-4a81-8b75-13fe37f14dc6'
);
$data = '{ "SellerDtls": {"Gstin": "27AADCG4992P1ZT"} }';
$response = Requests::post('https://my.gstzen.in/~gstzen/a/post-einvoice-data/einvoice-json/', $headers, $data);
