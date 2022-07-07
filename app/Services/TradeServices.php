<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\BinanceServices;
use Exception;

class TradeServices {

    public $repeat;

    public function __construct()
    { 
        $this->repeat = 0;

        $this->stopLossRatio = 0.98;

        $this->print = new \League\CLImate\CLImate;
    }

    /** Any order requires several subprocesses; before
     * send any order, we have to know price and current
     * account balance for that pair while determine the
     * pair's currency. only then we can define quantity
     * and limit for the order.
     *
     * @param array $data = [
        * 'side'   => 'BUY/SELL',
        * 'symbol' => 'BTCUSDT'
        * 'ratio'  => 1/4
        * ];
     * @return boolean
     */
    public function order(array $data = [], array $details = [])
    {
        if(empty($details)) $details = $this->getDetails($data);

        $order = $this->sendOrder( $details );
        
        print_r($order);

        return $this->repeater($details, $order);
    }

    /**
     * Repeatly check order's result and take actions
     *
     * @param $array $details
     * @param array $order
     * @return array
     */
    public function repeater($details, $order)
    {
        $counter = 1;

        while( $counter < 4 )
        {
            print("{$this->repeat}. siparişin {$counter}. kontrolü için 3 sn bekleniyor.."."\n");
            sleep(3);

            print($this->checkOrder($order));

            if( ! $this->checkOrder($order) )
            { 
                print("{$this->repeat}. siparişi {$counter}. kontrolü: siparişimiz gerçekleşmedi! "."\n");
                $counter++;
            } else {
                $this->repeat = 0;
                print("{$order['side']} işlemi gerçekleşti!"."\n");

                if($order['side'] === 'BUY')
                {
                    print("Stop emri giriliyor.."."\n");

                    $this->sendOrder(
                        [
                            'side' => $details['side'] === 'BUY' ? 'SELL' : 'BUY',
                            'symbol' => $details['symbol'],
                            'quantity' => $details['quantity'],
                            'price' => number_format($details['price']*0.99,'2','.','')
                        ],
                        'STOP_LOSS_LIMIT',
                        [ 
                            'stopPrice' => number_format($details['price']*0.989,'2','.','') 
                            ]
                    );

                    print("Stop emri tamam.."."\n");
                }

                return true;
            }
        }

        if($counter >= 4)
        {
            sleep(3); 
            BinanceServices::api()->cancelOpenOrders($details['symbol']);

            if( $this->repeat > 3 ){
                
                $this->repeat = 0;
                return $this->sendMarketOrder($details);
            } else {
                $this->repeat++;
                
                $newData = $this->newDataForOrder($details);
                $this->order([],$newData);
            }

        }
    }

    /**
     * Fetch price and balance from market, set quantity and
     * put a limit order to the sistem.
     * 
     * @param string $symbol
     * @param string $side
     * @param float $ratio
     * @param bool $stoploss
     * @return mixed  
     */
    public function quickOrder(
        string $symbol, 
        string $side, 
        float $ratio = 1.0, 
        bool $stoploss = false, 
        string $quantity = null
    ){
        $quote = ebaq($symbol)['quote'];

        $balance = BinanceServices::api()->balances()[$quote]['available'];

        if(!$balance > 0) throw new \Exception("Insufficient Balance! Cancelled.");

        $price = BinanceServices::api()->price($symbol);

        if(!isset($price)) throw new \Exception("Price not found for the Pair! Cancelled.");

        if(is_null($quantity)){
            $quantityRaw = $side == 'BUY' ? ($balance * $ratio) / $price : ($balance * $ratio) * $price;

            $quantity = quantityFormatter($symbol, $quantityRaw);
        } 
        
        if($stoploss){ 
            $this->print->lightGreen('🎈 stoploss order is being sent..');
            $price = $price * $this->stopLossRatio;
            Log::channel('bot')->info(["symbol" => $symbol,"quantity" => $quantity, "price" => $price]);
            $order = BinanceServices::api()->$side($symbol, $quantity, $price, 'STOP_LOSS_LIMIT', ['stopPrice' => $price ]);
            
        } else {
            $this->print->lightGreen('🎈 limit order is being sent.. ');
            Log::channel('bot')->info(["symbol" => $symbol,"quantity" => $quantity, "price" => $price]);
            $order = BinanceServices::api()->$side($symbol, $quantity, $price);
        }
        Log::channel($order);

        if($order['status'] !== 'FILLED' && $stoploss == false)
        {
            $this->print->lightGreen('❎ order is not filled, trying again');
            $this->checkAndWait($order, ['ratio' => $ratio, 'stoploss' => $stoploss]);
        }
        elseif($order['status'] == 'FILLED' && $stoploss == false)
        {
            $this->print->lightGreen('✅ order is filled, stoploss order is being sent..');
            $orderSide = $side == 'BUY' ? 'SELL' : 'BUY';
            $this->quickOrder($symbol, $orderSide, 1, true, $order['executedQty']);
        } 
        elseif($order['status'] == 'FILLED' && $stoploss == true) {
            $this->print->lightGreen('💪 Success! Order is filled and stoploss order setted. Cheers! 🍻');
            return $order;
        }
    }

