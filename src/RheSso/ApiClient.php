<?php

namespace RheSso;

class ApiClient
{
    private $httpClient;
    private $serviceId;

    /**
     * Constructs a client used to execute functions on the SSO API.
     *
     * @param $options array An associative array of options.
     *
     * $options = [
     *  'service_id'        => (integer) An identifier allowing the SSO server to uniquely identify the service application.
     *  'broker_id'         => (integer) An identifier allowing the SSO server to uniquely identify the broker application.
     *  'broker_secret'     => (string) Secret used to authenticate a trusted broker with the SSO server.
     *  'base_api_endpoint' => (string) Base endpoint for the API e.g. https://ENVIRONMENT.sso.rheglobal.com/api.
     * ]
     */
    public function __construct($options)
    {
        $options = array_merge([
            'service_id'        => null,
            'broker_id'         => null,
            'broker_secret'     => null,
            'base_api_endpoint' => null
        ], $options);

        $this->httpClient = new HttpClient(
            $options['broker_id'],
            $options['broker_secret'],
            $options['base_api_endpoint']
        );

        $this->serviceId = $options['service_id'];
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
     *
     * @throws SsoValidationException If the request data fails validation.
     */
    public function register(array $userDetails, array $options = [])
    {
        if (isset($userDetails['password']) && trim($userDetails['password']) === '') {

            unset($userDetails['password']);
        }

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
     * @return array Details of the seeded user, including their SSO ID.
     */
    public function seedSsoUser(array $userDetails)
    {
        $params = ['form_params' => $userDetails];

        return $this->httpClient->post('/users/import', $params)['payload'];
    }

    /**
     * Updates an existing SSO user.
     *
     * @param $userId integer ID of the SSO user.
     * @param $userDetails array Associative array of user details to update.
     *
     * @return array Associative array of updated user details.
     *
     * @throws SsoValidationException If the request data fails validation.
     */
    public function updateUser($userId, array $userDetails)
    {
        $params = ['form_params' => $userDetails];

        return $this->httpClient->put('/users/' . $userId, $params)['payload'];
    }

    /**
     * Attaches an SSO user to the service calling the endpoint.
     *
     * @param $userId integer ID of the SSO user.
     *
     * @return array A 200 OK status indicates a successful attachment.
     */
    public function attachUser($userId)
    {
        return $this->httpClient->post('/users/' . $userId . '/attach')['payload'];
    }

    /**
     * Detaches an SSO user from the service calling the endpoint.
     * The SSO user will be deleted if it is their last remaining service.
     *
     * @param $userId integer ID of the SSO user.
     *
     * @return array A 200 OK status indicates a successful detachment.
     */
    public function detachUser($userId)
    {
        return $this->httpClient->delete('/users/' . $userId)['payload'];
    }

    /**
     * Allows a user to sign the terms of service licence.
     *
     * @param $userId integer SSO user ID for the user signing the licence.
     *
     * @return array Empty array, signing was successful if no exception was thrown.
     */
    public function signTermsOfServiceLicence($userId)
    {
        return $this->signLicence($userId, LicenceType::TERMS_OF_SERVICE);
    }

    /**
     * Allows a user to sign the client licence.
     *
     * @param $userId integer SSO user ID for the user signing the licence.
     *
     * @return array Empty array, signing was successful if no exception was thrown.
     */
    public function signClientLicence($userId)
    {
        return $this->signLicence($userId, LicenceType::CLIENT_LICENCE);
    }

    /**
     * Allows a user to sign the EULA licence.
     *
     * @param $userId integer SSO user ID for the user signing the licence.
     *
     * @return array Empty array, signing was successful if no exception was thrown.
     */
    public function signEulaLicence($userId)
    {
        return $this->signLicence($userId, LicenceType::EULA_LICENCE);
    }

    /**
     * Allows a user to sign the contributions licence.
     *
     * @param $userId integer SSO user ID for the user signing the licence.
     *
     * @return array Empty array, signing was successful if no exception was thrown.
     */
    public function signContributionsLicence($userId)
    {
        return $this->signLicence($userId, LicenceType::CONTRIBUTIONS_LICENCE);
    }

    /**
     * Allows a user to sign the reasonable use licence.
     *
     * @param $userId integer SSO user ID for the user signing the licence.
     *
     * @return array Empty array, signing was successful if no exception was thrown.
     */
    public function signReasonableUseLicence($userId)
    {
        return $this->signLicence($userId, LicenceType::REASONABLE_USE_LICENCE);
    }

    /**
     * Allows a user to sign a licence.
     *
     * @param $licenceType string Type of licence to be signed (see possible type values).
     * @param $userId integer ID of the user signing the licence.
     *
     * @return array Empty array, signing was successful if no exception was thrown.
     */
    private function signLicence($userId, $licenceType)
    {
        $params = [
            'form_params' => [
                'user_id'      => $userId,
                'licence_type' => $licenceType
            ]
        ];

        return $this->httpClient->post('/sign-licence', $params)['payload'];
    }
}