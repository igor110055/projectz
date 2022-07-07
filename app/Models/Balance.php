<?php

namespace App\Models;

use App\Services\BinanceServices;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Throwable;

class Balance extends Model
{
    use HasFactory;

    public $prices = [];

    protected $table = "balances";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token',
        'total',
        'onOrder',
        'available',
        'estimatedPrice',
        'userId',
        'exchange',
        'orderIds'
    ];

     /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $cast = [
        'total'          => 'float',
        'available'      => 'float',
        'onOrder'        => 'float',
        'estimatedPrice' => 'float',
        'orderIds'       => 'json'
    ];

    /**
     * The datetime attributes
     *
     * @var array
     */
    protected $date = [
        'created_at','deleted_at','happenedAt'
    ];


    /**
     * Get the User that owns the Balance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

    /**
     * Get all of the orders for the Balance
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class,'balanceId','id');
    }

    /**
     * Get asset's currently market price from exchange
     *
     * @return string
     */
    public function getCurrentPriceAttribute(){
        throw_if( in_array( $this->token, fiatTokenList() ), "choosen asset is a stabil token", $this->token);

        return \App\Models\Price\CurrentPrice::where('symbol',$this->token.$this->user->quote_preference)
            ->latest()->first()->price;
        //BinanceServices::api()->price($this->token.$this->user->quote_preference);
    }

    /**
     * Get asset's current value by market price from exchange
     *
     * @return string
     */
    public function getCurrentValueAttribute(){
        throw_if( in_array( $this->token, fiatTokenList() ), "choosen asset is a stabil token", $this->token);

        return $this->current_price * $this->total;
    }

    /**
     * Return asset's realized cost
     *
     * @return int
     */
    public function getTotalCostAttribute()
    {
        $cost = $quantity = 0;
        foreach($this->token_orders['orders'] as $order)
        {
            if($order['quantity'] + $quantity <= $this->total){
                $quantity += $order['quantity'];

                $cost += $order['cumulative'];
            }
        }

        return $cost;
    }

    /**
     * Return asset's profit value
     *
     * @return string
     */
    public function getTotalRevenueAttribute()
    {
        return ($this->current_price * $this->total) - $this->total_cost;
    }

    /**
     * Return token avarage cost
     *
     * @return string
     */
    public function getAvarageCostAttribute()
    {
        return $this->total_cost / $this->total;
    }

    /**
     * Return asset's realized cost
     *
     * @return int
     */
    public function getTokenOrdersAttribute()
    {
        $qty = $timestamp = $index = 0;
        $orders = [];
        foreach($this->orders()->latest()->get() as $order)
        {
            if( $qty + $order->origQty <= $this->total ){
                $timestamp += $order->orderTimestamp;
                $qty += $order->origQty;
                $index += 1;
                $orders['orders'][] = [
                    'date'       => $order->orderDateTime,
                    'timestamp'  => $order->orderTimestamp,
                    'price'      => $order->price,
                    'quantity'   => $order->origQty,
                    'cumulative' => $order->cumulativeQuoteQty,
                ];
            }
        }
        $days = $timestamp / $index;
        $orders['avarageDays'] = createFromTimestamp($days)->diffInDays(now());

        return $orders;
    }

}
