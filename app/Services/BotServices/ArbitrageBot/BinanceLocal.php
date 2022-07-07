<?php

namespace App\Services\BotServices\ArbitrageBot;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use App\Models\ExchangeInfo;
use App\Services\BinanceServices;
use Illuminate\Support\Facades\Log;

class BinanceLocal
{
    public $current;
    /**
     * Local Arbitrage Bot
     */
    public function __construct()
    {
        $this->current = [ 'balance' => number_format(93,8,'.',','), 'asset' => 'USDT'];
        $this->reset = [ 'balance' =>  number_format(93,8,'.',',') ];
        $this->print = new \League\CLImate\CLImate;
    }

    public static function run()
    {
        $binance = new BinanceLocal();
        return $binance->print_table();
    }

    public function bot()
    {
        while(true){
            $this->print->inline("\n".Carbon::now().
                ' Searching market for arbitrage opportunities...'."\n");

            $data = $this->filter();

            if( count($data) > 1 ){
                $choosen = Arr::first($data);
                Log::channel('bot')->info($choosen);

                foreach($choosen as $key => $value){
                    $order = $this->order($value);
                    $orderF = ['status' => 'filled']; // fake order result
                    $this->repeater($order);
                    Log::channel('bot')->info($value);
                }
            }
            else $this->print->out('there is no arbitrage opportunity,'.
                ' waiting for next scan...');
            loader(20);
        }
    }

    /**
     * Check if order is filled and if it is not filled then wait 3 seconds and check it again.
     *
     * @param array $order
     * @return boolean
     */
    public function repeater($order)
    {
        if($order['status'] != 'FILLED'){
            $this->print->cyan('Order is not filled yet, waiting to check again...');
            sleep(5);
            $this->repeater($order);
        } else {
            Log::channel('bot')->info($order);
        }
        return true;
    }

    /**
     * Put a new order
     *
     * @param array $data
     * @return array
     */
    public function order($data)
    {
        $this->print->cyan('Performing a new order for '.$data['trade']);

        $quantity = number_format($this->quantityFormatter($data['trade'],$data['balance']),8,'.','');
        Log::channel('arbitrage')->info($data);
        // print_r($data);
        return BinanceServices::api()->order(
            $data['order'],
            $data['trade'],
            $quantity,
            $data['price'],
            'LIMIT', [], true
        );
    }

    public function print_table()
    {
        foreach($this->filter() as $key => $value){
            $this->print->greenTable($value);
        }
    }

    public function filter($ratio = 0)
    {
        $data = [];
        foreach($this->return() as $key => $value){
            // if(empty($value['third']['balance']) || in_blacklist($value['second']['trade']))
            //     continue;
            if(!empty($value['third']['balance'])){
                if($value['third']['balance'] > $ratio){
                    $data[ number_format($value['third']['balance'],8,'.','')] = $value;
                }
            }
        }
        krsort($data);
        return $data;
    }

    public function binance()
    {
        $this->price_list = BinanceServices::api()->prices(); // get initial price list
        $this->print->magenta('got price list..');
        $this->data = ExchangeInfo::whereStatus('TRADING')->get(); // get exchange info to determine active pairs in the price list
        $this->print->magenta('got exchange info..');
    }

    /**
     * Perform a local arbitrage scan on the network
     *
     * @return array
     */
    public function return()
    {
        $this->binance();

        $result = [];

        foreach($this->price_list as $key => $value)
        {       
            $extracted = extractBaseAndQuote($key);
            if( $extracted['quote'] == $this->current['asset'] )
                continue;

            // $this->resetWallet();
            array_push($result, $this->calculateTrade($key));
        }

        return $result;
    }

    /**
     * Define trade paths to reach trade pair
     *
     * @param string $symbol
     * @return array
     */
    public function drawPath($symbol)
    {
        $this->resetWallet();

        $extracted = extractBaseAndQuote($symbol);

        return [
            'first' => $this->hasPrice($extracted['quote'].$this->current['asset']),
            'second' => $this->hasPrice($symbol),
            'third' => $this->hasPrice($this->current['asset'].$extracted['base'])
        ];
    }

