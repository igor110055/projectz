<?php

namespace Database\Factories;

use App\Models\Pair;
use App\Models\Balance;
use Illuminate\Database\Eloquent\Factories\Factory;

class BalanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Balance::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $token = extractBaseAndQuote(Pair::inRandomOrder()
                                    ->first()->name)['base'];
        $total = $this->faker->numberBetween(1,1000);

        return [
            'token' => $token,
            'total' => $total,
            'onOrder' => 0,
            'available' => $total,
            'estimatedPrice' => 0,
            'userId' => 1,
            'exchange' => 'Binance',
        ];
    }
}
