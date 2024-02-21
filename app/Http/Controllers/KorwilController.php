<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Service\Sheets;
use App\Models\Korwil;
use App\Models\RoadActivities;
use Illuminate\Support\Facades\File;
use App\Http\Helpers\MethodsHelpers;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\Json;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ExcelDataImport;
use Exception;

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
        try {
            if (strtolower($request->filter) == "total") {
                $korwil = $this->resume($request);

                // $roadActivity = RoadActivities::filterByField('year', $request->year)->get();
                $roadActivity = RoadActivities::filterByField('year', (int)$request->year)->get();

                foreach ($roadActivity as $item) {
                    $item->count_pagu = ($item->auction_pagu ?? 0) + ($item->pl_pagu ?? 0);
                    $item->count_activity = ($item->auction_activity ?? 0) + ($item->pl_activity ?? 0);
                }

                $mergedData = [
                    'road_activities' => $roadActivity,
                    'korwil' => $korwil,
                ];
                return Json::response($mergedData);

            } else {
                $data = Korwil::filterByField('area', $request->area)
                    ->filterByField('month', $request->month)
                    ->filterByField('area', $request->area)
                    ->get();
                $totals = [
                    'total_package_before_refocusing' => 0,
                    'total_package_after_refocusing' => 0,
                    'total_pagu_after_refocusing' => 0,
                    'total_fe' => 0,
                    'total_contract' => 0,
                    'total_physique_percen' => 0,
                    'total_pho' => 0,
                    'total_ba' => 0,
                    'total_pagu_realiized' => 0,
                    'total_number_of_refocusing_package' => 0,
                    'total_pagu_refocusing' => 0,
                ];

                foreach ($data as $record) {
                    $totals['total_package_before_refocusing'] += $record->package_before_refocusing;
                    $totals['total_package_after_refocusing'] += $record->package_after_refocusing;
                    $totals['total_pagu_after_refocusing'] += $record->pagu_after_refocusing;
                    $totals['total_fe'] += $record->fe;
                    $totals['total_contract'] += $record->contract;
                    $totals['total_physique_percen'] += $record->physique_percen;
                    $totals['total_pho'] += $record->pho;
                    $totals['total_ba'] += $record->ba;
                    $totals['total_pagu_realiized'] += $record->pagu_realiized;
                    $totals['total_number_of_refocusing_package'] += $record->number_of_refocusing_package;
                    $totals['total_pagu_refocusing'] += $record->pagu_refocusing;
                }

                $merge = [
                    'totals' => $totals,
                    'data' => $data,
                ];

                return Json::response($merge);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Json::exception('Error Model ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\Illuminate\Database\QueryException $e) {
            return Json::exception('Error Query ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\ErrorException $e) {
            return Json::exception('Error Exception ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        }
    }

    public function resume(Request $request)
    {
        try {
            $year = $request->year;
            $data = Korwil::raw(function ($collection) use ($year) {
                return $collection->aggregate([
                    [
                        '$match' => [
                            'year' => (int)$year
                        ]
                    ],
                    [
                        '$group' => [
                            '_id' => [
                                'code' => '$code',
                                'package' => '$package',
                                'type' => '$type',
                                'area' => '$area',
                                'pic' => '$pic',
                                'month' => '$month',
                                'year' => '$year'
                            ],
                            'package_before_refocusing' => ['$sum' => '$package_before_refocusing'],
                            'package_after_refocusing' => ['$sum' => '$package_after_refocusing'],
                            'pagu_after_refocusing' => ['$sum' => '$pagu_after_refocusing'],
                            'fe' => ['$sum' => '$fe'],
                            'contract' => ['$sum' => '$contract'],
                            'physique_percen' => ['$sum' => '$physique_percen'],
                            'pho' => ['$sum' => '$pho'],
                            'ba' => ['$sum' => '$ba'],
                            'percentage_after_realized' => ['$sum' => '$percentage_after_realized'],
                            'pagu_realiized' => ['$sum' => '$pagu_realiized'],
                            'number_of_refocusing_package' => ['$sum' => '$number_of_refocusing_package'],
                            'pagu_refocusing' => ['$sum' => '$pagu_refocusing'],
                        ],
                    ],
                    [
                        '$project' => [
                            '_id' => 0,
                            'code' => '$_id.code',
                            'package' => '$_id.package',
                            'package_before_refocusing' => '$package_before_refocusing',
                            'package_after_refocusing' => '$package_after_refocusing',
                            'pagu_after_refocusing' => '$pagu_after_refocusing',
                            'fe' => '$fe',
                            'contract' => '$contract',
                            'pho' => '$pho',
                            'ba' => '$ba',
                            'percentage_after_realized' => '$percentage_after_realized',
                            'pagu_realiized' => '$pagu_realiized',
                            'number_of_refocusing_package' => '$number_of_refocusing_package',
                            'pagu_refocusing' => '$pagu_refocusing',
                            'type' => '$_id.type',
                            'area' => '$_id.area',
                            'pic' => '$_id.pic',
                            'month' => '$_id.month',
                            'year' => '$_id.year',
                        ],
                    ],
                ]);
            });

            return $data;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Json::exception('Error Model ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\Illuminate\Database\QueryException $e) {
            return Json::exception('Error Query ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\ErrorException $e) {
            return Json::exception('Error Exception ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        }
    }

    public function import(Request $request)
    {
        try {

            // $file = $request->file('file');
            $path = $request->file('file')->getRealPath();
            $data = Excel::toArray([], $path)[0];

            if (!empty($data)) {

                $headerSkipped = false;

                foreach ($data as $row) {
                    if (!$headerSkipped) {
                        $headerSkipped = true;
                        continue;
                    }
                    // dd( $row[1]);
                    $existingRecord = Korwil::where('package', $row[1])
                        ->where('type', $row[16])
                        ->where('month', $row[17])
                        ->where('year', $row[18])
                        ->first();

                    // dd($existingRecord);

                    if ($existingRecord) { // Periksa jika $existingRecord tidak null atau tidak false
                        $existingRecord->update([
                            'code' =>  $row[0], // Kolom 'no' di model ExcelData sesuai dengan kolom 'No' di Excel
                            'package' =>  $row[1],
                            'package_before_refocusing' =>  $row[2],
                            'package_after_refocusing' =>  $row[3],
                            'pagu_after_refocusing' =>  $row[4],
                            'fe' => $row[5],
                            'contract' => $row[6],
                            'physique_percen' => $row[7],
                            'pho' => $row[8],
                            'ba' => $row[9],
                            'percentage_after_realized' => $row[10],
                            'pagu_realiized' => $row[11],
                            'number_of_refocusing_package' => $row[12],
                            'pagu_refocusing' => $row[13],
                            'area' => $row[14],
                            'pic' => $row[15],
                            'type' => $row[16],
                            'month' => $row[17],
                            'year' => $row[18],
                        ]);
                    } else {
                        Korwil::create([
                            'code' =>  $row[0], // Kolom 'no' di model ExcelData sesuai dengan kolom 'No' di Excel
                            'package' =>  $row[1],
                            'package_before_refocusing' =>  $row[2],
                            'package_after_refocusing' =>  $row[3],
                            'pagu_after_refocusing' =>  $row[4],
                            'fe' => $row[5],
                            'contract' => $row[6],
                            'physique_percen' => $row[7],
                            'pho' => $row[8],
                            'ba' => $row[9],
                            'percentage_after_realized' => $row[10],
                            'pagu_realiized' => $row[11],
                            'number_of_refocusing_package' => $row[12],
                            'pagu_refocusing' => $row[13],
                            'area' => $row[14],
                            'pic' => $row[15],
                            'type' => $row[16],
                            'month' => $row[17],
                            'year' => $row[18],
                        ]);
                    }
                }
                $response = "success";
                return Json::response($response);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Json::exception('Error Model ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\Illuminate\Database\QueryException $e) {
            return Json::exception('Error Query ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\ErrorException $e) {
            return Json::exception('Error Exception ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        }
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
        try {
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
                        $korwil->month = Carbon::now()->format('F');
                        $korwil->year = Carbon::now()->year;
                        $korwil->created_at = Carbon::now();
                        $korwil->save();
                    }
                }
            }
            // return response()->json($response);
            return Json::response($response);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
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
