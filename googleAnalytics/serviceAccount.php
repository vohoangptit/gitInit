<?php
require_once __DIR__ . '/vendor/autoload.php';
// Use the developers console and download your service account
// credentials in JSON format. Place them in this directory or
// change the key file location if necessary.

putenv('GOOGLE_APPLICATION_CREDENTIALS='.__DIR__.'/service-account.json');

$service = getAuthenticateServiceAccount();

function getAuthenticateServiceAccount() {
    try {
        // Create and configure a new client object.
        $client = new Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope(Google_Service_Analytics::ANALYTICS);
        return new Google_Service_AnalyticsReporting($client);
    } catch (Exception $e) {
        print "An error occurred: " . $e->getMessage();
    }
}
