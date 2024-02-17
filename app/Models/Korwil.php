<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;
use App\Http\Helpers\MethodsHelpers;


class Korwil extends Model
{
    use HasFactory;
    protected $collection = 'korwils';
    protected $connection = 'mongodb';

    protected $fillable = [
        'id',
        'code',
        'package',
        'package_before_refocusing',
        'package_after_refocusing',
        'pagu_after_refocusing',
        'fe',
        'contract',
        'physique_percen',
        'pho',
        'ba',
        'percentage_after_realized',
        'pagu_realiized',
        'number_of_refocusing_package',
        'pagu_refocusing',
        'type',
        'area',
        'pic',
        'month',
        'year',
        'created_at',
        'updated_at'
    ];

    public function scopeFilterByField($query, $record, $value)
    {
        MethodsHelpers::filterByField($query, $record, $value);
    }
}
