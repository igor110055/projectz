<?php

namespace App\Services\BotServices;

use App\Models\Price\TickerHighlights as TH;
use App\Services\BinanceServices;
use Illuminate\Support\Facades\Log;

class PumpDumpInvestor {

    // properties which all class functions has needs to
    public $perOrderLimit, $botLimit, $totalBalance, $bullOrBear, $counter, $allData;

    /**
     * create new service instance
     *
     * @param array $data
     */
    public function __construct($data = null)
    {
        $this->data          = $data;
        $this->botLimit      = 10;
        $this->totalBalance  = 1000;
        $this->perOrderLimit = $this->totalBalance / $this->botLimit;
        $this->stopLoss      = 1;
        $this->bullOrBear    = 0;
        $this->counter       = 0;
        $this->api           = BinanceServices::api();
        $this->log           = [];
        $this->th            = new TH;
    }

    /**
     * get  $count best candidates in a $timer min time frame for buy orders
     *
     * @return array
     */
    public function candidates(int $timer = 5, int $count = 10)
    {
        for($n=0; $n < $this->botLimit; $n++){
            $candidates = collect(TH::intersectOfWinners($n,$count));
            if( count($candidates) > $timer ) {
               return $candidates;
            }
        }
    }


    public function initData($data)
    {
        $this->log[$data['symbol']] =  $data;
        return  $data;
    }

    public static function run()
    {
        $pump = new PumpDumpInvestor();
        return $pump->marketWatcher();
    }

    /**
     * 3 işlem üst üste düşüş yönlü geçtiyse ve fiyat %0.5 geri geldiyse,
     * ya da her şart altında fiyat son fiyatından %1 geri gelirse,
     * ya da en yüksek fiyattan %2 geri geldiyse işlemi kapat
     *
     * @param array $data
     * @param array $trade
     * @return bool
     */
    public function shortStrategy($data, $trade)
    {
        // if( -3 <= $this->bullOrBear && $data["totalDiffPricePercent"] < -0.5 )
        if( $trade['price'] < ($this->log[$data['symbol']]['highestPrice']*0.995) )
        {
            if($this->log[$data['symbol']]['ordered'] === true){
                Log::channel('stderr')->info("\n\n"."Long işlemi kapatıldı, satım gerçekleştirildi."."\n\n");
                // $this->log[$data['symbol']] = null;
                $this->log[$data['symbol']] = [
                $this->log[$data['symbol']]['event']          => "SHORT",
                $this->log[$data['symbol']]['shortPrice']          => $trade['price'],
                $this->log[$data['symbol']]['shortTime']           => now()->timestamp * 1000,
                $this->log[$data['symbol']]['finalPNL']            => $this->log[$data['symbol']]['quantity'] * $trade['price'],
                $this->log[$data['symbol']]['totalDifferent'] => $data['totalDiffPricePercent'],
                $this->log[$data['symbol']]['total']         => ($this->log[$data['symbol']]['quantity'] * $trade['price']) - $this->log[$data['symbol']]['cost'],
                ];
                Log::channel('stderr')->info($this->log[$data['symbol']]);
                $this->log[$data['symbol']]['ordered'] = null;
            } else {
                Log::channel('stderr')->info("{$data['symbol']} işleminden vaz geçildi. ");
            }


            return false;
        }
        return true;
    }

    /**
     * Toplam %0.5'lik bir değişim ve 3 kez üst üste bullish işlem varsa
     * ve henüz alım yapılmadıysa, alım gerçekleştir
     *
     * @param array $data
     * @param array $trade
     * @return bool
     */
    public function longStrategy($data, $trade)
    {
        if( $data["totalDiffPricePercent"]  > 0.5 &&
        $this->bullOrBear > 3 &&
        $this->log[$data['symbol']]['ordered'] === false
        ){
            Log::channel('stderr')->info("\n\n"."Long işlemi girildi."."\n\n");
            $this->log[$data['symbol']]['event'] = 'LONG';
            $this->log[$data['symbol']]['longPrice'] = $trade['price'];
            $this->log[$data['symbol']]['longTime'] = now()->timestamp * 1000;
            $this->log[$data['symbol']]['longQuantity'] = $this->perOrderLimit / $trade['price'];
            $this->log[$data['symbol']]['longCost'] = $this->perOrderLimit * 1.025;
            $this->log[$data['symbol']]['longOrdered'] = true;

            Log::channel('stderr')->info($this->log[$data['symbol']]);
            Log::channel('stderr')->info('işlem sonrası hareketler izleniyor..');
        }
    }

