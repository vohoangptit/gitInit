<?php
// Load the Google API PHP Client Library.
require_once __DIR__ . '/../vendor/autoload.php';

// Start a session to persist credentials.
session_start();

// Create the client object and set the authorization configuration
// from the client_secrets.json you downloaded from the Developers Console.

$client = new Google_Client([
    'prompt' => 'consent',
    'access_type' => 'offline',
]);
$client->setAuthConfig(__DIR__ . '/../client_secrets.json');
$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php');
$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);

// Handle authorization flow from the server.
if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
    $data = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $_SESSION['refresh_token'] = $client->getRefreshToken();
    $_SESSION['access_token'] = $client->getAccessToken();
    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/';
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}


