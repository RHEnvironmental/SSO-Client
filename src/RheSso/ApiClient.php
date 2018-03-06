<?php

namespace RheSso;

class ApiClient
{
    private $httpClient;

    public function __construct($brokerId, $brokerSecret, $baseApiEndpoint)
    {
        $this->httpClient = new HttpClient($brokerId, $brokerSecret, $baseApiEndpoint);
    }

    public function isEmailRegistered($email)
    {
        return $this->httpClient->get('/check-email-exists/' . $email)['payload'];
    }

    public function register(array $userDetails, array $options = [])
    {
        $options = ['form_params' => array_merge($userDetails, ['options' => $options])];

        return $this->httpClient->post('/users', $options)['payload'];
    }

    public function getUserByEmail($email)
    {
        return $this->httpClient->get('/user-by-email/' . $email)['payload'];
    }

    public function getUserByCredentials($email, $password)
    {
        $params = ['form_params' => ['email' => $email, 'password' => $password]];

        return $this->httpClient->post('/user-by-credentials', $params)['payload'];
    }

    public function seedSsoUser(array $userDetails)
    {
        $params = ['form_params' => $userDetails];

        return $this->httpClient->post('/users/import', $params)['payload'];
    }
}