    public function checkAndWait($order,$data)
    {
        sleep(2);
        // check rate limiting and act only if rate is not exceeded
        if($this->repeat < 3){
            $this->repeat++;
            if(!$this->checkOrder($order)){
                $this->checkAndWait($order, $data);
                $this->print->lightGreen('😥'.$this->repeat .'. try is still not success, trying again..');
            }
            elseif(!$data['stoploss']) 
            {
                $this->repeat = 0;
                $this->print->lightGreen('✅'.$this->repeat.'. is successfully executed, stoploss order is being sent..');
                $this->quickOrder($order['symbol'], $order['side'], $data['ratio'], true);
            }
        } else {
            $this->print->lightGreen('❎ rate limit is exceeded, trying perform a new order with new values');
            BinanceServices::api()->cancelOpenOrders($order['symbol']);
            $this->repeat = 0;
            $this->quickOrder($order['symbol'], $order['side'], $data['ratio'], $data['stoploss']);
        }
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
     * Renew prices based on last prices and order side
     *
     * @param array $order
     * @return array $price
     */
    public function newDataForOrder($detail)
    {
        switch ($detail['side']) {
            case 'BUY':
            $data['limitPrice'] = number_format($detail['price'] * 1.01,'2','.','');
                break;

            default:
            $data['limitPrice'] = number_format($detail['price'] * 0.99,'2','.','');
                break;
        }

        return  [
            'side'     => $detail['side'],
            'symbol'   => $detail['symbol'],
            'price'    => $data['limitPrice'],
            'quantity' => $data['limitPrice'] > 1 ?
                number_format($detail['quantity'],2,'.',''):
                number_format($detail['quantity'],4,'.',''),
            ];
    }

    /**
     * Send prepared order to the server
     *
     * @param array $details
     * @return array
     */
    public function sendOrder($details, $type = null, $flags = [])
    {
        return BinanceServices::api()->order(
            $details['side'],
            $details['symbol'],
            $details['quantity'],
            $details['price'],
            is_null($type) ? 'LIMIT' : $type,
            $flags
        );
    }

    /**
     * Send a Market order with current price
     *
     * @param array $details
     * @return array
     */
    public function sendMarketOrder($details)
    {
        if($this->repeat > 3){
            if($details['side'] === 'BUY'){
                return BinanceServices::api()->marketBuy($details['symbol'], $details['quantity'], []);
            } else {
                return BinanceServices::api()->marketSell($details['symbol'], $details['quantity'], []);
            }
        }
    }


    /**
     * Calculate quantity for the order according
     * to given price and currency or the data itself
     *
     * @param array $data = [
        *  'side' => 'BUY/SELL',
        *  'symbol' => 'BTCUSDT',
        *  'ratio' => '1/4'
        * ];
     * @return string
     */
    public function getDetails( $data )
    {
        $quote = ebaq($data['symbol'])['quote'];

        $currentBalance = floatval(
            BinanceServices::wallet()->balances()[$quote]['available']
        ) * $data['ratio'];

        if(!$currentBalance > 0){
            throw new \Exception("Insufficient Balance! Cancelled.");
            return false;
        }

        $price = floatval(BinanceServices::api()->price($data['symbol']));

        $quantity = $data['side'] == 'BUY' ? 
            floatval( $currentBalance  / $price) : 
            floatval( BinanceServices::wallet()->balances()[$quote] * $price );

        return [
            'symbol'    => $data['symbol'],
            'side'      => $data['side'],
            'quantity'  => quantityFormatter($data['symbol'], $quantity),
            'price'     => number_format($price,2,'.',''),
            // 'stopLossPrice' => number_format($price * 0.95,2,'.',''),
        ];
    }

    /**
     * Get user orders with filters for spesific token
     *
     * @param string $symbol
     * @param string $side
     * @return array
     */
    public function orders($symbol, string $side)
    {
        return array_filter(BinanceServices::api()->orders($symbol),
            function($order) use ($side){
                return $order['side'] === $side && $order['executedQty'] >0;
            }
        );
    }

    /**
     * Check if there is an active stoploss  order for given pair
     *
     * @param string $pair
     * @return boolean
     */
    public function checkStopLoss($pair)
    {
        return count( array_filter(BinanceServices::api()->openOrders(), function($orders) use ($pair){
            return  $orders['symbol'] === $pair && $orders['type'] === 'STOP_LOSS_LIMIT';
        }) ) > 0 ? true : false;
    }

    /**
    * Check if all tokens which are holding on wallet
    * has active stop loss order on the exchangeı
    *
    *  @param float $raio
    *  @return boolean
    */
    public function stopLoss2All(float $ratio = 0.98)
    {
        foreach(BinanceServices::wallet()->realBalances() as $key => $balance)
        {
            $pair = addCurrency( $key );
            if( $key !== 'USDT' ){
                $limit = number_format(BinanceServices::market()->price( $pair  ) * $ratio,'2','.','');
                $price = number_format($limit * 0.999,'2','.','');
                $flag = ["stopPrice" => $price ];

                $order[$key] = BinanceServices::api()->sell(
                    $pair,
                    number_format($balance['available'],'2','.',''),
                    $limit,
                    'STOP_LOSS_LIMIT',
                    $flag
                );
            }
        }
        return $order;
    }


    /**
     * Get current spendable balance for only specified
     * token from user account
     *
     * @param string $currency
     * @return array
     */
    public function getBalance($currency)
    {
        return BinanceServices::wallet()->balances()[$currency]['available'];
    }
}