    /**
     * Güncel işlemleri takip ederek yapılan işlemlerin artış mı azalış yönünde mi
     * olduğunu takip et, ayrıca daha yüksek bir fiyata erişildiyse en yüksek fiyatı güncelle
     *
     * @param array $data
     * @param array $trade
     * @return array
     */
    public function countProcess($data, $trade)
    {
        $this->counter++;
        $limitPrice = number_format($this->log[$data['symbol']]['highestPrice'] * 0.995,'8','.','');
        // if new price is bigger than oldest one then it is a bullish ticker
        if($data['lastClosePrice'] < $trade['price']){
            Log::channel('stderr')->info("{$this->counter}. fiyat değişikliği BULL fiyat: {$trade['price']} ".
                "ve değişim oranı: {$data['totalDiffPricePercent']}. highestPrice {$this->log[$data['symbol']]['highestPrice']} limitPrice ".$limitPrice); //> $limitPrice);
            $this->bullOrBear += 1;
        } else {
        // if last price is bigger than newest one then it is a bearish ticker
            Log::channel('stderr')->info("{$this->counter}. fiyat değişikliği BEAR fiyat: {$trade['price']} ".
                "ve değişim oranı: {$data['totalDiffPricePercent']}. highestPrice {$this->log[$data['symbol']]['highestPrice']} limitPrice ".$limitPrice );// > $limitPrice);
            $this->bullOrBear -= 1;
        }

        return [ $data, $trade ];
    }

    /**
     * Scan the market to found best fitting pair according to our strategy
     *
     * @return void
     */
    public function marketWatcher()
    {
        $marketActivities = new \App\Services\BotServices\MarketActivities;
        $api = BinanceServices::api();

        Log::channel('stderr')->info('Market taranıyor..');
        $endpoint = "!miniTicker@arr";

        $api->mTicker(function($api, $ticker) use($marketActivities, $endpoint){
            $data = $marketActivities->handle($ticker);

            if(!is_null($data)){
                $this->initData($data);
                $api->terminate($endpoint);
                $this->pairWatcher($data);
                return;
            }
        });

        return;
    }

    /**
     * After market scanned and decided to order pair based to collected data
     * we are watching that pair real time trade events to buy, sell or cancel to pair
     *
     * @return void
     */
    public function pairWatcher($data)
    {
        Log::channel('stderr')->info("{$data['symbol']} için hareketler izleniyor..");
        $api = BinanceServices::api();

        $api->trade($data['symbol'], function($api, $symbol, $trade) use ($data){
            $data["totalDiffPricePercent"] = percentage($trade['price'],  $data['lastClosePrice']);
            // count tickers and their directions while update prices
            [$data, $trade] = $this->countProcess($data, $trade);

            // update highest price if it has been higher price
            if( $this->log[$data['symbol']]['highestPrice'] < $trade['price']){
                $this->log[$data['symbol']]['highestPrice'] = $trade['price'];
                Log::channel('stderr')->info("\033[42m \033[30m yeni 'en yüksek fiyat' güncellemesi, yeni fiyat : {$this->log[$data['symbol']]['highestPrice']} \033[0m");
            }
            // SELL Conditions
            if($this->shortStrategy($data, $trade) === false){
                $api->terminate( strtolower($data['symbol'])."@trades" );
                Log::channel('stderr')->info($data['symbol']." işlemi kapatıldı.");
                // throw new \Exception('ClosedPosition');
                return;
            }
            // BUY Conditions
            $this->longStrategy($data, $trade);
        });
    }

    public function restOrder($side, $symbol)
    {
        \App\Services\BinanceServices::trade()->order([
            'side' => $side ,
            'symbol' => $symbol,
            'ratio' => $this->capitalForPerOrder
        ]);
    }

}
