<?php

namespace RheSso;

use Jasny\SSO\Broker;
use Jasny\SSO\Exception;
use Jasny\SSO\NotAttachedException;

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

    /**
     * Execute on SSO server.
     *
     * @param string       $method  HTTP method: 'GET', 'POST', 'DELETE'
     * @param string       $command Command
     * @param array|string $data    Query or post parameters
     * @return array|object
     */
    protected function request($method, $command, $data = null)
    {
        if (!$this->isAttached()) {
            throw new NotAttachedException('No token');
        }
        $url = $this->getRequestUrl($command, !$data || $method === 'POST' ? [] : $data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: Bearer '. $this->getSessionID()]);

        if ($method === 'POST' && !empty($data)) {
            $post = is_string($data) ? $data : http_build_query($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        $response = curl_exec($ch);
        if (curl_errno($ch) != 0) {
            $message = 'Server request failed: ' . curl_error($ch);
            throw new Exception($message);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        list($contentType) = explode(';', curl_getinfo($ch, CURLINFO_CONTENT_TYPE));

        if ($contentType != 'application/json') {
            $message = 'Expected application/json response, got ' . $contentType;
            throw new Exception($message);
        }

        $data = json_decode($response, true);

        if ($httpCode == 403) {
            $this->clearToken();
            throw new NotAttachedException($data['error'] ?: $response, $httpCode);
        }

        if ($httpCode >= 400) throw new SsoAuthException($data['error'] ?: $response, $data['data'] ?: null);

        return $data;
    }

}