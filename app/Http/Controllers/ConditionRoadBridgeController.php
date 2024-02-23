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

            // Inisialisasi array untuk menyimpan hasil pengolahan
            $result = [
                'jalan' => [],
                'jembatan' => []
            ];

            // Lakukan pengolahan data
            foreach ($data as $item) {
                // Buat key untuk tahun
                $year_key = (string) $item->year;

                // Buat key untuk kondisi
                $condition_key = $item->condition;

                // Buat array untuk menyimpan informasi kondisi
                $condition_info = [
                    'condition' => $item->condition,
                    'unit' => $item->unit,
                    'field' => $item->field,
                    'pic' => $item->pic,
                    $year_key => $item->value // Tambahkan nilai untuk tahun tertentu
                ];

                // Tentukan apakah ini data jalan atau Jembatan
                if (strtolower($item->type) === 'jalan') {
                    // Cek apakah kondisi sudah ada di dalam array jalan
                    $existing_index = array_search($condition_key, array_column($result['jalan'], 'condition'));
                    if ($existing_index !== false) {
                        // Jika kondisi sudah ada, tambahkan nilai untuk tahun tertentu
                        $result['jalan'][$existing_index][$year_key] = $item->value;
                    } else {
                        // Jika kondisi belum ada, tambahkan informasi kondisi ke dalam array jalan
                        $result['jalan'][] = $condition_info;
                    }
                } else if (strtolower($item->type) === 'jembatan') {
                    // Cek apakah kondisi sudah ada di dalam array Jembatan
                    $existing_index = array_search($condition_key, array_column($result['jembatan'], 'condition'));
                    if ($existing_index !== false) {
                        // Jika kondisi sudah ada, tambahkan nilai untuk tahun tertentu
                        $result['jembatan'][$existing_index][$year_key] = $item->value;
                    } else {
                        // Jika kondisi belum ada, tambahkan informasi kondisi ke dalam array Jembatan
                        $result['jembatan'][] = $condition_info;
                    }
                }
            }

            // Urutkan array berdasarkan kondisi
            usort($result['jalan'], function ($a, $b) {
                return strcmp($a['condition'], $b['condition']);
            });

            usort($result['jembatan'], function ($a, $b) {
                return strcmp($a['condition'], $b['condition']);
            });

            return Json::response($result);
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
