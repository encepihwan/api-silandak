<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class RoadActivities extends Model
{
    use HasFactory;
    protected $collection = 'road_activities';
    protected $connection = 'mongodb';

    protected $fillable = [
        'id',
        'subactivity',
        'auction_pagu',
        'auction_activity',
        'pl_pagu',
        'pl_activity',
        'year',
        'created_at',
        'updated_at'
    ];
}
