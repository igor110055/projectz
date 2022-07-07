<?php

namespace App\Services\BotServices\TradeServices;

use App\Services\BinanceServices;

class OrderCenter
{

    public function __construct()
    {

        $this->print = new \League\CLImate\CLImate;
        $this->stopLossRatio = 0.98;
    }

    /**
     * verilen çiftin fiyatını alıp quote cinsinden bakiyemizle sipariş adetimizi hesaplar ve sipariş verir
     * verilen siparişi kontrol eder, sipariş tamamlanamadıysa yeterli tekrar kontrol ardından yeni sipariş geçer
     * sipariş tamamlandıktan sonra stop loss koyarak görevini tamamlar
     *
     * @param string $symbol
     * @param string $side
     * @param float $ratio
     * @param bool $stoploss
     * @return mixed
     */
    public function quickOrder( string $symbol, string $side, float $ratio = 1.0, bool $stoploss = false, array $orderData = [] // only for stoploss
    ){
        $quote = $side == 'BUY' ? ebaq($symbol)['quote'] : ebaq($symbol)['base'];

        $balance = $this->getBalance($quote);

        $price = $this->getPrice($symbol);

        $quantity = $this->getQuantity($symbol, $balance, $price, $ratio, $orderData, $side, $stoploss);

        $order = $this->handleOrder($symbol, $side, $quantity, $price, $stoploss, $orderData);

        if($order['status'] !== 'FILLED' && $stoploss == false){
            $this->print->lightGreen('❎ Order did not Filled, Checking again...');
            $this->checkOrderAndWait($order, ['ratio' => $ratio, 'stoploss' => $stoploss]);
        }
        elseif($order['status'] == 'FILLED' && $stoploss == false){
            print_r($order);
            $this->logger('Limit Order Filled.',$order);
            $this->print->lightGreen('✅ Order Successfully Filled, Preparing Stoploss Order');
            $orderSide = $side == 'BUY' ? 'SELL' : 'BUY';
            $this->quickOrder($symbol, $orderSide, 1, true, $order);
        }
        elseif($order['status'] == 'FILLED' && $stoploss == true) {
            $this->logger('Successfully Completed Orders', $order);
            $this->print->lightGreen('💪 Success! Order is filled and stoploss order setted. Cheers! 🍻');
            return $order;
        }
    }

    /**
     * Handle orders by seperate them by their side and stoploss flags
     *
     * @param string $symbol
     * @param string $side
     * @param float $quantity
     * @param float $price
     * @param bool $stoploss
     * @param array $orderData
     * @return array
     */
    public function handleOrder($symbol, $side, $quantity, $price, $stoploss, $orderData = null)
    {
        if($this->isStopLossOrder($stoploss,$orderData))
        {
            $stopLossPrice = $this->getStopLossPrice($orderData['price']);
            $this->print->lightGreen('🎯 Successfully Ordered a Stop Loss Entry.');
            $order = $this->performOrder($symbol, $side, $quantity, $price, $stopLossPrice);
            $this->logger('Stop Loss Order', ['price' => $stopLossPrice, 'side' => $side]);
        }
        else
        {
            $this->print->lightGreen('🎯 Successfully Ordered a Limit Entry.');
            $order = $this->performOrder($symbol, $side, $quantity, $price, null);
            $this->logger($order);
        }

        return $order;
    }

    /**
     * Perform a order based on given parameters
     *
     * @param string $symbol
     * @param string $side
     * @param string $quantity
     * @param string $price
     * @param string $stopPrice
     * @return array
     */
    public function performOrder($symbol, $side, $quantity, $price, $stopPrice = null)
    {
        if(!is_null($stopPrice))
            $order = BinanceServices::api()->$side(
                $symbol,
                $quantity,
                $stopPrice,
                'STOP_LOSS_LIMIT',
                ['stopPrice' => $stopPrice]
            );
        else
            $order = BinanceServices::api()->$side(
                $symbol,
                $quantity,
                $price
            );

        $this->logger('Order entered.', $order);

        return $order;
    }

    /**
     * Check if order is filled and if not wait for 2 sec and check it again
     *
     * @param array $order
     * @param array $data
     * @return void
     */
    public function checkOrderAndWait($order,$data)
    {
        sleep(2);
        // check rate limiting and act only if rate is not exceeded
        if($this->repeat < 3){
            $this->repeat++;
            if(!$this->checkOrder($order)){
                $this->checkOrderAndWait($order, $data);
                $this->print->lightGreen('😥'.$this->repeat .'. try is still not success, trying again..');
            }
            elseif(!$data['stoploss'])
            {
                $this->repeat = 0;
                $this->print->lightGreen('✅ Order at '.$this->repeat.'th trying is successfully executed, stoploss order is being sent..');
                $this->quickOrder($order['symbol'], $order['side'], 1, true, $order);
                $this->lagger('Limit Order Filled'.$order);
            }
        } else {
            $this->print->lightGreen('❎ rate limit is exceeded, trying perform a new order with new values');
            BinanceServices::api()->cancelOpenOrders($order['symbol']);
            $this->lagger('Rate limit exceed, got new values to the repeat it',$order);
            $this->repeat = 0;
            $this->quickOrder($order['symbol'], $order['side'], $data['ratio'], $data['stoploss']);
        }
    }

