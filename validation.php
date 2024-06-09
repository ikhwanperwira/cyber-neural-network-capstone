<?php
require 'aws-config.php';
require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Aws\Exception\AwsException;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

function getJWKS($region, $userPoolId) {
    $url = "https://cognito-idp.{$region}.amazonaws.com/{$userPoolId}/.well-known/jwks.json";
    $json = file_get_contents($url);
    $jwks = json_decode($json, true);
    return $jwks['keys'];
}

function validateToken($idToken, $region, $userPoolId) {
    try {
        $jwks = getJWKS($region, $userPoolId);
        $decodedToken = JWT::decode($idToken, JWK::parseKeySet($jwks), ['RS256']);
        
        // Periksa apakah token sudah kadaluarsa
        if ($decodedToken->exp < time()) {
            throw new Exception('Token has expired');
        }

        return $decodedToken;
    } catch (Exception $e) {
        echo 'Token validation failed: ' . $e->getMessage();
        return false;
    }
}

function refreshToken($refreshToken, $clientId, $region, $accessKeyId, $secretAccessKey) {
    try {
        $client = new CognitoIdentityProviderClient([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $secretAccessKey,
            ],
        ]);

        $result = $client->initiateAuth([
            'ClientId' => $clientId,
            'AuthFlow' => 'REFRESH_TOKEN_AUTH',
            'AuthParameters' => [
                'REFRESH_TOKEN' => $refreshToken,
            ],
        ]);

        return $result['AuthenticationResult'];
    } catch (AwsException $e) {
        echo 'Token refresh failed: ' . $e->getAwsErrorMessage();
        return false;
    }
}