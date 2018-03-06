<?php

namespace RheSso;

use GuzzleHttp\Client;

class HttpClient
{
    private $httpClient;

    private $brokerId;
    private $brokerSecret;
    private $baseApiEndpoint;

    public function __construct($brokerId, $brokerSecret, $baseApiEndpoint)
    {
        $this->httpClient = new Client();

        $this->brokerId        = $brokerId;
        $this->brokerSecret    = $brokerSecret;
        $this->baseApiEndpoint = $baseApiEndpoint;
    }

    public function get($path, array $options = [])
    {
        return $this->request('get', $path, $options);
    }

    public function post($path, array $options = [])
    {
        return $this->request('post', $path, $options);
    }

    public function request($method, $path, array $options = [])
    {
        $endpoint = $this->baseApiEndpoint . $path;

        $options = array_merge_recursive([
            'headers' => [
                'X-Broker-Id'     => $this->brokerId,
                'X-Broker-Secret' => $this->brokerSecret
            ]
        ], $options);

        $response = $this->httpClient->$method($endpoint, $options);

        return json_decode($response->getBody()->getContents(), true);
    }
}