<?php
namespace App\Services\BotServices\TechnicalBot;

use Illuminate\Support\Arr;

class AroonIndicator {

    /**
     * Create a new class instance
     *
     * @param string $token
     * @param string $timeframe
     * @param integer $period
     */
    public function __construct($token, $timeframe = 'hours', $period = 25)
    {
        $term = defineModelAndOrder($timeframe);

        $data = $term[0]::where('symbol',$token)->orderByDesc($term[1])->take($period+2)->get();

        $this->series = priceSeries($data,true);

        $this->period = $period;

        $this->run();

    }

    public function run(){
        $aroon = trader_aroon($this->series['high'], $this->series['low'], $this->period);
        $high = $aroon[0][25];
        $low = $aroon[1][25];

        if($high > $low) {
            $trend = 'bullish';
        } else {
            $trend = 'bearish';
        }

        return [ 'high' => $high , 'low' => $low, 'trend' => $trend ];
    }
    /**
     * Return Aroon indicator upper bant value
     *
     * @param integer $period
     * @return integer
     */
    public function aroonUp($period)
    {
        return (($period + Arr::first(trader_maxindex($this->series['high'],$period))) / $period ) * 100;
    }

    /**
     * Return Aroon indicator down bant value
     *
     * @param integer $period
     * @return integer
     */
    public function aroonDown($period)
    {
        return (($period + Arr::first(trader_minindex($this->series['low'],$period))) / $period ) * 100;
    }

    /**
     * Run indicator
     *
     * @return array
     */
    public function run2()
    {
        $up = $this->aroonUp($this->period);

        $down = $this->aroonDown($this->period);

        $type = $up > $down ? 'bullish' : 'bearish';

        return [ 'up' => $up, 'down' => $down, 'type' => $type  ];
    }
}
