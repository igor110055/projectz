<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'symbol',
        'pairId',
        'balanceId',
        'remoteOrderId',
        'type',
        'side',
        'price',
        'origQty',
        'icebergQty',
        'executedQty',
        'cumulativeQuoteQty',
        'status',
        'isWorking',
        'timeInForce',
        'stopPrice',
        'orderTimestamp',
        'orderUpdateTimestamp',
        'orderDateTime',
        'userId',
        'exchanges'
    ];

     /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $cast = [
        'price'              => 'float',
        'origQty'            => 'float',
        'icebergQty'         => 'float',
        'executedQty'        => 'float',
        'cumulativeQuoteQty' => 'float',
        'orderDateTime'      => 'datetime',
        'isWorking'          => 'boolean'
    ];

    /**
     * Get the User that owns the Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function User()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

    /**
     * Get the Pair that using on the Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pair()
    {
        return $this->belongsTo(Pair::class, 'pairId', 'id');
    }

    /**
     * Get the Balance for the Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function balance()
    {
        return $this->belongsTo(Balance::class,'balanceId','id');
    }


}
