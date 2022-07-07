<?php

namespace App\Services\BotServices\ArbitrageBot;

use Illuminate\Support\Str;
use App\Models\ExchangeInfo;
use App\Services\BinanceServices;

class Binance {

    //private $data, $pricelist;

    public function __construct()
    {
        $this->initial = [ 'balance' => 93, 'asset' => 'USDT'];
        $this->reset = [ 'balance' => 93, 'asset' => 'USDT'];
        $this->print = new \League\CLImate\CLImate;
        $this->pricelist = BinanceServices::api()->prices();
        $this->data = ExchangeInfo::whereStatus('TRADING')->get();
    }


    public function run()
    {
        foreach($this->data as $key => $value){
            if( !in_blacklist($value->symbol) && in_array($value->symbol, array_keys($this->pricelist))){
                $path[$key] = $this->drawPath($value->symbol);
                $this->resetWallet();
            }

        }

        return $path;
    }

    /**
     * Fetch all prices from exchange
     *
     * @return void
     */
    public function fetchPrices()
    {
        $this->pricelist = BinanceServices::api()->prices();
    }

    /**
     * Reset wallet to initial values for the new checks
     *
     * @return void
     */
    public function resetWallet()
    {
        $this->initial = $this->reset;
    }

    /**
     * Draw a triangular trade path based on given symbol
     * with tokens who prices provided in the price list
     *
     * @param string $symbol
     * @return mixed
     */
    public function drawPath($symbol)
    {
        $extracted = extractBaseAndQuote($symbol);
        $initial = $this->initial['asset'];

        $stages = [
            'stageI' => $extracted['quote'].$initial,
            'stageII' => $symbol,
            'stageIII' => $extracted['base'].$initial
        ];

        foreach($stages as $key => $stage){
            if( $this->filters( $stage )){
                $data[$key] = $this->getPriceWithDetails($extracted['quote'].$initial);
                $this->initial = [
                    'asset' => $stage['asset'],
                    'balance' => $stage['quantity']
                ];
            }
            $this->resetWallet();
        }

        return $data;
    }

    /**
     * Check if pair has a price in the price list
     * and if not then check for the alternative pair
     *
     * @param array $array
     * @return array
     */
    public function getPriceWithDetails($symbol)
    {
        $available = 1 - 0.00075;
        $data = [];
        $balance = nb($this->initial['balance'] * $available);
        $price = $this->pricelist[$symbol];

        if(in_array($symbol, array_keys($this->pricelist))){ // no rotate => BTCUSDT
            $data = [
                'symbol' => $symbol,
                'balance' => $balance,
            ];
            if (Str::endsWith($symbol, $this->initial['asset'])) { // eldeki asset ile bitiyor
                $data['quantity'] = $this->quantityFormatter($symbol, $balance / $price);
                $data['asset'] = extractBaseAndQuote($symbol)['base'];
                $data['order'] = 'BUY';
            }
            else if(Str::startsWith($symbol, $this->initial['asset'])) { // eldeki asset ile başlıyor
                $data['quantity'] = $this->quantityFormatter($symbol, $balance * $price);
                $data['asset'] = extractBaseAndQuote($symbol)['quote'];
                $data['order'] = 'SELL';
            }
            else {
                $data['quantity'] = $this->quantityFormatter($symbol, $balance / $price);
                $data['asset'] = extractBaseAndQuote($symbol)['base'];
                $data['order'] = 'BUY';
            }
        }
        else if(in_array($alternative, array_keys($this->pricelist) )){ // rotated => USDTTRY
            $data = [
                'symbol' => $this->alternativeSymbol($symbol),
                'price' => nb($this->pricelist[$alternative]),
                'balance' => $balance,
            ];
            if (Str::endsWith($alternative, $this->initial['asset'])) { // eldeki asset ile bitiyor
                $data['quantity'] = $this->quantityFormatter($alternative, $balance / $price);
                $data['asset'] = extractBaseAndQuote($alternative)['base'];
                $data['order'] = 'BUY';
            }
            else if(Str::startsWith($alternative, $this->initial['asset'])) { // eldeki asset ile başlıyor
                $data['quantity'] = $this->quantityFormatter($alternative, $balance * $price);
                $data['asset'] = extractBaseAndQuote($alternative)['quote'];
                $data['order'] = 'SELL';
            }
        }

        return $data;
    }

    public function filters($value)
    {
        if( !in_blacklist($value) ||
            in_array($value, array_keys($this->pricelist))
        ) return true;
        else return false;
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
      $stepSize = $this->data->where('symbol',$symbol)->first()->filters[2]['stepSize'];
      return nb($quantity - fmod($quantity, $stepSize));
    }

}
