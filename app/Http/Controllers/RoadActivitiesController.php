<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Helpers\Json;
use App\Models\RoadActivities;
use App\Models\Korwil;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Helpers\MethodsHelpers;
use Jenssegers\Mongodb\Query\Builder as MongoBuilder;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RoadActivitiesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            
            $data = RoadActivities::filterByField('year', (int)$request->year)->get();

            foreach ($data as $item) {
                $item->count_pagu = ($item->auction_pagu ?? 0) + ($item->pl_pagu ?? 0);
                $item->count_activity = ($item->auction_activity ?? 0) + ($item->pl_activity ?? 0);
            }

            return Json::response($data);
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
        //
    }

    public function import(Request $request)
    {
        try {

            // $file = $request->file('file');
            $path = $request->file('file')->getRealPath();
            $path = $request->file('file')->getRealPath();
            $reader = IOFactory::createReader('Xlsx');
            // $data = Excel::toArray([], $path)[0];
            $spreadsheet = $reader->load($path);

            // Mendapatkan data dari lembar pertama (indeks 0)
            $data = $spreadsheet->getActiveSheet()->toArray();
            if (!empty($data)) {

                $headerSkipped = false;

                foreach ($data as $index => $row) {
                    if (!$headerSkipped) {
                        if ($index === 0 && isset($row[0]) && isset($row[0]) && trim($row[0]) === "KEGIATAN BIDANG JALAN (FISIK DAN JASA KONSULTANSI TANPA BIAYA LAIN-LAIN)") {
                            $headerSkipped = true;
                            continue; // Skip the first header row
                        } else {
                            return Json::exception('Kesalah Input Format Excel');
                        }
                    }

                    $subactivity =  strval($row[1]);
                    $auction_pagu =  intval($row[2]);
                    $auction_activity =  intval($row[3]);
                    $pl_pagu =  intval($row[4]);
                    $pl_activity = intval($row[5]);
                    $year = intval($row[6]);

                    if ($index === 1) continue;
                    if ($index === 2) continue;

                    $existingRecord = RoadActivities::where('subactivity', $row[1])->where('year', $row[6])->first();

                    if ($existingRecord) {
                        // Jika sudah ada, update data
                        $existingRecord->update([
                            'auction_pagu' =>  $auction_pagu,
                            'auction_activity' =>  $auction_activity,
                            'pl_pagu' =>  $pl_pagu,
                            'pl_activity' => $pl_activity,
                        ]);
                    } else {
                        RoadActivities::create([
                            'subactivity' =>  $subactivity,
                            'auction_pagu' =>  $auction_pagu,
                            'auction_activity' =>  $auction_activity,
                            'pl_pagu' =>  $pl_pagu,
                            'pl_activity' => $pl_activity,
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
