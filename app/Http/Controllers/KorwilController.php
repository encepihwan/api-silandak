<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Service\Sheets;
use Illuminate\Support\Facades\File;
use App\Http\Helpers\MethodsHelpers;


class KorwilController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $sa;

    public function __construct()
    {
        $client = new \Google\Client();
        $client->setApplicationName('SILANDAK');
        $client->setScopes([\Google\Service\Sheets::SPREADSHEETS_READONLY]);
        $client->setAccessType('offline');

        // Build the path to the credentials file
        $credentialsPath = base_path('./upbeat-medley-319008-c172a7bbdb41.json');
        
        // Check if the credentials file exists
        if (File::exists($credentialsPath)) {
            $client->setAuthConfig($credentialsPath);

            $this->sa = new \Google\Service\Sheets($client);
        } else {
            // Handle the case where credentials file does not exist
            throw new \Exception('Credentials file not found.');
        }
    }

    private function fetch_data()
    {
        $spreadsheetId = '1MEbpNzmrZx9Yk5IIwvNKZKv0aCLbU7aNfj5Sj1LLNf0';
        $range = 'RESUME PER DESEMBER'; // Adjust the range as needed

        $response = $this->sa->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();

        return $values;
    }

    public function index()
    {
        $data = $this->fetch_data();
        return response()->json($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
