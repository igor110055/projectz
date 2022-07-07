<?php
namespace App\Services\BotServices\TechnicalBot;

use Illuminate\Support\Arr;

class AdxIndicator {

    /**
     * Create a new class instance
     *
     * @param string $token
     * @param string $timeframe
     * @param integer $period
     */
    public function __construct($token, $timeframe = 'hours', $period = 14)
    {
        $term = defineModelAndOrder($timeframe);

        $data = $term[0]::where('symbol',$token)->orderByDesc($term[1])->take($period*2)->get();

        $this->series = priceSeries($data,true);
        $this->period = $period;
    }

    public function run( )
    {
        $adx =  trader_adx(
            $this->series['high'],
            $this->series['low'],
            $this->series['close'],
            14
        );

        $minus_di = trader_minus_di(
            $this->series['high'],
            $this->series['low'],
            $this->series['close'],
            $this->period
        );

        $plus_di = trader_plus_di(
            $this->series['high'],
            $this->series['low'],
            $this->series['close'],
            $this->period
        );

        return ['adX' =>$adx , 'minusDi' => $minus_di, 'plusDi' => $plus_di];

    }

}

