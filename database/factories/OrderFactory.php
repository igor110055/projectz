<?php

namespace Database\Factories;

use App\Models\Pair;
use App\Models\Order;
use App\Models\Balance;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $pair = Pair::inRandomOrder()->first();
        $qty = $this->faker->numberBetween(1,50000);
        $price = 1;

        return [
            'symbol' => $pair->name,
            'pairId' => $pair->id,
            'balanceId' => Balance::inRandomOrder()->first()->id,
            'remoteOrderId' => Str::uuid(),
            'type' => 'limit_order',
            'side' => 'BUY',
            'price' => $price,
            'origQty' => $qty,
            'executedQty' => $qty,
            'cumulativeQuoteQty' => $price * $qty,
            'status' => 'FILLED',
            'isWorking' => false,
            'timeInForce' => true,
            'stopPrice' => 0,
            'userId' => 1,
            'exchanges' => 'Binance'

        ];
    }
}
