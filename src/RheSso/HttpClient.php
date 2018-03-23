<?php

namespace RheSso;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class HttpClient
{
    private $httpClient;

    private $brokerId;
    private $brokerSecret;
    private $baseApiEndpoint;

    /**
     * Constructs an HTTP client used to communicate with the SSO server API.
     *
     * @param $brokerId string Identifier allowing the SSO server to uniquely identify the broker.
     * @param $brokerSecret string Secret used to authenticate the broker with the SSO server.
     * @param $baseApiEndpoint string Base endpoint for the API e.g. https://ENVIRONMENT.sso.rheglobal.com/api.
     */
    public function __construct($brokerId, $brokerSecret, $baseApiEndpoint)
    {
        $this->httpClient = new Client();

        $this->brokerId        = $brokerId;
        $this->brokerSecret    = $brokerSecret;
        $this->baseApiEndpoint = $baseApiEndpoint;
    }

    /**
     * Sends GET requests to the SSO server.
     *
     * @param $path string Path of the API endpoint e.g. /users/2
     * @param $options array Associative array of request data.
     *
     * @return array Associative array containing response data.
     */
    public function get($path, array $options = [])
    {
        return $this->request('get', $path, $options);
    }

    /**
     * Sends POST requests to the SSO server.
     *
     * @param $path string Path of the API endpoint e.g. /users/2
     * @param $options array Associative array of request data.
     *
     * @return array Associative array containing response data.
     */
    public function post($path, array $options = [])
    {
        return $this->request('post', $path, $options);
    }

    /**
     * Sends PUT requests to the SSO server.
     *
     * @param $path string Path of the API endpoint e.g. /users/2
     * @param $options array Associative array of request data.
     *
     * @return array Associative array containing response data.
     */
    public function put($path, array $options = [])
    {
        $options = array_merge_recursive(['form_params' => ['_method' => 'put']], $options);

        return $this->request('post', $path, $options);
    }

    /**
     * Sends an HTTP request to the SSO server including authentication headers.
     *
     * @param $method string HTTP verb to use for the request.
     * @param $path string Path of the API endpoint e.g. /users/2
     * @param $options array Associative array of options.
     *
     * @return array Associative array containing response data.
     */
    private function request($method, $path, array $options = [])
    {
        $endpoint = $this->baseApiEndpoint . $path;

        $options = array_merge_recursive([
            'headers' => [
                'X-Broker-Id'     => $this->brokerId,
                'X-Broker-Secret' => $this->brokerSecret
            ]
        ], $options);

        try {

            $response = $this->httpClient->$method($endpoint, $options);

            return json_decode($response->getBody()->getContents(), true);

        } catch (ClientException $e) {

            if ($e->getResponse()->getStatusCode() === 422) {

                $errors = json_decode($e->getResponse()->getBody()->getContents(), true)['payload'];

                throw new SsoValidationException($errors, $e);
            }

            throw $e;
        }
    }
}