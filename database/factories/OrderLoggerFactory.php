<?php

namespace Database\Factories;

use App\Models\ExchangeInfo;
use App\Services\BinanceServices;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderLoggerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $array = ExchangeInfo::where('symbol','like','%'.'USDT')
            ->where('symbol','not like','%'.'DOWN'.'%')
            ->where('symbol','not like','%'.'UP'.'%')
            ->where('status','TRADING')
            ->pluck('symbol')
            ->toArray();

        $symbol = $this->faker->randomElement($array);
        $price = BinanceServices::api()->price($symbol);

        $qty = $this->faker->randomFloat(
            $nbMaxDecimals = 4, $min = 0.01, $max = 30
        );

        return [
            'symbol'              => $symbol,
            'side'                => 'BUY',
            'price'               => $price,
            'stopPrice'           => null,
            'stopLimitPrice'      => null,
            'quantity'            => $qty,
            'cummulativeQuoteQty' => $price * $qty,
            'status'              => 'FILLED',
        ];

    }
}
