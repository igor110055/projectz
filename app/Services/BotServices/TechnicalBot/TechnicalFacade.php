<?php

namespace App\Services\BotServices\TechnicalBot;

use App\Services\BotServices\TechnicalBot\AroonIndicator;
use App\Services\BotServices\TechnicalBot\ConsolidatingTrader;
use App\Services\BotServices\TechnicalBot\SupResTrader;
use Illuminate\Support\Facades\Facade;

class TechnicalFacade extends Facade
{
    /**
     * Run consolidating screener
     *
     * @return App\Services\BotServices\TechnicalBot\ConsolidatingTrader $consolidatingTrader
     */
    public static function consolidating($daily = false, $percent = 3, $list = null)
    {
        $con = new ConsolidatingTrader();

        return $con->run($daily, $percent, $list);
    }

    /**
     * Return pair price details for the given period
     *
     * @param string $period 'd' for days 'h' for hours
     * @param integer $timerange
     * @param bool $list - requested for a list or single pair
     * @param string $symbol
     *
     * @return App\Services\BotServices\TechnicalBot\SupResTrader $supres;
     */
    public static function breakouts(
        string | null $symbol = null,
        string $period = 'h',
        int $timerange = 200,
        bool $list = true
    ) {
        $supres = new SupResTrader();

        return $supres->run($symbol, $period, $timerange, $list);
    }

    /**
     * Brings Oversold class which including few methods like
     * rsiSeries($pair,$period = 200,$timeframe="day/hour")
     * isOverSold($pair,$period = 200,$timeframe="day/hour")
     * isOverBough($pair,$period = 200,$timeframe="day/hour")
     * isAny()
     *
     * @return App\Services\BotServices\TechnicalBot\OversoldPrices
     */
    public static function overSoldOrOverBought()
    {
        $isOverSold = new \App\Services\BotServices\TechnicalBot\OversoldPrices();

        return $isOverSold->isAny();
    }

    /**
     * Run a pump dump investor for the general market
     *
     * @return \App\Services\BotServices\PumpDumpInvestor
     */
    public static function pumpDumpInvestor()
    {
        return \App\Services\BotServices\PumpDumpInvestor::run();
    }

    /**
     * Run a Golden Cross recognise algorithm
     *
     * @param GolDeaCrosser::class
     */
    public static function goldenCrosses()
    {
        $gd = new \App\Services\BotServices\TechnicalBot\GolDeaCrosser;

        return $gd->isAnyGoldenCross();
    }

    /**
     * Run a candle stick pattern recognise algorithm
     *
     * @param PatterRecogniser::class
     */
    public static function patternRecogniser($token)
    {
        $col = \App\Models\Price\HourlyPrice::where('symbol', $token)->get();

        $pr = new \App\Services\BotServices\TechnicalBot\PatternRecogniser($col);

        return $pr->__init();
    }

    /**
     * Return an aroon indicator
     *
     * @param string|null $token
     * @param string $timeframe
     * @param integer $period
     * @return \App\Services\TechnicalBot\AroonIndicator
     */
    public static function aroon($token = null, $timeframe = 'hours', $period = 25)
    {
        return new AroonIndicator($token,$timeframe,$period);
    }

    /**
     * Return a Moving agarages indicator
     *
     * @param string $pair
     * @param string $timeframe
     * @return \App\Services\BotServices\TechnicalBot\MovingAvarages
     */
    public static function ema($pair, $timeframe)
    {
        return new \App\Services\BotServices\TechnicalBot\MovingAvarages($pair, $timeframe);
    }

    public static function binanceArbitrage()
    {
        return new \App\Services\BotServices\ArbitrageBot\Binance();
        
    }

    protected static function getFacadeAccessor()
    {
        return 'Technical';
    }
}
