<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;

    /**
     * Fields that can be mass assigned.
     *
     * @var array
     */
    protected $fillable = [
        'symbol',
        'timeframe',
        'open',
        'high',
        'low',
        'close',
        'openTime',
        'closeTime',
        'assetVolume',
        'baseVolume',
        'trades',
        'assetBuyVolume',
        'takerBuyVolume',
        'ignored',
        'created_at',
    ];

    /**
     * Fields which will type casting. 
     * 
     * @var array
     */
    protected $cast = [
        'open'           => 'float',
        'high'           => 'float',
        'low'            => 'float',
        'close'          => 'float',
        'assetVolume'    => 'float',
        'baseVolume'     => 'float',
        'assetBuyVolume' => 'float',
        'takerBuyVolume' => 'float',
    ];
}
