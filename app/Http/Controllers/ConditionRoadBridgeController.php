<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Helpers\Json;
use App\Models\ConditionRoadBridge;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ConditionRoadBridgeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $data = ConditionRoadBridge::get();

            $response = [
                'Jalan' => [],
                'Jembatan' => []
            ];

            // Membuat daftar tahun dinamis
            $years = [];

            foreach ($data as $item) {
                $year = $item['year'];
                $type = $item['type'];

                // Mengumpulkan daftar tahun unik dari data
                if (!in_array($year, $years)) {
                    $years[] = $year;
                }

                // Inisialisasi struktur data tahun jika belum ada
                if (!isset($response[$type][$year])) {
                    $response[$type][$year] = [];
                }
            }

            // Mengurutkan daftar tahun secara ascending
            sort($years);
            // dd($year);

            // Menyusun struktur respons berdasarkan jenis dan tahun
            foreach ($data as $item) {
                $year = $item['year'];
                $value = $item['value'];
                $type = $item['type'];

                // Membuat struktur data untuk setiap tahun
                $response[$type][$year][] = [
                    'No.' => count($response[$type][$year]) + 1,
                    'Kebutuhan Data' => $item['condition'],
                    'Satuan' => $item['unit'],
                    'Bidang' => $item['field'],
                    'PD Penenggung Jawab' => $item['pic'],
                    $year => $value
                ];
            }

            // Mengisi tahun yang tidak memiliki data dengan nilai default
            foreach ($response as $type => &$typeData) {
                foreach ($years as $year) {
                    if (!isset($typeData[$year])) {
                        $typeData[$year] = [];
                    }
                }
            }

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
            $data = Excel::toArray([], $path)[0];

            if (!empty($data)) {

                $headerSkipped = false;

                foreach ($data as $row) {
                    if (!$headerSkipped) {
                        $headerSkipped = true;
                        continue;
                    }

                    $existingRecord = ConditionRoadBridge::where('condition', $row[1])->where('year', $row[4])->first();

                    if ($existingRecord) {
                        $existingRecord->update([
                            'type' =>  $row[2],
                            'unit' =>  $row[3],
                            'value' => $row[5],
                            'field' => $row[6],
                            'pic' => $row[7],
                        ]);
                    } else {
                        ConditionRoadBridge::create([
                            'condition' =>  $row[1],
                            'type' =>  $row[2],
                            'unit' =>  $row[3],
                            'year' =>  $row[4],
                            'value' => $row[5],
                            'field' => $row[6],
                            'pic' => $row[7],
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
