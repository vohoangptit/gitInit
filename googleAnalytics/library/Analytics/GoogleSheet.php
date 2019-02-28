<?php

namespace Analytics;

use function GuzzleHttp\Psr7\_parse_request_uri;

class GoogleSheet
{


    /**
     * GoogleDriver constructor.
     */
    public function __construct()
    {
    }

    function getClient()
    {
        $client = new \Google_Client();

        $client->setApplicationName('Google Sheets API PHP');
        $client->setScopes([\Google_Service_Sheets::DRIVE
            , \Google_Service_Sheets::DRIVE_FILE
            , \Google_Service_Sheets::SPREADSHEETS]);
        $client->setAuthConfig(__DIR__ . '/../../credential_sheet.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $tokenPath = 'token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }
        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));
                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    function uploadSheet($client, $title)
    {

        try {
            $service = new \Google_Service_Sheets($client);
            $spreadsheetBody = new \Google_Service_Sheets_Spreadsheet([
                'properties' => [
                    'title' => $title
                ],
            ]);
            $spreadsheet = $service->spreadsheets->create($spreadsheetBody, [
                'fields' => 'spreadsheetId'
            ]);
//            printf("Spreadsheet ID: %s\n", $spreadsheet->spreadsheetId);
            return $spreadsheet->spreadsheetId;
        } catch (\Exception $e) {
            echo "<pre>";
            print_r($e->getMessage());
            echo "</pre>";
            exit();
        }
    }

    function appendSheet($client,$sheetId,$ten,$email,$linkCV,$cv,$note)
    {
        try {
            $service = new \Google_Service_Sheets($client);
            $requestBody = new \Google_Service_Sheets_ValueRange();
            $requestBody->setValues(
                (
                    [
                        ['Name', $ten],
                        ['Email', $email],
                        ['Link CV', $linkCV],
                        ['Name CV', $cv],
                        ['Note', $note]
                    ]
                )
            );
            $spreadsheet = $service->spreadsheets_values->append($sheetId,
                'E3:F4', $requestBody, [
                    'valueInputOption' => 'USER_ENTERED',
                ]);
//            printf("Spreadsheet ID: %s\n", $spreadsheet->spreadsheetId);

        } catch (\Exception $e) {
            echo "<pre>";
            print_r($e->getMessage());
            echo "</pre>";
            exit();
        }

    }

}

// Get the API client and construct the service object.