    /**
     * Draw an triangular trading path
     *
     * @param string $symbol
     * @return boolean
     */
    public function calculateTrade($symbol)
    {
        $commission = 0.00075;
        $available = 1 - $commission;

        $path = $this->drawPath($symbol);

        foreach($path as $key => $trade)
        {
            $extracted = extractBaseAndQuote($trade['symbol']);

            if( empty($this->price_list[$trade['symbol']]) ) return;

            if($trade['rotated'] > 0){
                $balance = ($this->current['balance'] * $available) * $this->price_list[$trade['symbol']]; // - ;
                $balance = $this->quantityFormatter($trade['symbol'], $balance);
                $asset = $extracted['quote'];
                $order = 'SELL';
            }
            elseif( $trade['rotated'] > 1){
                $balance = 0;
                $asset = $extracted['base'];
            }
            else {
                $balance = ($this->current['balance'] * $available) / $this->price_list[ $trade['symbol']];
                $balance = $this->quantityFormatter($trade['symbol'], $balance);
                $asset = $extracted['base'];
                $order = 'BUY';
            }

            $result[$key] = [
                'order' => $order,
                'trade' => $trade['symbol'],
                'price' => $this->price_list[$trade['symbol']],
                'balance' => $balance,
                'asset' =>  $asset
            ];
            $this->updateCurrent($result[$key]);
        }

        // $this->print->redTable($result);
        return $result;
    }

    /**
     * Update current statue of symbolic wallet
     *
     * @param array $array
     * @return void
     */
    public function updateCurrent($array)
    {
        $this->current = [
            'balance' => $array['balance'],
            'asset' => $array['asset']
        ];
    }

    /**
     * Check if every pair in the path has a price in the price list
     *
     * @param array $array
     * @return boolean
     */
    public function hasPrices($array)
    {
        foreach($array as $key => $value)
        {
            return $this->hasPrice($value) ? $this->hasPrice($value) : false;
        }
    }

    /**
     * Check if pair has a price in the price list
     * and if not then check for the alternative pair
     *
     * @param array $array
     * @return boolean|array
     */
    public function hasPrice($symbol)
    {
        $alternative = $this->alternativeSymbol($symbol);
        if(in_array($symbol, array_keys($this->price_list))){
            return ['symbol' => $symbol, 'rotated' => 0];
        }
        else if(in_array($alternative, array_keys($this->price_list) )){
            return ['symbol' => $alternative, 'rotated' => 1];
        }
        else {
            // $this->print->bold()->backgroundRed("Market has not {$symbol} price");
            return ['symbol' => $alternative, 'rotated' => 2]; // zero price
        }
    }

    /**
     * Generate alternative pair by changing base and quote asset's places
     *
     * @param string $symbol
     * @return string
     */
    public function alternativeSymbol($symbol)
    {
        $extracted = extractBaseAndQuote($symbol);
        return $extracted['quote'].$extracted['base'];
    }

    /**
     * Reset wallet to the initial values
     *
     * @return void
     */
    public function resetWallet()
    {
        $this->current = [
            'balance' => $this->reset['balance'],
            'asset' => 'USDT'
        ];
    }

    /**
     * Check if given symbol and the current asset are both stable tokens
     * which can not convert between each other
     *
     * @param string $symbol
     * @return boolean
     */
    public function checkStableToken($symbol)
    {
        $stableTokens = ['USDT','BUSD','TUSD','USDC','USDP'];
        $tradeToken = extractBaseAndQuote($symbol)['quote'];
        return in_array($this->current['asset'],$stableTokens) == in_array($tradeToken,$stableTokens);
    }

        /**
     * Format quantity number according to stepSize
     *
     * @param string $symbol
     * @param string $quantity
     * @return float
     */
    public function quantityFormatter($symbol,$quantity)
    {
        $stepSize = $this->data->where('symbol',$symbol)->first()->filters[2]['stepSize'];
        return nb($quantity - fmod($quantity, $stepSize));
    }
}