    /**
     * Get latest stop loss price to perform a stop loss order
     *
     * @param string $symbol
     * @return string
     */
    public function getStopLossPrice($price)
    {
        return nb($price * $this->stopLossRatio);
    }

    /**
     * Determine if given order is a limit or stop loss order
     *
     * @param bool $stoplossstoploss
     * @param arrat $order
     * @return bool
     */
    public function isStopLossOrder($stoploss, $order)
    {
        return $stoploss == true && !empty($order);
    }

    /**
     * Get formatted quantity for order
     *
     * @param string $balance
     * @param string $price
     * @param string $ratio
     * @param array $order
     * @return string
     */
    public function getQuantity($symbol, $balance, $price, $ratio, $order, $side, $stoploss)
    {
        // limit order
        if($stoploss == false)
        {
            if($side == 'BUY')
                $quantity = quantityFormatter($symbol,(($balance * $ratio) / $price));
            elseif($side == 'SELL')
                $quote = ebaq($symbol)['base'];
                $quantity = BinanceServices::wallet()->balances()[$quote]['available'];
        }
        // stoploss order
        elseif($stoploss == true)
        {
            if($side == 'BUY')
                $quantity = quantityFormatter($symbol,(($balance * $ratio) / $price));
            elseif($side == 'SELL')
                $quantity = $order['quantity'];
        }

        return $quantity;
    }

    /**
     * Get current price of given symbol
     *
     * @param string $symbol
     * @return string
     */
    public function getPrice($symbol)
    {
        $price = BinanceServices::api()->price($symbol);

        if(!isset($price)) throw new \Exception("Price not found for the Pair! Cancelled.");

        return $price;
    }

    /**
     * Get Balance of the given quote
     *
     * @param string $quote
     * @return string
     */
    public function getBalance($quote)
    {
        $balance = BinanceServices::api()->balances()[$quote]['available'];

        if(!$balance > 0) throw new \Exception("Insufficient Balance! Cancelled.");

        return $balance;
    }

    /**
     * Check Order if filled
     *
     * @param array $order
     * @return mixed
     */
    public function checkOrder($order)
    {
        $orderStatus = BinanceServices::api()->orderStatus(
            $order['symbol'], $order['orderId']
        );

        return $orderStatus['status'] === 'FILLED' ? true : false;
    }

    /**
     * Cancel Open Order and Sell the Symbol
     *
     * @param string $message
     * @param array $data
     */
    public function cooSell($symbol)
    {
        BinanceServices::api()->cancelOpenOrders($symbol);

        $quote = ebaq($symbol)['quote'];
        $balance = BinanceServices::wallet()->balances()[$quote]['available'];
        $price = BinanceServices::api()->price($symbol);

        // $this->performOrder($symbol, 'BUY', $balance, $price);
        $this->performOrder($symbol, $side, $quantity, $price, $stopLossPrice);
    }

    /**
     * Put a Stop Loss Order to the market
     *
     * @param array $order
     * @param array $data
     */
    public function stopOrder($order)
    {
        $stopPrice = nb($order['price']*$this->stopLossRatio);

        $order = BinanceServices::api()->sell(
            $order['symbol'],
            $order['executedQty'],
            $stopPrice,
            'STOP_LOSS_LIMIT',
            ['stopPrice' => $stopPrice]
        );

        $this->logger('Stop Loss Order Created', $order);

        return $order;
    }

    /**
     * Log output standartazing
     *
     * @param string $action
     * @param array $order
     * @return \League\CLImate\CLImate
     */
    public function logger($action, $order)
    {
        return $this->print->out(
            "<bold>Action:</bold> <underline>{$action}</underline>\n".
            "<bold>Symbol:</bold> <underline>{$order['symbol']}</underline>\n".
            "<bold>Order:</bold> <underline>{$order['side']}</underline>\n".
            "<bold>Price:</bold> <underline>{$order['price']}</underline>\n".
            "<bold>Quantity:<bold> <underline>{$order['origQty']}</underline>\n".
            "<bold>Executed:</bold> <underline>{$order['executedQty']}</underline>\n".
            "<background_red><bold><underline>Status: {$order['status']}</underline></bold></background_red>"
        );
    }

}
