<?php

namespace App\Services\BotServices\TechnicalBot;

use Illuminate\Support\Arr;

class VolumePower
{
    public static function run($token, $timestamp = 'timestamp')
    {
        $data          = \App\Models\Price\HourlyPrice::where('symbol',$token)->orderByDesc($timestamp)->take(7)->get();
        $series        = priceSeries($data,true)['volume'];
        $period        = 7;
        $max           = 100;
        $currentVolume = Arr::flatten($series)[0];
        $avarage       = arrayAvarage($series, $period);

        while($currentVolume < $avarage){
            array_pop($series);
            $max     = $max - 15;
            $period  = $period - 1;

            if($period == 1) return false;

            $avarage = arrayAvarage($series, $period);
        }

        if($currentVolume > $avarage){
            return $max;
        }
    }
}
