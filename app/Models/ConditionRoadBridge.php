<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;
use App\Http\Helpers\MethodsHelpers;

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
        'field',
        'pic',
        'created_at',
        'updated_at'
    ];

    public function scopeFilterByField($query, $record, $value)
    {
        MethodsHelpers::filterByField($query, $record, $value);
    }
}
