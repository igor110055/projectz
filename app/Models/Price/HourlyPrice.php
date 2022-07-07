<?php

namespace App\Models\Price;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HourlyPrice extends Model
{

    protected $fillable = [
        'symbol',
        'price',
        'timestamp',
        'pair_id',
        'open',
        'high',
        'low',
        'volume',
        'count',
    ];

    protected $cast = [
        'price' => 'float',
        'open' => 'float',
        'high' => 'float',
        'low' => 'float',
        'timestamp' => 'date'
    ];


    protected $dates = [
        'created_at','updated_at','timestamp'
    ];

}
