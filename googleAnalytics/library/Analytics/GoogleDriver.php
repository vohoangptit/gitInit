<?php
namespace Analytics;

use function GuzzleHttp\Psr7\_parse_request_uri;
use http\Client;

class GoogleDriver{


    /**
     * GoogleDriver constructor.
     */
    public function __construct()
    {
    }

    function getClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName('GoogleAnalytics');
        $client->setScopes(
            [\Google_Service_Drive::DRIVE
            ,\Google_Service_Drive::DRIVE_FILE
            ,\Google_Service_Drive::DRIVE_APPDATA
            ,\Google_Service_Drive::DRIVE_SCRIPTS
            ,\Google_Service_Drive::DRIVE_METADATA]);
        $client->setAuthConfig(__DIR__ .'/../../credential_driver.json');
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
                $authCode = '4/_gBOV0xinSLIFgk3dKQtMoAMl-BIbDUVDEllWjqHDs88a4ywBQJt6zo';
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

    function uploadFile($client, $nameContents, $mimeType, $name){
        try{

            $service = new \Google_Service_Drive($client);
            $fileMetadata = new \Google_Service_Drive_DriveFile(array(
                'name' => $name,
                'mimeType' => $mimeType, // type for .docx file
            ));

            $content = file_get_contents($nameContents,true);

            $file = $service->files->create($fileMetadata, array(
                'data' => $content,
                'mimeType' => 'text/plain',
                'uploadType' => 'multipart',
                'fields' => 'id'));
            printf("File ID: %s\n", $file->id);

        }catch (\Exception $e){
            echo "<pre>";
            print_r($e->getMessage());
            echo "</pre>";
            exit();
        }

    }

    function exportFile($client){
        $fileId = '1IW6d5CHj863JcgOwbjzto8c9Kj6ZD_gy';
        $service = new \Google_Service_Drive($client);
        $response = $service->files->get($fileId, array(
            'atl' => 'media'
        ));
        $content = $response->getBody()->getContents();
        echo "<pre>";
        print_r($content);
        echo "</pre>";
        exit();
    }

    function updateFile($client){
        try{
            $this->exportFile($client);
            $service = new \Google_Service_Drive($client);
            $fileMetadata = new \Google_Service_Drive_DriveFile();
            $data ="";
            $service->files->update('1IW6d5CHj863JcgOwbjzto8c9Kj6ZD_gy', $fileMetadata, array(
                'data' => $data,
                'mimeType' => 'text/plain',
                'uploadType' => 'multipart',
                'keepRevisionForever' => true,
                'supportsTeamDrives' => true,
                'useContentAsIndexableText' => true,
            ));
        }catch (\Exception $e){
            echo "<pre>";
            print_r($e->getMessage());
            echo "</pre>";
            exit();
        }
    }
}

// Get the API client and construct the service object.


