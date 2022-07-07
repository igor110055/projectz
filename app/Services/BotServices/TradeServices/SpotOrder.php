<?php

namespace App\Services\BotServices\TradeServices;

use App\Services\BinanceServices;
use App\Services\BotServices\TradeServices\Components;
use Illuminate\Support\Str;

class SpotOrder {

    public function __construct()
    {
        $this->stopLossRatio = 0.95;
        $this->components = new Components();
        $this->print = new \League\CLImate\CLImate;
        $this->api = BinanceServices::api();
        $this->repeat = 1;
        $this->print = new \League\CLImate\CLImate;
    }

    /**
     * Put a Limit order for the given pair according to given ratio
     * and repeatedly check if the order is filled or not, if not then cancel it
     * and generate a new order with the new parameters
     */
    public function orderWithOco(string $symbol,string $side = 'buy',
                            float $ratio = 1.0, bool $stoploss = false)
    {
        $order = $this->order($symbol, $side, $ratio,"",$stoploss);

        if( $order['status'] !== 'FILLED' ){
            $this->repeatIt($order,$ratio);
        }

        $results['order'] = $order;
        $results['ocoOrder'] = $this->ocoOrder($symbol);
        //$this->stopLossOrder($symbol, $ratio);


        return $results;
    }

    /**
     * Put a stoploss order to the system
     */
    public function stopLossOrder($symbol, $ratio, $price = "")
    {
        $this->print->out($this->messages()['preparing_stop']);
        $price = empty($price) ? "" : $price;
        $order = $this->order($symbol, "sell", $ratio, $price, true);
        if($order['orderId'] > 0){
            $this->print->out($this->messages()['stop_order_placed']);
        } else {
            $this->print->backgroundMagenta()->out('order failed?');
            print_r($order);
        }
    }

    /**
     * Put a Limit order for the given pair according to given ratio
     */
    public function qOrder(string $symbol,string $side = 'buy',
                            float $ratio = 1.0, bool $stoploss = false)
    {
        $order = $this->order($symbol, $side, $ratio,"",$stoploss);

        if( $order['status'] !== 'FILLED' ){
            $this->repeatIt($order,$ratio);
        }

        return $order;
    }

    /**
     * Cancel Open Order and Put a new order with given parameters
     */
    public function cQOrderWithSl(string $symbol, string $side = 'buy',
                                float $ratio = 1.0, string $price = "",
                                bool $stoploss = false)
    {
        BinanceServices::api()->cancelOpenOrders($symbol);

        $this->qOrderWithSL($symbol,$side,$ratio,"",$stoploss);
    }

    /**
     * Cancel Open Order and Put a new order with given parameters
     */
    public function cQOrder(string $symbol, string $side = 'buy',
                                float $ratio = 1.0, string $price = "",
                                bool $stoploss = false)
    {
        BinanceServices::api()->cancelOpenOrders($symbol);

        $this->qOrder($symbol,$side,$ratio,"",$stoploss);
    }


    public function newSlOrder(string $symbol, string $price = null, float $ratio = 1.0)
    {
        try {
            $this->api->cancelOpenOrders($symbol);
        } catch(\Exception $e){
            $this->print->backgroundRed()->out($e->getMessage());
        }

        $this->print->out($this->messages()['stop_order_cancelled']);

        $qty = BinanceServices::wallet()->balances()[ebaq($symbol)['base']]['available'];

        $price = priceFormatter($symbol, $price);

        $this->api->sell($symbol,$qty, $price, 'STOP_LOSS_LIMIT',['stopPrice' => $price]);

        return $this->stopLossOrder($symbol,$ratio,$price);
    }

