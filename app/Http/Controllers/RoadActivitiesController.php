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
            // $data = $roadActivity = RoadActivities::filterByField('year', $request->year)->get();

            // foreach ($roadActivity as $item) {
            //     $item->count_pagu = ($item->auction_pagu ?? 0) + ($item->pl_pagu ?? 0);
            //     $item->count_activity = ($item->auction_activity ?? 0) + ($item->pl_activity ?? 0);
            // }
            $year = $request->year;
            $data = RoadActivities::raw(function ($collection) use ($year) {
                return $collection->aggregate([
                    [
                        '$match' => [
                            'year' => (int)$year,
                        ]
                    ],
                    [
                        '$group' => [
                            '_id' => [
                                'subactivity' => '$subactivity',
                                'year' => '$year',
                                'auction_pagu' => '$auction_pagu',
                                'auction_activity' => '$auction_activity',
                                'pl_pagu' => '$pl_pagu',
                                'pl_activity' => '$pl_activity',

                            ],
                            'count_pagu' => ['$sum' => ['$add' => ['$auction_pagu', '$pl_pagu']]],
                            'count_activity' => ['$sum' => ['$add' => ['$auction_activity', '$pl_activity']]],
                        ],
                    ],
                    [
                        '$project' => [
                            '_id' => 0,
                            'subactivity' => '$_id.subactivity',
                            'year' => '$_id.year',
                            'auction_pagu' => '$_id.auction_pagu',
                            'auction_activity' => '$_id.auction_activity',
                            'pl_pagu' => '$_id.pl_pagu',
                            'pl_activity' => '$_id.pl_activity',
                            'count_pagu' => '$count_pagu',
                            'count_activity' => '$count_activity',
                        ],
                    ],
                ]);
            });

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
            $data = Excel::toArray([], $path)[0];

            if (!empty($data)) {

                $headerSkipped = false;

                foreach ($data as $index => $row) {
                    if (!$headerSkipped) {
                        $headerSkipped = true;
                        continue;
                    }

                    if ($index === 1) continue;

                    $existingRecord = RoadActivities::where('subactivity', $row[1])->where('year', $row[6])->first();

                    if ($existingRecord) {
                        // Jika sudah ada, update data
                        $existingRecord->update([
                            'auction_pagu' =>  $row[2],
                            'auction_activity' =>  $row[3],
                            'pl_pagu' =>  $row[4],
                            'pl_activity' => $row[5],
                        ]);
                    } else {
                        RoadActivities::create([
                            'subactivity' =>  $row[1],
                            'auction_pagu' =>  $row[2],
                            'auction_activity' =>  $row[3],
                            'pl_pagu' =>  $row[4],
                            'pl_activity' => $row[5],
                            'year' => $row[6],
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
