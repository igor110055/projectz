<?php

namespace App\Services;

use App\Models\Exchange;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class BinanceServices {
    // Class instance of exchange's api service
    public $api;

    protected $user;

    public $wallet, $market;
    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct()
    {
        if(app()->runningInConsole()){
            $this->exchange = Exchange::find(6);
            // $this->exchange = Exchange::find(11);
        }
        else {
            $this->exchange = Auth::user()->exchange->first();
        }

        // get currentUser api keys
        $this->api = new \Binance\API(
            // $this->exchange->key,$this->exchange->secret
            Crypt::decrypt($this->exchange->key), Crypt::decrypt($this->exchange->secret)
        );

        $this->api->useServerTime();

    }

    /**
     * Store exchang info
     *
     * @return array
     */
    public function storeExchangeInfo()
    {
        $symbols = $this->api->exchangeInfo()['symbols'];

        foreach($symbols as $key => $symbol){
            \App\Models\ExchangeInfo::create($symbol);
        }

        return $symbols;
    }

    /**
     * Get market functionss
     *
     * @return \Binance\API
     **/
    public static function api()
    {
        $binance = new BinanceServices;
        return $binance->api;
    }


    /**
     * Get market functionss
     *
     * @return \App\Services\MarketServices
     */
    public static function market()
    {
        return new \App\Services\MarketServices;
    }


    /**
     * Get wallet functions
     *
     * @return \App\Services\WalletServices
     */
    public static function wallet()
    {
        return new \App\Services\WalletServices;
    }

    /**
     * Get trade functions
     *
     * @return \App\Services\TradeServices
     */
    public static function trade()
    {
        return new \App\Services\TradeServices;
    }

    /**
     * Get order center functions
     *
     * @return \App\Services\BotServices\TradeServices\OrderCenter
     */
    public static function orderCenter()
    {
        return new \App\Services\BotServices\TradeServices\OrderCenter;
    }

    /**
     * alias
     * Get order center functions
     *
     * @return \App\Services\BotServices\TradeServices\OrderCenter
     */
    public static function oc()
    {
        return new \App\Services\BotServices\TradeServices\OrderCenter;
    }

    public static function currentBalances()
    {
        $all = BinanceServices::wallet()->balances();
        $prices = BinanceServices::api()->prices();

        foreach($all as $symbol => $detail){
            if($symbol === 'USDT'){
                $total['USDT'] = $detail['available']+$detail['onOrder'];
            } else{
                $price = $prices[$symbol.'USDT'];
                $total[$symbol] = ($price * $detail['available'])+($price*$detail['onOrder']);
            }
        }

        return $total;
    }

    public static function currentTotalBalance()
    {
        $total = 0;
        foreach(BinanceServices::currentBalances() as $key => $value){
            $total += $value;
        }

        return $total;
    }

    public static function marketBot()
    {
        $m = new \App\Services\BotServices\MarketAnalyze;

        return $m->socketConnection();
    }

}
