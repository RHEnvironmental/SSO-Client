<?php

namespace RheSso;

use Jasny\SSO\Broker;
use Jasny\SSO\Exception;
use Jasny\SSO\NotAttachedException;

class SsoBroker extends Broker
{
    private $useSecureCookies;

    /**
     * Constructs a new SSO broker used to authenticate with the RHE SSO server.
     *
     * @param $ssoAuthUrl string Endpoint providing SSO authentication e.g. https://ENVIRONMENT.sso.rheglobal.com/auth.
     * @param $brokerId string The identifier allows the SSO server to uniquely identify the broker application.
     * @param $brokerSecret string Secret used to authenticate a trusted broker with the SSO server.
     * @param $cookieLifetime integer Seconds until SSO session expires and user needs to re-authenticate.
     * @param $useSecureCookies boolean Force cookie transmission over an HTTPS connection.
     */
    public function __construct($ssoAuthUrl, $brokerId, $brokerSecret, $cookieLifetime = 86400, $useSecureCookies = false)
    {
        parent::__construct($ssoAuthUrl, $brokerId, $brokerSecret, $cookieLifetime);
        $this->useSecureCookies = $useSecureCookies;
    }

    /**
     * Generate session token
     */
    public function generateToken()
    {
        if (isset($this->token)) return;

        $this->token = base_convert(md5(uniqid(rand(), true)), 16, 36);

        $cookieExpiry = time() + $this->cookie_lifetime;
        setcookie($this->getCookieName(), $this->token, $cookieExpiry, '/', '', $this->useSecureCookies, true);
        setcookie("sso_attached", '1', $cookieExpiry, '/', '', $this->useSecureCookies, false);
    }

    /**
     * Clears session token
     */
    public function clearToken()
    {
        setcookie($this->getCookieName(), null, 1, '/', '', $this->useSecureCookies, true);
        setcookie("sso_attached", '0', 1, '/', '', $this->useSecureCookies, false);
        $this->token = null;
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

        if ($httpCode >= 400) throw new SsoAuthException($data['error'] ?: $response, $data['data'] ?: []);

        return $data;
    }

    /**
     * Log the client in at the SSO server.
     *
     * Only brokers marked trused can collect and send the user's credentials. Other brokers should omit $username and
     * $password.
     *
     * @param string $username
     * @param string $password
     * @return array  user info
     * @throws Exception if login fails eg due to incorrect credentials
     */
    public function login($username = null, $password = null)
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        if (!isset($username) && isset($_POST['username'])) $username = $_POST['username'];
        if (!isset($password) && isset($_POST['password'])) $password = $_POST['password'];

        $result = $this->request('POST', 'login', compact('username', 'password', 'user_agent'));

        $this->userinfo = $result;

        return $this->userinfo;
    }
}