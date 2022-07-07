<?php

namespace App\Services\BotServices\ArbitrageBot;

use Illuminate\Support\Str;
use App\Models\ExchangeInfo;
use App\Services\BinanceServices;

class BinanceFinal
{
    /**
     * Lokal arbitraj algortiması,
     * [+] exchangeInfo 'dan aktif çiftleri ve borsadan güncel fiyatları alıyoruz.
     * [+] exchangeInfo dan gelen çiftler üzerinde döngü kurup işlem çiftleri ile başlangıç
     *  assetimizi harmanlayarak arbitraj yollarını oluşturuyoruz .
     * [+] oluşturduğumuz bu yolların gerçekte olup olmadığını kontrol için fiyat listesiyle
     * karşılaştırıyor, olmayan çiftlerin tersini deniyor, ve yine yoksa pas geçiyoruz.
     * [+] varlıkları teyitli yeni yol ve çiftlerin karalistede ya da elimizdeki asset
     * tipinde olmadığını kontrol ediyoruz
     * 5. teyitlerden başarılı çıkan işlemler için siparişler oluşturuyoruz.
     * 6. oluşturulan siparişlerin tamamlanıp tamamlanmadığını kontrol ediyor,
     * tamamlandıysa yeni işleme geçiyoruz.
     */

    public function __construct()
    {
        $this->initial = [ 'balance' => 100, 'asset' => 'USDT']; 
        $this->print = new \League\CLImate\CLImate;
        $this->priceList = [];
        $this->data = ExchangeInfo::whereStatus('TRADING')->get();
        $this->fetchPrices();
    }

    public function bot()
    {
        $data = $this->run();
        if($data->first()['third']['quantity'] > ($this->initial['balance']*1.005)) {
            $this->print->out('Arbitraj işlemi bulundu. Son Kontrol yapılıyor..');
            // $this->print->greenTable($data->first());

            foreach($data->first() as $key => $value){
                $price = BinanceServices::api()->price($value['symbol']);
                $quantity = $value['order'] == 'BUY' ? 
                    $value['balance'] / $price : $value['balance'] * $price;
                $quantity = $this->quantityFormatter($value['symbol'], $quantity);
                $order = BinanceServices::api()->order(
                    $value['order'],
                    $value['symbol'],
                    $quantity,
                    $price, 
                    'LIMIT',[]
                );
                $this->print->out($price, $quantity);
                if($order['status'] != 'FILLED'){
                    $this->repeater($value['symbol']);
                } else {
                    $this->print->out('Sipariş tamamlandı.');
                }
            }
        } else {
            $this->fetchPrices();
            $this->print->out('Arbitraj işlemi bulunamadı.. en iyi fiyat '.$data->first()['third']['quantity']);
            sleep(10);
            $this->bot();
        }
        return $data->first()['third']['quantity'];
    }

    public function repeater($symbol)
    {
        $order = BinanceServices::api()->openOrders($symbol)[0];
        if($order['status'] != 'FILLED'){
            $this->print->cyan('Sipariş tamamlanamadı, yeniden kontrol için bekleniyor...');
            sleep(5);
            $this->repeater($symbol);
        } else {
            Log::channel('bot')->info($order);
        }
        return true;
    }

    /**
     * Run scanner
     *
     * @return void
     */
    public function run()
    {
        
        foreach($this->data as $key => $value){ 
            $groups[] = $this->path($value->symbol); 
        }
        $data = [];
        foreach($groups as $key => $symbol){
            if(!empty($symbol)){ 
                $result = $this->calculate($symbol); 

                if($result['third']['quantity'] > $this->initial['balance']){
                    $this->print->greenTable($result);
                    $data[] = $result;
                }
            }
        } 

        // return collect($data)->sortByDesc('third.quantity');
    }

    /**
     * Produces a line between pairs to accomplish a
     * triangular arbitrage trade
     *2
     * @return void
     */
    public function path($value)
    {
        if(in_blacklist($value) || !in_array($value, array_keys($this->priceList)))
            return;

        $base = extractBaseAndQuote($value)['base'];
        $quote = extractBaseAndQuote($value)['quote'];

        if($quote == $this->initial['asset']) return;

        $first = $quote.$this->initial['asset'];
        $second = $value;
        $third = $base.$this->initial['asset']; 
        // $third = $base.'USDT'; 

        if( $this->filterPriceList($first) && 
            $this->filterPriceList($second) &&
            $this->filterPriceList($third)
        ){
            return [
                'first' => $first, 
                'second' => $second,
                'third' => $third
            ];
        }   
    
    }

    /**
     * Check if given symbol has the price in the price list and if not then
     * change it with alternative one and prepare trade data according to it
     *
     * @param array $array
     * @return boolean
     */
    public function calculate($group, $p = null)
    { 
        $initial = $this->initial;
        $data = [];
        foreach($group as $key => $value){
            $balance = floatval($initial['balance']);
            if(is_null($p)) $price = floatval($this->priceList[$value]);
            else $price = $p;
            if(Str::startsWith($value, $initial['asset'])){
                $quantity = $balance * $price;
                $asset = ebaq($value)['quote'];
                $order = 'SELL';
            }
            elseif(Str::endsWith($value, $initial['asset'])){
                $quantity = $balance / $price;
                $asset = ebaq($value)['base'];
                $order = 'BUY';
            } 

            $quantity = $this->quantityFormatter($value, floatval($quantity)); 

            $data[$key] = [
                'symbol' => $value,
                'price' => nb($price),
                'balance' => $balance,
                'quantity' => $quantity,
                'order' => $order,
                'asset' => $asset,
            ];

            $initial = [
                'balance' => $data[$key]['quantity'],
                'asset' => $data[$key]['asset'],
            ];
        } 

        return $data;
    }
 
    /**
     * Format quantity number according to stepSize
     *
     * @param string $symbol
     * @param string $quantity
     * @return float
     */
    public function quantityFormatter($symbol, $quantity)
    {
        $stepSize = ExchangeInfo::where('symbol',$symbol)
            ->first()->filters[2]['stepSize'];

        return nb($quantity - fmod($quantity, $stepSize));
    }

    /**
     * Fetch prices from remote source
     *
     * @return void
     */
    public function fetchPrices()
    {
        $this->priceList = BinanceServices::api()->prices();
    } 

    /**
     * Filter data according to price list
     *
     * @param string $symbols
     * @return boolean
     */
    public function filterPriceList($symbol)
    {
        return in_array($symbol, array_keys($this->priceList)); // return true if in priceList -> needs true
    } 
}
