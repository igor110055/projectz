<?php

namespace App\Services;
use App\Models\Pair;
use App\Models\Ticker;

class MarketServices {
    // binance api instance to serve class
    public $api;

    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct()
    {
        $binance = new \App\Services\BinanceServices;
        $this->api = $binance->api;
    }

    /**
     * Return fiat token currencies
     *
     * @return array
     */
    public function fiatTokens()
    {
        return [
            'USDT','BUSD','AUD','BIDR','BRL','EUR','GBP','RUB','TRY',
            'TUSD','USDC','DAI','IDRT','UAH','NGN','VAI','BVND','USDP',
        ];
    }

    /**
     * Return fiat token currencies
     *
     * @return array
     */
    public function baseTokens()
    {
        return [
            'BTC','BNB','XRP'
        ];
    }

    /**
     * Get only spesified pair's price
     *
     * @param string $pair
     * @return string
     */
    public function price($pair)
    {
        return $this->api->price($pair);
    }

    /**
     * Get the top gainers or the top losers of the last 24h
     *
     * @param string $gainersOrLosers
     * @return Illuminate\Support\Collection
     */
    public function top($gainersOrLosers = "gainers")
    {
        $criteria = $gainersOrLosers === "gainers" ? "sortByDesc" : "sortBy";

        return collect(BinanceServices::api()->prevDay())
            ->$criteria('priceChangePercent')->take(100);
    }

    /**
     * save exchange coin list to the local db
     *
     * @return void
     */
    public function copyRemotePairList()
    {
        $tokens = BinanceServices::api()->exchangeInfo()['tokens'];

        foreach($tokens as $key => $token){
            if(Pair::whereName($key)->exists()){
                echo(" {$key} already recorded."."\n");
            } else {
                echo("this is a new token {$key}, recording");
                $record = Pair::create(['name' => $key]);
                if($record) echo('. recorded'."\n");
            }
        }
    }

    public function candlestickData($symbol, $time, $record = 1000)
    {
        return BinanceServices::api()->candlesticks($symbol,$time,$record);
    }

    /**
     * get prevData for all tokens and update their
     * values in the ticker table
     */
    public function copyCandleSticks()
    {
        foreach(Pair::where()->get() as $pair){
            sleep(1);
            echo('talep göndermeden önce 1sn bekedik');
            $tickers = $this->candlestickData($pair->name, '1m');
            foreach($tickers as $ticker){
                if(Ticker::whereSymbol($pair->name)->where('openTime',$ticker['openTime'])->exists()){
                    echo('this event has already stored, getting next ticker'."\n");
                } else {
                    Ticker::insert([
                        'symbol'         => $pair->name,
                        'open'           => $ticker['open'],
                        'close'          => $ticker['close'],
                        'high'           => $ticker['high'],
                        'low'            => $ticker['low'],
                        'volume'         => $ticker['volume'],
                        'assetVolume'    => $ticker['assetVolume'],
                        'baseVolume'     => $ticker['baseVolume'],
                        'assetBuyVolume' => $ticker['assetBuyVolume'],
                        'takerBuyVolume' => $ticker['takerBuyVolume'],
                        'openTime'       => $ticker['openTime'],
                        'closeTime'      => $ticker['closeTime'],
                        'pairId'         => $pair->id,
                    ]);
                    echo("{$pair->name} çiftine ait {$ticker['openTime']} işlemi kayıt edildi."."\n");
                }
            }
        }
    }

    public function copyMiniTickersWithWebsocket()
    {
        $callback = function($api, $symbol, $result){
            echo($result['symbol'."\n"]);
        };

        return BinanceServices::api()->testMiniTicker($callback);
    }
}

