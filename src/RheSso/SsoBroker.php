<?php

namespace RheSso;

use Jasny\SSO\Broker;

class SsoBroker extends Broker
{
    public function __construct($ssoAuthUrl, $brokerId, $brokerSecret, $cookieLifetime = 86400)
    {
        parent::__construct($ssoAuthUrl, $brokerId, $brokerSecret, $cookieLifetime);
    }
}