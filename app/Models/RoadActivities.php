<?php

namespace App\Models;

use App\Http\Helpers\MethodsHelpers;
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

    public function scopeFilterByField($query, $record, $value)
    {
        MethodsHelpers::filterByField($query, $record, $value);
    }
}
