<?php

namespace App\Services\BotServices\TechnicalBot;

use App\Models\Price;
use App\Models\ExchangeInfo;
use App\Models\Price\DailyPrice;
use App\Models\Price\HourlyPrice;

class ConsolidatingTrader
{

    /**
     * because of price stuck and moves in a very tight area
     * during consolidating perods, simple checking by max and
     * min values we are defining the if coin consolidating or not
     *
     * @param array $series
     * @return boolean
     */
    public function isConsolidating($series, $percent = null)
    {
        $percentage = is_null($percent) ? 2 : $percent;

        try {
            
            $min = min($series[2]);
            $max = max($series[1]);
            return $min > ($max * ((100 - $percentage) / 100));

        } catch (\Throwable$th) {
            echo ($th->getMessage());
        }
    }

    /**
     * Loop over all pairs and take last 15 days candlesticks to analyse
     * and reformat all the collection as keeps all the price inside one array
     * then check they max and mix to define is_consolidating?
     *
     * @return array
     */
    public function run($daily = false, $percent = 3, $list = null)
    {
        $consolidatings = [];

        // $arr = is_null($list) ? ExchangeInfo::getPairList() : $list;
        $arr = is_null($list) ? ExchangeInfo::getUsdtPairs() : $list;

        foreach ($arr as $pair) {
            $collection = $daily ?
            Price::where('symbol', $pair)->where('timeframe','1d')->orderByDesc('closeTime')->take(15)->get() :
            Price::where('symbol', $pair)->where('timeframe','1h')->orderByDesc('closeTime')->take(15)->get();
            // DailyPrice::where('symbol', $pair)->orderByDesc('timestamp')->take(7)->get() :
            // HourlyPrice::where('symbol', $pair)->orderByDesc('timestamp')->take(24)->get();
            // return array_merge(priceSeries($collection), ['symbol' => $pair ]);
            $series = series($collection, true);

            try 
            {
                if (isset($series['low']) && gettype($series['low'] == 'array')) {
                    $consolidatings[$pair] = $this->isConsolidating($series, $percent);
                }
            } 
            catch (\Throwable$th) {
                print($th->getMessage());
            }
        }

        if (is_null($consolidatings)) {
            return false;
        }
 
        return $consolidatings;
    }
}
