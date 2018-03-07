<?php

namespace RheSso;

class ApiClient
{
    private $httpClient;

    /**
     * Constructs a client used to execute functions on the SSO API.
     *
     * @param $brokerId string The identifier allows the SSO server to uniquely identify the broker application.
     * @param $brokerSecret string Secret used to authenticate a trusted broker with the SSO server.
     * @param $baseApiEndpoint string Base endpoint for the API e.g. https://ENVIRONMENT.sso.rheglobal.com/api.
     */
    public function __construct($brokerId, $brokerSecret, $baseApiEndpoint)
    {
        $this->httpClient = new HttpClient($brokerId, $brokerSecret, $baseApiEndpoint);
    }

    /**
     * Checks if a user exists on the SSO.
     *
     * @param $email string Email address of the user to check.
     *
     * @return boolean True if the user exists, false otherwise.
     */
    public function isEmailRegistered($email)
    {
        return $this->httpClient->get('/check-email-exists/' . $email)['payload'];
    }

    /**
     * Creates a user account on the SSO server.
     *
     * @param $userDetails array Parameters used to create the user.
     * @param $options array Options used to control the behaviour of the registration.
     *
     * @return array User details that were registered.
     */
    public function register(array $userDetails, array $options = [])
    {
        $options = ['form_params' => array_merge($userDetails, ['options' => $options])];

        return $this->httpClient->post('/users', $options)['payload'];
    }

    /**
     * Retrieves a user from the SSO by their email address.
     *
     * @param $email string Email address of the user to retrieve.
     *
     * @return array User details associated with the email address.
     */
    public function getUserByEmail($email)
    {
        return $this->httpClient->get('/user-by-email/' . $email)['payload'];
    }

    /**
     * Retrieves a user from the SSO by their email address and password.
     * Useful for authenticating users locally without using shared session tokens.
     *
     * @param $email string Email address of the user to retrieve.
     * @param $password string Plaintext password of the user to retrieve.
     *
     * @return array User details associated with the email address and password.
     */
    public function getUserByCredentials($email, $password)
    {
        $params = ['form_params' => ['email' => $email, 'password' => $password]];

        return $this->httpClient->post('/user-by-credentials', $params)['payload'];
    }

    /**
     * Allows application users to be seeded into the SSO user database.
     * Useful for applications to begin using the SSO with their own user data set.
     * Applications should store a reference to the returned SSO ID for each user seeded.
     *
     * @param $userDetails array Details of the user to be seeded into the SSO user database.
     *
     * @return array Details of the successfully user, including their SSO ID.
     */
    public function seedSsoUser(array $userDetails)
    {
        $params = ['form_params' => $userDetails];

        return $this->httpClient->post('/users/import', $params)['payload'];
    }
}