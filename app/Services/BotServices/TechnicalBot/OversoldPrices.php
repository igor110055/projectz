<?php

namespace App\Services\BotServices\TechnicalBot;

use App\Models\ExchangeInfo;
use Illuminate\Support\Arr;

class OversoldPrices
{
    /**
     * Return a RSI series for given price pair
     *
     * @param string $token
     * @param integer $period
     * @param string $timeframe
     * @return array
     */
    public function rsiSeries(string $token, int $period = 60, string $timeframe = 'day')
    {
        $term = defineModelAndOrder($timeframe);

        $data = $term[0]::where('symbol', $token)->orderByDesc($term[1])->take($period)->get();

        if( count($data) < $period -2 ) return $token." has not enought data to analyze";

        return new \App\Services\BotServices\TechnicalBot\StochRsi($data);
    }

    /**
     * Check if pair is over sold already
     *
     * @param string $token
     * @param integer $period
     * @param string $timeframe
     * @return boolean
     */
    public function isOverSold($series)
    {
        return (gettype($series) == 'string') ? $series : Arr::first($series->srsi[0]) < 10;
    }

    /**
     * Check if pair is over bought already
     *
     * @param object $series
     * @return boolean
     */
    public function isOverBought($series)
    {
        return (gettype($series) == 'string') ? $series : Arr::first($series->srsi[1]) > 90;
    }

    /**
     * get all list's result if there is oversold or overbought
     *
     * @param array $list
     * @param integer $period
     * @param string $timeframe
     * @return array
     */
    public function isAny($list = null, $period = 200, $timeframe = 'hours')
    {
        $pairs = $this->checkParam($list);

        foreach ($pairs as $pair) {
            $rsiSeries = $this->rsiSeries($pair,$period,$timeframe);
            if($this->isOverSold($rsiSeries)) $result[$pair]['overSold'] = true;
            if($this->isOverBought($rsiSeries)) $result[$pair]['overBought'] = true;
        }

        return isset($result) ? $result : false;
    }


    public function checkParam($list)
    {
        if(!is_null($list) && gettype($list) == 'string'){
            abort(422,"argument must be a list or null");
        }

        return is_null($list) ? ExchangeInfo::getPairList() : $list;
    }
}
