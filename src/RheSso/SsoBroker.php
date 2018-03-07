<?php

namespace RheSso;

use Jasny\SSO\Broker;

class SsoBroker extends Broker
{
    /**
     * Constructs a new SSO broker used to authenticate with the RHE SSO server.
     *
     * @param $ssoAuthUrl string Endpoint providing SSO authentication e.g. https://ENVIRONMENT.sso.rheglobal.com/auth.
     * @param $brokerId string The identifier allows the SSO server to uniquely identify the broker application.
     * @param $brokerSecret string Secret used to authenticate a trusted broker with the SSO server.
     * @param $cookieLifetime integer Seconds until SSO session expires and user needs to re-authenticate.
     */
    public function __construct($ssoAuthUrl, $brokerId, $brokerSecret, $cookieLifetime = 86400)
    {
        parent::__construct($ssoAuthUrl, $brokerId, $brokerSecret, $cookieLifetime);
    }
}