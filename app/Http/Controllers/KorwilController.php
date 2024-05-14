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
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;


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
            if (strtolower($request->area) == "total") {
                $korwil = $this->resume($request);

                // $roadActivity = RoadActivities::filterByField('year', $request->year)->get();
                $roadActivity = RoadActivities::filterByField('year', (int)$request->year)->get();

                // Periksa jika jumlah korwil dan roadActivities sama
                if (count($korwil) === count($roadActivity)) {
                    // Iterasi setiap RoadActivity dengan index yang cocok
                    foreach ($roadActivity as $index => $item) {
                        // Ambil korwil yang sesuai berdasarkan index
                        $matchedKorwil = $korwil[$index];

                        // Hitung count_pagu dan count_activity
                        $item->count_pagu = ($item->auction_pagu ?? 0) + ($item->pl_pagu ?? 0);
                        $item->count_activity = ($item->auction_activity ?? 0) + ($item->pl_activity ?? 0);

                        // Tambahkan korwil yang cocok ke dalam RoadActivity
                        $item->korwil = $matchedKorwil;
                    }
                } else {
                    foreach ($roadActivity as $item) {
                        $item->korwil = null;
                    }
                }

                // dd($roadActivity);
                return Json::response($roadActivity);
            } else {
                $year = Carbon::now()->year();
                $month = Carbon::now()->format('F');
                // dd($month);

                $data = Korwil::when($request->area, function($query) use ($request) {
                    return $query->where('area', $request->area);
                })->when($request->area, function($query) use ($month) {
                    return $query->where('month', $month);
                })->when($request->year, function ($query) use ($year) {
                    return $query->where('year', (int) $year);
                })->get();

                // dd($data);
                // Korwil::filterByField('area', $request->area)
                //     ->filterByField('month', $request->month)
                //     ->filterByField('year', $request->year)
                //     ->get();
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

    public function stackchart(Request $request)
    {
        try {
            $filter = strtolower($request->filter) == 'fisik' ? 'percentage_after_realized' : 'pagu_realiized';

            $data = Korwil::where('month', '=', $request->month)
                ->select('package', $filter)
                ->get()
                ->groupBy('package')
                ->map(function ($group)  use ($filter) {
                    return [
                        'package' => $group->first()->package,
                        'data' => $group->pluck($filter)->toArray()
                    ];
                })->values()
                ->toArray();

            $area = Korwil::where('month', '=', $request->month)
                ->pluck('area')
                ->unique()
                ->values()
                ->toArray();

            $response = [
                'series' => $data,
                'area' => $area
            ];

            return Json::response($response);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Json::exception('Error Model ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\Illuminate\Database\QueryException $e) {
            return Json::exception('Error Query ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\ErrorException $e) {
            return Json::exception('Error Exception ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        }
    }

    public function piechart(Request $request)
    {
        try {
            $filter = strtolower($request->filter) == 'fisik' ? 'percentage_after_realized' : 'pagu_realiized';

            $data = $this->resume($request);

            $filteredData = $data->filter(function ($item) use ($filter) {
                return $item->$filter != 0;
            });

            $merge = [
                'series' => $filteredData->pluck($filter)->toArray(),
                'package' => $filteredData->pluck('package')->toArray()
            ];

            return Json::response($merge);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Json::exception('Error Model ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\Illuminate\Database\QueryException $e) {
            return Json::exception('Error Query ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\ErrorException $e) {
            return Json::exception('Error Exception ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        }
    }

    public function sumary(Request $request)
    {
        try {
            // percentage
            $korwil = $this->resume($request);
            $total_physique_percen = 0;
            $total_package_after_refocusing = 0;
            foreach ($korwil as $item) {
                $total_physique_percen += $item->physique_percen;
                $total_package_after_refocusing += $item->package_after_refocusing;
            }

            // dd("p:".$total_physique_percen, "r:".$total_package_after_refocusing);
            $total_percentage_after_realized = round($total_physique_percen / $total_package_after_refocusing * 100);

            $roadActivity = RoadActivities::filterByField('year', (int)$request->year)->get();
            $lelang = 0;
            $pengadaan = 0;
            foreach ($roadActivity as $activity) {
                $lelang += $activity->auction_activity;
                $pengadaan += $activity->pl_activity;
            }

            $data = [
                'lelang' => $lelang,
                'pengadaan' => $pengadaan,
                'percentage' => $total_percentage_after_realized,
            ];

            return Json::response($data);
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
            $month = $request->month;


            $data = Korwil::raw(function ($collection) use ($year, $month) {

                $matchCondition = [];

                // Jika tahun kosong, tambahkan filter untuk bulan
                if (empty($year) && !empty($month)) {
                    $matchCondition['month'] = $month;
                }

                // Jika bulan kosong, tambahkan filter untuk tahun
                if (empty($month) && !empty($year)) {
                    $matchCondition['year'] = (int)$year;
                }

                // Jika keduanya tidak kosong, tambahkan filter untuk tahun dan bulan
                if (!empty($year) && !empty($month)) {
                    $matchCondition['year'] = (int)$year;
                    $matchCondition['month'] = $month;
                }

                $pipeline = [
                    ['$match' => $matchCondition],
                    [
                        '$group' => [
                            '_id' => [
                                'code' => '$code',
                                'package' => '$package',
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
                            'percentage_after_realized' => ['$sum' => '$physique_percen'],
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
                            'physique_percen' => '$physique_percen',
                            'fe' => '$fe',
                            'contract' => '$contract',
                            'pho' => '$pho',
                            'ba' => '$ba',
                            'percentage_after_realized' => [
                                '$multiply' => [
                                    ['$divide' => ['$physique_percen', '$package_after_refocusing']],
                                    100
                                ]
                            ],
                            'pagu_realiized' => '$pagu_realiized',
                            'number_of_refocusing_package' => '$number_of_refocusing_package',
                            'pagu_refocusing' => '$pagu_refocusing',
                            'year' => '$_id.year',
                        ],
                    ],
                ];

                return $collection->aggregate($pipeline);
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
            $reader = IOFactory::createReader('Xlsx');

            // Memuat file Excel
            $spreadsheet = $reader->load($path);

            // Mendapatkan data dari lembar pertama (indeks 0)
            $data = $spreadsheet->getActiveSheet()->toArray();

            // Mengonversi array menjadi format JSON
            // dd($path);
            // $data = Excel::toArray([], $path)[0];

            if (!empty($data)) {

                $headerSkipped = false;

                foreach ($data as $index => $row) {
                    if (!$headerSkipped) {
                        if ($index === 0 && isset($row[0]) && isset($row[0]) && trim($row[0]) === "PROGRES KEGIATAN PROGRAM PENYELENGGARAAN JALAN - BIDANG JALAN") {
                            $headerSkipped = true;
                            continue; // Skip the first header row
                        } else {
                            return Json::exception('Kesalah Input Format Excel');
                        }
                    }

                    if ($index === 1) continue;

                    $month = strval($row[17]);
                    $type = strval($row[16]);
                    $pic = strval($row[15]);
                    $area = strval($row[14]);
                    $code = strval($row[0]);
                    $package = strval($row[1]);

                    // Mengonversi kolom-kolom lainnya menjadi integer
                    $package_before_refocusing = intval($row[2]);
                    $package_after_refocusing = intval($row[3]);
                    $pagu_after_refocusing = intval($row[4]);
                    $fe = intval($row[5]);
                    $contract = intval($row[6]);
                    $physique_percen = intval($row[7]);
                    $pho = intval($row[8]);
                    $ba = intval($row[9]);
                    $percentage_after_realized = intval($row[10]);
                    $pagu_realiized = intval($row[11]);
                    $number_of_refocusing_package = intval($row[12]);
                    $pagu_refocusing = intval($row[13]);
                    $year = intval($row[18]);

                    // dd( $row[1]);
                    $existingRecord = Korwil::where('package', $package)
                        ->where('type', $type)
                        ->where('month', $month)
                        ->where('year', $year)
                        ->first();

                    // dd($existingRecord);

                    if ($existingRecord) {
                        $existingRecord->update([
                            'code' =>  $code,
                            'package' =>  $package,
                            'package_before_refocusing' =>  $package_before_refocusing,
                            'package_after_refocusing' =>  $package_after_refocusing,
                            'pagu_after_refocusing' =>  $pagu_after_refocusing,
                            'fe' => $fe,
                            'contract' => $contract,
                            'physique_percen' => $physique_percen,
                            'pho' => $pho,
                            'ba' => $ba,
                            'percentage_after_realized' => $percentage_after_realized,
                            'pagu_realiized' => $pagu_realiized,
                            'number_of_refocusing_package' => $number_of_refocusing_package,
                            'pagu_refocusing' => $pagu_refocusing,
                            'area' => $area,
                            'pic' => $pic,
                            'type' => $type,
                            'month' => $month,
                            'year' => $year,
                        ]);
                    } else {
                        Korwil::create([
                            'code' =>  $code,
                            'package' =>  $package,
                            'package_before_refocusing' =>  $package_before_refocusing,
                            'package_after_refocusing' =>  $package_after_refocusing,
                            'pagu_after_refocusing' =>  $pagu_after_refocusing,
                            'fe' => $fe,
                            'contract' => $contract,
                            'physique_percen' => $physique_percen,
                            'pho' => $pho,
                            'ba' => $ba,
                            'percentage_after_realized' => $percentage_after_realized,
                            'pagu_realiized' => $pagu_realiized,
                            'number_of_refocusing_package' => $number_of_refocusing_package,
                            'pagu_refocusing' => $pagu_refocusing,
                            'area' => $area,
                            'pic' => $pic,
                            'type' => $type,
                            'month' => $month,
                            'year' => $year,
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
