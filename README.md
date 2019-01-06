# OAuth 2.0 Client

This package makes it simple to integrate your application with my [OAuth 2.0](https://accounts.lucacastelnuovo.nl) service.

[![Latest Version](https://img.shields.io/github/release/luca-castelnuovo/oauth2-client.svg?style=flat-square)](https://github.com/Luca-Castelnuovo/oauth2-client/releases)
[![Software License](https://img.shields.io/github/license/Luca-Castelnuovo/oauth2-client.svg?style=flat-square)](https://github.com/Luca-Castelnuovo/oauth2-client/blob/master/LICENSE)

---

We are all used to seeing those "Connect with Facebook/Google/etc." buttons around the internet, and social network integration is an important feature of most web applications these days. Many of these sites use an authentication and authorization standard called OAuth 2.0 ([RFC 6749](http://tools.ietf.org/html/rfc6749)).

This OAuth 2.0 client library will work with any OAuth provider that conforms to the OAuth 2.0 standard. Out-of-the-box, it works with my OAuth2 service but only requires changes to `urlAuthorize` and `urlAccessToken` variables to work with others.

## Requirements

The following versions of PHP are supported.

* PHP 5.6
* PHP 7.0
* PHP 7.1
* PHP 7.2
* PHP 7.3

### Authorization Code Grant

The authorization code grant type is the most common grant type used when authenticating users with a third-party service. This grant type utilizes a client (this library), a server (the service provider), and a resource owner (the user with credentials to a protected—or owned—resource) to request access to resources owned by the user. This is often referred to as _3-legged OAuth_, since there are three parties involved.

```php
$provider = new OAuth([
    'clientID'                => 'CLIENT_ID',        // The client ID assigned to you by the provider
    'clientSecret'            => 'CLIENTS_SECRET',   // The client password assigned to you by the provider
    'redirectUri'             => 'YOUR_WEBSITE_URL',
    'urlAuthorize'            => 'https://accounts.lucacastelnuovo.nl/auth/authorize',
    'urlAccessToken'          => 'https://accounts.lucacastelnuovo.nl/auth/token',
]);

// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {

    // Fetch the authorization URL from the provider; you can specify
    // the required scopes in an array or leave blank for the default basic:read
    // scope. this returns the urlAuthorize option and generates and applies
    // any necessary parameters
    // (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl(['basic:read']);

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (!$provider->checkState($_GET['state'])) {
    exit('Invalid state');

} else {

    try {

        // Try to get an access token using the authorization code grant.
        $access_token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);


        // We have an access token, which we may use in authenticated
        // requests against the service provider's API.
        echo 'Access Token: ' . $access_token->getToken() . "<br>";
        echo 'Expired in: ' . $access_token->getExpires() . "<br>";
        echo 'Already expired? ' . ($access_token->hasExpired() ? 'expired' : 'not expired') . "<br>";

        // The provider provides a way to get an authenticated API request for
        // the service, using the access token;
        $request = $provider->authenticatedRequest(
            'GET',
            'https://api.lucacastelnuovo.nl/user/',
            $access_token->getToken()
        );

        print_r($request);

    } catch (Exception $e) {

        // Failed to get the access token or user details.
        exit($e->getMessage());

    }

}
```

### Client Credentials Grant

When your application is acting on its own behalf to access resources it controls/owns in a service provider, it may use the client credentials grant type. This is best used when the credentials for your application are stored privately and never exposed (e.g. through the web browser, etc.) to end-users. This grant type functions similarly to the resource owner password credentials grant type, but it does not request a user's username or password. It uses only the client ID and secret issued to your client by the service provider.

```php
// Note: The `urlAuthorize` option is required, even though
// it's not used in the OAuth 2.0 client credentials grant type.

$provider = new OAuth([
    'clientID'                => 'CLIENT_ID',        // The client ID assigned to you by the provider
    'clientSecret'            => 'CLIENTS_SECRET',   // The client password assigned to you by the provider
    'redirectUri'             => 'YOUR_WEBSITE_URL',
    'urlAuthorize'            => 'https://accounts.lucacastelnuovo.nl/auth/authorize',
    'urlAccessToken'          => 'https://accounts.lucacastelnuovo.nl/auth/token',
]);

try {

    // Try to get an access token using the client credentials grant.
    $access_token = $provider->getAccessToken('client_credentials');

} catch (Exception $e) {

    // Failed to get the access token
    exit($e->getMessage());

}
```

## Install from source

Download the PHP library from Github, then include Unirest.php in your script:

```bash
git clone git@github.com:Luca-Castelnuovo/oauth2-client.git
```

```php
require '/path/to/oauth2-client/src/oauth.php';
```

## License

The MIT License (MIT). Please see [License File](https://github.com/Luca-Castelnuovo/oauth2-client/blob/master/LICENSE) for more information.
