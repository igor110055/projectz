<?php

namespace App\Services\BotServices\TechnicalBot;

use Illuminate\Support\Facades\DB;

class GolDeaCrosser
{

    /**
     * Simple Moving Avarage by given time range
     *
     * @param string $symbol
     * @param int $day
     * @return trader_sma
     */
    public function sma($symbol, $day)
    {
        $data = DB::table('daily_prices')->where('symbol', $symbol)
            ->orderByDesc('timestamp')->take($day)->get();

        $series = priceSeries($data)['close'];

        return trader_sma($series, $day);
    }

    /**
     * Check if some amounth of sma bigger then other amounth
     *
     * @param string $symbol
     * @return boolean
     */
    public function checkGoldenCross($symbol)
    {
        $sma200 = $this->sma($symbol, 200);
        $sma50 = $this->sma($symbol, 50);

        if (gettype($sma50) === 'array' && gettype($sma200) === 'array') {
            return array_values($sma50)[0] > array_values($sma200)[0];
        } else {
            print(' . ');
        }
    }

    /**
     * Check all pairs if there is an golden cross
     *
     * @return boolean
     */
    public function isAnyGoldenCross($list = null)
    {
        $return = [];
        if (is_null($list)) {
            foreach (\App\Models\ExchangeInfo::getPairList() as $pair) {
                if ($this->checkGoldenCross($pair)) {
                    $return[$pair] = $this->checkGoldenCross($pair);
                }
            }
        } else {
            foreach ($list as $pair) {
                if ($this->checkGoldenCross($pair)) {
                    $return[$pair] = $this->checkGoldenCross($pair);
                }
            }
        }
        return $return;
    }
}
