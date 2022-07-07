<?php

namespace App\Services\BotServices\TechnicalBot;

use Illuminate\Support\Arr;

class MovingAvarages {

    /**
     * Object constructor diffirent types of moving avarages
     *
     * @param string $token
     * @param string $timeframe 'hours','days','current'
     * @param integer $period   20,60,100 etc
     */
    public function __construct($token, $timeframe = "hours")
    {
        $term = defineModelAndOrder($timeframe);

        $collection = $term[0]::where('symbol', $token)->orderByDesc($term[1])->take(60)->get();

        $this->series = priceSeries($collection);
    }

    /**
     * Return
     *
     * @param array $period
     * @return integer
     */
    public function ema($period)
    {
        return Arr::first(trader_ema($this->series['close'], $period));
    }

    /**
     * Check for given slow ema is bigger than fast ema
     *
     * @param integer $slow
     * @param integer $fast
     * @return boolean
     */
    public function EMovingAvarages($slow = 10, $fast = 20)
    {
        $slow = $this->ema($slow);

        $fast = $this->ema($fast);

        return $slow > $fast;

    }
}
