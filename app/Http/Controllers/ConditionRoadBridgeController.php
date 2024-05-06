<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Helpers\Json;
use App\Models\ConditionRoadBridge;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

            
            $path = $request->file('file')->getRealPath();
            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($path);

            // Mendapatkan data dari lembar pertama (indeks 0)
            $data = $spreadsheet->getActiveSheet()->toArray();

            if (!empty($data)) {

                $headerSkipped = false;

                foreach ($data as $index => $row) {
                    if (!$headerSkipped) {
                        if ($index === 0 && isset($row[0]) && isset($row[0]) && trim($row[0]) === "DATA KONDISI JALAN & JEMBATAN") {
                            $headerSkipped = true;
                            continue; // Skip the first header row
                        } else {
                            return Json::exception('Kesalah Input Format Excel');
                        }
                    }
                    if ($index === 1) continue; //skip
                    $condition = strval($row[1]);
                    $type = strval($row[2]);
                    $unit = strval($row[3]);
                    $year = intval($row[4]);
                    $value = intval($row[5]);
                    $field = strval($row[6]);
                    $pic = strval($row[7]);

                    $existingRecord = ConditionRoadBridge::where('condition', $condition)->where('year', $year)->first();

                    if ($existingRecord) {
                        $existingRecord->update([
                            'type' => $type,
                            'unit' =>  $unit,
                            'value' => $value,
                            'field' => $field,
                            'pic' => $pic,
                        ]);
                    } else {
                        ConditionRoadBridge::create([
                            'condition' =>  $condition,
                            'type' =>  $type,
                            'unit' =>  $unit,
                            'year' =>  $year,
                            'value' => $value,
                            'field' => $field,
                            'pic' => $pic,
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
