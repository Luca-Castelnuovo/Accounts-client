<?php

session_start();

class AccessToken
{
    private $token;
    private $scope;
    private $expires;

    public function __construct($config)
    {
        $this->token = $config['token'];
        $this->scope = $config['scope'];
        $this->expires = $config['expires'];
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getExpires()
    {
        return $this->expires;
    }

    public function hasExpired()
    {
        return $this->expires <= time();
    }
}


class OAuth
{
    private $clientID;
    private $clientSecret;
    private $redirectUri;

    private $urlAuthorize;
    private $urlAccessToken;

    public function __construct($config)
    {
        $this->clientID = $config['clientID'];
        $this->clientSecret = $config['clientSecret'];
        $this->redirectUri = $config['redirectUri'];
        $this->urlAuthorize = $config['urlAuthorize'];
        $this->urlAccessToken = $config['urlAccessToken'];
    }

    private function request($method, $url, $data = false, $headers = [])
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if ($data) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response, true);

        if (!$response['success']) {
            throw new Exception($response['error']);
        }

        return $response;
    }

    private function setState()
    {
        $_SESSION['auth_state'] = bin2hex(random_bytes(32));
        return $_SESSION['auth_state'];
    }


    private function getState()
    {
        return $_SESSION['auth_state'];
    }

    public function checkState($state)
    {
        return $state === $this->getState();
    }

    public function getAuthorizationUrl($scope_array= null, $redirect_uri = null)
    {
        if (!isset($scope_array) && empty($scope_array)) {
            $scope = 'basic:read';
        } else {
            $scope = implode(",", $scope_array);
        }

        if (!isset($redirect_uri) && empty($redirect_uri)) {
            $redirect_uri = $this->redirectUri;
        }

        $state = $this->setState();

        return "{$this->urlAuthorize}?client_id={$this->clientID}&scope={$scope}&redirect_uri={$redirect_uri}&state={$state}";
    }

    public function getAccessToken($grant_type, $data = null)
    {
        $data['grant_type'] = $grant_type;
        $data['client_id'] = $this->clientID;
        $data['client_secret'] = $this->clientSecret;

        if ($grant_type === 'authorization_code') {
            $data['state'] = $this->getState();
        }

        $access_response = $this->request('POST', $this->urlAccessToken, $data);

        $access_token = new AccessToken([
            'token' => $access_response['access_token'],
            'scope' => $access_response['scope'],
            'expires' => $access_response['expires']
        ]);

        return $access_token;
    }

    public function authenticatedRequest($method, $url, $access_token, $data = false, $headers = [])
    {
        $headers[] = "Authorization: Bearer {$access_token}";
        return $this->request($method, $url, $data, $headers);
    }
}
