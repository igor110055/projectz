<?php

namespace App\Models\Price;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'price',
        'timestamp',
        'pair_id',
        'open',
        'high',
        'low',
        'volume',
        'quoteVolume',
        'count',
        'priceChange',
        'priceChangePercent',
        'weightedAvgPrice',
        'lastQty'
    ];

    protected $dates = [
        'created_at','updated_at'
    ];

    protected $appends = [
        'event_time'
    ];

    public function getEventTimeAttribute()
    {
        return createFromTimestamp($this->timestamp)->format('Y-m-d H:m:i');
    }
}
