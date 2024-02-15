<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class ConditionRoadBridge extends Model
{
    use HasFactory;
    protected $collection = 'condition_road_bridges';
    protected $connection = 'mongodb';

    protected $fillable = [
        'id',
        'condition',
        'type',
        'unit',
        'year',
        'value',
        'filed',
        'pic',
        'created_at',
        'updated_at'
    ];
}
