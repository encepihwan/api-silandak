<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Service\Sheets;
use App\Models\Korwil;
use Illuminate\Support\Facades\File;
use App\Http\Helpers\MethodsHelpers;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\Json;
use Illuminate\Support\Carbon;


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

    public function index(Request $request)
    {
        
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
        $response = "";
        $data = $this->fetch_data();
        $configurations = [
            ['slice_start' => 7, 'slice_length' => 13, 'pic' => 'PA HERSO'],
            ['slice_start' => 27, 'slice_length' => 12, 'pic' => 'PA DIAR'],
            ['slice_start' => 46, 'slice_length' => 13, 'pic' => 'ABAH ADE'],
            ['slice_start' => 66, 'slice_length' => 13, 'pic' => 'PA SISWO'],
            ['slice_start' => 86, 'slice_length' => 13, 'pic' => 'PA ZAENAL'],
            ['slice_start' => 106, 'slice_length' => 13, 'pic' => 'PA TATAN'],
            ['slice_start' => 126, 'slice_length' => 13, 'pic' => 'PA AGUS'],
            ['slice_start' => 146, 'slice_length' => 13, 'pic' => 'PA EFENDI'],
            ['slice_start' => 166, 'slice_length' => 13, 'pic' => 'PA ANDI'],
            ['slice_start' => 186, 'slice_length' => 13, 'pic' => 'PA AGUNG'],
        ];
        try{
            foreach ($configurations as $config) {
                $korwilData = array_slice($data, $config['slice_start'], $config['slice_length']);           
                foreach ($korwilData as $row) {
                    if (count($row) >= 15) {
                        $korwil = new Korwil();
                        $korwil->code = $row[1];
                        $korwil->package = $row[2];
                        $korwil->package_before_refocusing = $row[3];
                        $korwil->package_after_refocusing = $row[4];
                        $korwil->pagu_after_refocusing = $row[5];
                        $korwil->fe = $row[6];
                        $korwil->contract = $row[7];
                        $korwil->physique_percen = $row[8];
                        $korwil->pho = $row[9];
                        $korwil->ba = $row[10];
                        $korwil->percentage_after_realized = $row[11];
                        $korwil->pagu_realiized = $row[12];
                        $korwil->number_of_refocusing_package = $row[13];
                        $korwil->pagu_refocusing = $row[14];
                        $korwil->type = "01";
                        $korwil->area = "";
                        $korwil->pic = $config['pic'];
                        $korwil->created_at = Carbon::now();
                        $korwil->save();
                    }
                }
            }
            return response()->json($response);
        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Json::exception('Error Model ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\Illuminate\Database\QueryException $e) {
            return Json::exception('Error Query ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\ErrorException $e) {
            return Json::exception('Error Exception ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        }

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
