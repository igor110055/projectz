<?php
namespace App\Services\BotServices\TechnicalBot;

use App\Models\ExchangeInfo;

class BbIndicator
{
    public function run($token, $timeframe = 'hours' , $period = 14)
    {
        $term = defineModelAndOrder($timeframe);

        $data = $term[0]::where('symbol',$token)->orderByDesc($term[1])->take($period+1)->get();

        $series = priceSeries($data,true);

        $data = trader_bbands($series['close'], $period, 2, 2, TRADER_MA_TYPE_SMA);

        $bb['top'] = $data[0][$period-1];
        $bb['middle'] = $data[1][$period-1];
        $bb['lower'] = $data[2][$period-1];

        return $bb;
    }

    public function accumilations($list = [], $timeframe = "hours", $period = 14)
    {
        $items = empty($list) ? ExchangeInfo::getPairList() : $list;

        foreach($items as $key => $value){
            $term = defineModelAndOrder($timeframe);

            $collection = $term[0]::where('symbol',$value)->orderByDesc($term[1])->take($period+2)->get();

            $priceSeries = priceSeries($collection)['close'];

            // return $priceSeries;
            $data[$value] = trader_bbands($priceSeries['close'], $period, 2, 2, TRADER_MA_TYPE_SMA);

            if($data[$value][0][$period-1] == 0 || $data[$value][2][$period-1] == 0) continue;
            // return $data;
            $ratio = $data[$value][0][$period-1] / $data[$value][2][$period-1];

            if($ratio < 1.5 && $data[$value]){
                $return[$value] = true;
            }

        }
        return $return;
    }
}
