<?php

namespace App\Services\BotServices;

use App\Models\Price;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Services\BinanceServices;
use Illuminate\Support\Facades\Log;

/**
 * PumpDumpBot, ani fiyat değişimi sergileyen işlem çiftlerini tespit için
 * websocket ile marketi tarar ve edindiği sonuçları whitelistine göre filtreler.
 * Tespit edilen işlemin teyidi için ....
 */
class PumpDumpBot {

    /**
     * Create a new bot instance
     *
     * @param string $token
     */
    public function __construct($token)
    {
        $this->token = $token;
        // $this->order = new \App\Services\BotServices\TradeServices\Order();
        $this->api = BinanceServices::api();
        $this->candlesticks = collect(BinanceServices::api()->candlesticks($token, '1m'));
        $this->botLimit = 1;
        $this->whitelist = ['USDT'];
    }

    /**
     * is current price above bollinger's middle line
     *
     * @param string $token
     * @return PumpDumpBot
     */
    public function isAboveBband()
    {
        $series = series($this->candlesticks, true);
        $bbands = trader_bbands($series['close'],20,2,2,TRADER_MA_TYPE_SMA);
        $upper = Arr::first($bbands[0]);
        $middle = Arr::first($bbands[1]);
        $lower = Arr::first($bbands[2]);

        $current = $this->candlesticks->last()['close'];

        return $current > $middle;
    }

    /**
     * Seperate the candlesticks into two parts and check last candlestick
     * volumes are bigger then the first part. If it is then return true.
     *
     * @param integer $lastCandles
     * @return boolean
     */
    public function isGainingVolume(int $lastCandles = 5)
    {
        $count = count($this->candlesticks) - $lastCandles;
        $candles = $this->candlesticks->take($count);
        $latest = $this->candlesticks->sortByDesc('openTime')->take($lastCandles);

        $baseVolumeAll = 0;
        $tradesAll     = 0;

        foreach($candles as $key => $value){
            $baseVolumeAll += $value['baseVolume'];
            $tradesAll     += $value['trades'];
        }

        $avgBaseVolume = $baseVolumeAll / $count;
        $avgTrades     = $tradesAll / $count;

        $baseVolumeLast = 0;
        $tradesLast     = 0;

        foreach($candles as $key => $value){
            $baseVolumeLast += $value['baseVolume'];
            $tradesLast     += $value['trades'];
        }

        $avgBaseVolumeLast = $baseVolumeLast / $lastCandles;
        $avgTradesLast     = $tradesLast / $lastCandles;

        return $avgBaseVolume < $avgBaseVolumeLast && $avgTrades < $avgTradesLast;
    }

    /**
     * Check given token's volume is increasing and price is above
     * bollinger's middle line. If it is then put a buy order,
     * and make a new websocket connection just for this token to watch
     * price movements. use statik stoploss and takeprofit.
     *
     * @param string $token
     * @return boolean
     */
    public function handle()
    {
        if(!$this->checkConditions()){
            return false;
        };

        // $this->order->qOrderWithSl($this->token, 'buy', 1/1);

        Log::channel('job')->info("PumpDumpBot bought {$this->token}");

        return true;
    }

    /**
     * Check conditions for pump and dump
     *
     * @return boolean
     */
    public function checkConditions()
    {
        if (! $this->botLimit > 0){
            Log::channel('job')->warning("There are not any available bots at the moment.");
            return false;
        } 
        if(! $this->isGainingVolume() ) {
            Log::channel('job')->warning("{$this->token} has not gaining volume.");
            return false;
        }
        if(! $this->isAboveBband() ) {
            Log::channel('job')->warning("Price is not above of bollinger bands for {$this->token}");
            return false;
        }

        //$this->bot--;
        // if()
        return true;
    }
}
