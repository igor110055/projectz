<?php

namespace App\Services\BotServices\TechnicalBot;

use Illuminate\Support\Arr;
use App\Models\ExchangeInfo;
use App\Models\Price\HourlyPrice;

class SupResTrader
{

    /**
     * Take 5 candles, if the candle in the middle has
     * the highest high and the 2nd and 4th candle have
     * lower highs, we have a bearish fractal or a resistance
     * level. Vice versa for a bullish fractal.
     *
     * @param array $array
     * @return boolean
     */
    public function supports($array, $price)
    {
        $supports = [];
        $key = 0;
        foreach ($array as $pair => $item) {
            if ($key >= 2 && $key < count($array) - 2) {
                if (
                    $array[$key - 2]->low > $array[$key - 1]->low &&
                    $array[$key - 1]->low > $array[$key]->low &&
                    $array[$key]->low < $array[$key + 1]->low &&
                    $array[$key + 1]->low < $array[$key + 2]->low
                ) {
                    // $supports[] = number_format($item->low, '8', '.', '');
                    $supports[] = $item->low;
                }
            }
            $key++;
        }

        sort($supports);
        // return $supports;

       return $this->arrangeRange($supports);
    }

    /**
     * Take 5 candles, if the candle in the middle has
     * the highest high and the 2nd and 4th candle have
     * lower highs, we have a bearish fractal or a resistance
     * level. Vice versa for a bullish fractal.
     *
     * @param array $array
     * @return boolean
     */
    public function resistances($array, $price)
    {
        $resistances = [];
        $key = 0;
        foreach ($array as $pair => $item) {
            if ($key >= 2 && $key < count($array) - 2) {
                if (
                    $array[$key - 2]->high < $array[$key - 1]->high &&
                    $array[$key - 1]->high < $array[$key]->high &&
                    $array[$key]->high > $array[$key + 1]->high &&
                    $array[$key + 1]->high > $array[$key + 2]->high
                ) {
                    // $resistances[] = number_format($item->high, '8', '.', '');
                    $resistances[] = $item->high;
                }
            }

            $key++;
        }
        sort($resistances);

        // return $resistances;
        return $this->arrangeRange($resistances);
    }

    /**
     * Birbirlerine çok yakın olan trend çizgilerini silerek
     * çizgilerin yoğunluğunu azaltır. Artçıl trend çizgisi ile
     * Ardıl trend çizgisi arasında %2den az olması durumunda
     * artçıl trend çizgisi silinir.
     *
     * @param array $array
     * @return array
     */
    public function arrangeRange($array)
    {
        $buffer = [];

        foreach ($array as $index => $value)
        {
            if(($index < count($array) - 1) && ($index > 0))
            {
                if( ( $value * 1.01 ) >= $array[$index+1] )
                {
                    continue;
                }
                elseif(( $value * 1.01 ) <= $array[$index-1])
                {
                    continue;
                }
                else
                {
                    $buffer[$index] = $value;
                }
            }
        }

        return $buffer;
    }

    /**
     * Return single pair hirtorical data
     *
     * @param string $period
     * @param integer $timerange
     * @param string $crypto
     * @return array
     */
    public function singlePairData($crypto = 'none', $period = 'hours', $timerange = 24)
    {
        $term = defineModelAndOrder($period);

        return $data[] = $term[0]::where('symbol', $crypto)->orderByDesc($term[1])->take($timerange)->get();
    }

    /**
     * Return all pairs historical data
     *
     * @param string $period
     * @param integer $timerange
     * @return array
     */
    public function allPairsData($period = 'hours', $timerange = 124)
    {
        foreach (\App\Models\ExchangeInfo::getPairlist() as $crypto) {
            $term = defineModelAndOrder($period);
            $data[$crypto] = $term[0]::where('symbol', $crypto)->orderByDesc($term[1])
                ->take($timerange)->get();
        }

        return $data;
    }

    /**
     * Return pair  and resistance levels
     *
     * @param string $symbol for pair or null for all tokens
     * @param string $period 'hours' for hours and 'd' for days
     * @param int $timerange how many candle should take
     * @return array
     */
    public function handle(
        string $symbol = null,
        string $period,
        int $timerange,
    ) {

        $result = [];

        $single = $this->singlePairData($symbol, $period, $timerange);
        $prices = Arr::flatten(
            \App\Models\Price\CurrentPrice::where('symbol', $symbol)
                ->select('price')->latest()->take(3)->get()->toArray()
        );

        $result['resistances'] = $this->resistances($single, $prices[0]);
        $result['supports'] = $this->supports($single, $prices[0]);

        $result['prices'] = $prices;

        return $result;
    }

    /**
     * Return if a pair break to resisdance
     *
     * @param string|null $symbol
     * @param string $period
     * @param integer $timerange
     * @param boolean $list
     * @return boolean
     */
    public function breakout( string $symbol = null, string $period, int $timerange )
    {
        $result = $this->handle($symbol, $period, $timerange);

        $closestResistance = $result['resistances'];
        $closestSupport = $result['supports'];

        if (
            $result['prices'][count($result['prices']) - 1] < $closestSupport
            && $result['prices'][0] > $closestSupport
        ) {
            $result['breakingSupport'] = true;
        }

        if (
            $result['prices'][count($result['prices']) - 1] < $closestResistance
            && $result['prices'][0] > $closestResistance
        ) {
            $result['breakingResistance'] = true;
        }

        return $result;
    }

    /**
     * Run entire class to get support and resistance
     * levels with check if there is a breakout or not
     *
     * @param string|null $symbol
     * @param string $period
     * @param int $timerange
     * @param bool $list
     *
     * @return array
     */
    public function run($symbol = null, $period = 'hours', $timerange = 72, $list = true)
    {
        $result = [];
        if (is_null($symbol) && $list) {
            foreach (ExchangeInfo::getPairList() as $pair) {
                $result[$pair] = $this->breakout($pair, 'hours', 216);
            }
        } else {
            $result[$symbol] = $this->breakout($symbol, $period, $timerange);
        }

        $return = array_filter($result, function ($item) {
            return isset($item['breakingSupport']) || isset($item['breakingResistance']);
        });

        return empty($return) ? false : $return;
    }

    public static function runForList($list, $period = 'hours', $timerange = 216)
    {
        $sr = new SupResTrader();
        $result = [];
        foreach ($list as $pair) {
            $result[$pair] = $sr->breakout($pair, 'hours', $timerange);
        }

        // $return = array_filter($result, function ($item) {
        //     return isset($item['breakingSupport']) || isset($item['breakingResistance']);
        // });

        return $result;
    }
}
