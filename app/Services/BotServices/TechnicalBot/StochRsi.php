<?php

namespace App\Services\BotServices\TechnicalBot;

class StochRsi
{
    /**
     * Return a StochRSI dual array
     *
     * @param string $token which will be use its historical data
     * @param string $column which column will use to produce rsi
     * @return App\Services\BotServices\TechnicalBot\StochRsi
     */
    public function __construct($data, $column = 'high')
    {
        $series = priceSeries($data, true);

        $rsi = trader_rsi($series[$column], 14);

        $this->srsi = trader_stoch($rsi, $rsi, $rsi, 14, 3, TRADER_MA_TYPE_SMA, 3, TRADER_MA_TYPE_SMA);

        return $this->srsi;
    }
}
