<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticker extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'symbol', 'open', 'close', 'low', 'high', 'volume',
        'assetVolume','baseVolume','assetBuyVolume','takerBuyVolume',
        'openTime','closeTime','pairId','code'
    ];

    /**
     * The attributes will type casting
     *
     * @var array
     */
    protected $cast = [
        'open' => 'float',
        'close' => 'float',
        'low' => 'float',
        'high' => 'float',
        'volume' => 'float',
        'assetVolume' => 'float',
        'baseVolume' => 'float',
        'assetBuyVolume' => 'float',
        'takerBuyVolume' => 'float',
    ];

   /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $datetime = [
        'created_at','updated_at','openTime','closeTime'
    ];

    /**
     * Get the Pair that owns the Ticker
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pair()
    {
        return $this->belongsTo(Pair::class,'pairId', 'id');
    }
}
