<?php
require 'aws-config.php';
require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Aws\Exception\AwsException;

function validateToken($idToken, $expectedAud) {
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $idToken);

    // Decode the payload
    $payload = json_decode(base64_decode($payloadEncoded), true);

    // Periksa apakah token sudah kadaluarsa
    if ($payload['exp'] < time()) {
        throw new Exception('Token has expired');
    }

    // Periksa apakah 'aud' sesuai
    if ($payload['aud'] !== $expectedAud) {
        throw new Exception('Invalid token audience');
    }

    return $payload;
}

// Fungsi untuk memperbarui token
function refreshTokenIfNeeded($client, $clientId) {
    if (!isset($_SESSION['access_token_expiration']) || time() >= $_SESSION['access_token_expiration']) {
        try {
            $result = $client->initiateAuth([
                'ClientId' => $clientId,
                'AuthFlow' => 'REFRESH_TOKEN_AUTH',
                'AuthParameters' => [
                    'REFRESH_TOKEN' => $_SESSION['refresh_token'],
                ],
            ]);

            $_SESSION['access_token'] = $result['AuthenticationResult']['AccessToken'];
            $_SESSION['id_token'] = $result['AuthenticationResult']['IdToken'];
            $_SESSION['access_token_expiration'] = time() + $result['AuthenticationResult']['ExpiresIn'];
        } catch (AwsException $e) {
            echo 'Error refreshing token: ' . $e->getAwsErrorMessage();
            session_destroy();
            header('Location: login.php');
            exit();
        }
    }
}