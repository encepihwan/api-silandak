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
    public function index()
    {
        //
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

                    ConditionRoadBridge::create([
                        'condition' =>  $row[1],
                        'type' =>  $row[2],
                        'unit' =>  $row[3],
                        'year' =>  $row[4],
                        'value' => $row[5],
                        'filed' => $row[6],
                        'pic' => $row[7],
                    ]);
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
