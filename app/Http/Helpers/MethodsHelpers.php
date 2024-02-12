<?php

namespace App\Http\Helpers;

use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Mail;
use App\Mail\SendMail;
use Illuminate\Support\Facades\File;

class MethodsHelpers
{
    

    public static function sendMail($email, $mailData)
    {
        Mail::to($email)->send(new SendMail($mailData));
    }

    public static function getClient()
    {
        $client = new Client();
        $client->setApplicationName('SILANDAK');
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAccessType('offline');

        // Build the path to the credentials file
        $credentialsPath = base_path('./upbeat-medley-319008-c172a7bbdb41.json');
        
        // Check if the credentials file exists
        if (File::exists($credentialsPath)) {
            $client->setAuthConfig($credentialsPath);
            $service = new Sheets($client);
            return $service;
        } else {
            // Handle the case where credentials file does not exist
            throw new \Exception('Credentials file not found.');
        }
    }

    public static function fetchData()
    {
        $sa = self::getClient();
        $spreadsheetId = '1MEbpNzmrZx9Yk5IIwvNKZKv0aCLbU7aNfj5Sj1LLNf0';
        $range = 'RESUME PER DESEMBER'; // Adjust the range as needed

        $response = $sa->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();

        return $values;
    }

}