    /**
     * Puts a order to the market based on the parameters,
     * its icluding stoploss orders
     */
      public function order(string $symbol, string $side = "buy", float $ratio = 1.0,
                        string $price = "",bool $stoploss = false, array $order = [])
    {
        $symbol = Str::upper($symbol);
        $side = Str::lower($side);

        $price = empty($price) ? $this->components->getPrice($symbol) : $price;

        if($side == 'buy')
            $quantity = $this->components->getQuantity($symbol, $side, $ratio, $stoploss);
        elseif($side == 'sell')
            $quantity = $this->components->getBalance(ebaq($symbol)['base']);

        if($stoploss) {
            $stopPrice = priceFormatter($symbol, $price * $this->stoploss);
            $rawPrice = $this->components->prices[$symbol];
            $type = 'STOP_LOSS_LIMIT';
            try {
                $order = $this->api->$side( $symbol, $quantity, $stopPrice, $type ,['stopPrice' => $stopPrice] );
            } catch (\Throwable $th) {
                print($th->getMessage());
                return false;
            }
        } else {
            $type = 'LIMIT';
            try {
                $order = $this->api->$side($symbol, $quantity, $price);
                $this->print->out("Order: {$order['symbol']}, Side: {$order['side']}, Type: {$order['type']},".
                " Price: {$order['price']}, Quantity: {$order['origQty']} <background_magenta> Status: ".
                 " {$order['status']} </background_magenta>");
            } catch (\Throwable $th) {
                print($th->getMessage());
            }
        }

        return $order;
    }

    /**
     * Rescusive function to repeat the same check to verify
     * that command was executed successfully
     */
    public function repeatIt(array $order,float $ratio = 1.0)
    {
        $this->repeat++;
        sleep(3);

        $order = $this->api->orderStatus($order['symbol'], $order['orderId']);

        // emir doldurulamadı ve tekrar limiti aşılmadı
        if (($order['status'] != 'FILLED') && ($this->repeat < 3)){
            $this->print->out($this->messages()['checking_not_filled']);
            $this->repeatIt($order, $ratio);
        }
        // emir doldurulamadı ama tekrar limiti aşıldı
        elseif ( ($order['status'] != 'FILLED') && ($this->repeat >= 3) ){
            $this->repeat = 0;
            $this->print->out($this->messages()['checking_exceed']);
            try {
                $this->api->cancelOpenOrders($order['symbol']);
            } catch (\Throwable $th) {
                print($th->getMessage());
            }
            try {
                $this->qOrderWithSL($order['symbol'], $order['side'], $ratio);
            } catch(\Throwable $th){
                print($th->getMessage());
            }
        }
        // emir dolduruldu
        elseif( $order['status'] == 'FILLED' ){
            $this->repeat = 0;
            $this->print->out($this->messages()['checking_filled']);
            return true;
        }
    }

    public function ocoOrder(string $symbol, string $side = 'sell', float $ratio = 1.0)
    {
        $currentPrice = $this->api->prices()[$symbol];
        $quantity = quantityFormatter($symbol, $this->api->balances()[ebaq($symbol)['base']]['available'] * $ratio);
        $price = priceFormatter($symbol, $currentPrice*1.1);
        $stopLimitPrice = $stopPrice = priceFormatter($symbol, $currentPrice*$this->stopLossRatio);

        return $this->api->ocoOrder(  $side, $symbol, $quantity, $price, $stopPrice, $stopLimitPrice );
    }


    /**
     * return predefined messages for the issue
     */
    public function messages()
    {
        return [
            'checking_not_filled'       => "\t"."Checking order for ".
                "<bold>{$this->repeat}th times</bold>, Status: <background_magenta> NEW </background_magenta>",

            'checking_exceed'           => "\t"."Order Limit <bold><green>Exceeded</green></bold> ".
                "Process has <background_magenta>cancelled</background_magenta> and <background_magenta>".
                "trying new Limit Order.</background_magenta>",

            'preparing_stop'            => "Calculating and <magenta>Putting Stop Loss</magenta> Limit order",

            'checking_filled'           => 'Order <bold><magenta>Filled</magenta></bold> Successfully',

            'stop_order_placed'         => 'Stop Loss Order <bold><magenta>Placed</magenta></bold> Successfully',

            'stop_order_placed'         => 'Stop Loss Order <bold><magenta>Cancelled</magenta></bold> Successfully',
        ];
    }

}
