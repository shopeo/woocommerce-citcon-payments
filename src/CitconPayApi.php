<?php

namespace Shopeo\WoocommerceCitconPayments;

use GuzzleHttp\Client;

class CitconPayApi
{
    private $merchant_key;
    private $url;
    private $client;
    private $debug;

    public function __construct($merchant_key, $debug = false)
    {
        $this->merchant_key = $merchant_key;
        $this->debug = $debug;
        $this->url = $debug ? 'https://api.sandbox.citconpay.com' : 'https://api.citconpay.com';
        $this->client = new Client([
            'base_uri' => $this->url,
            'timeout' => 2.0,
        ]);
    }

    private function accessToken()
    {
        $response = $this->client->post('/v1/access-tokens', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $this->merchant_key
            ],
            'body' => json_encode([
                'token_type' => 'server'
            ]),
        ]);
        $body = $response->getBody();
        $data = json_decode($body->getContents(), true);
        return $data['data']['access_token'];
    }

    public function charge($reference, $amount, $currency, $country, $billing_address, $method, $ipn_url, $success_url, $fail_url, $goods)
    {
        $body = [
            'transaction' => [
                'reference' => strval($reference),
                'amount' => $amount,
                'currency' => $currency,
                'country' => $country,
                "auto_capture" => true
            ],
            'payment' => [
                'method' => $method,
                'request_token' => false,
                'billing_address' => $billing_address,
                '3ds' => [
                    'mode' => 'always'
                ]
            ],
            'consumer' => [
                'reference' => strval($reference),
            ],
            'urls' => [
                'ipn' => $ipn_url,
                'success' => $success_url,
                'fail' => $fail_url
            ],
            'goods' => $goods
        ];
        error_log(json_encode($body));
        $access_token = $this->accessToken();
        $response = $this->client->post('/v1/charges', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token
            ],
            'body' => json_encode($body),
        ]);
        $body = $response->getBody();
        $content = $body->getContents();
        $data = json_decode($content, true);
        error_log($content);
        error_log(print_r($data, true));
        return $data;
    }
}