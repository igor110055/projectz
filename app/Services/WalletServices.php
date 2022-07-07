<?php

namespace App\Services;

use App\Models\Balance;
use App\Services\BinanceServices;
use App\Http\Controllers\OrderController;

class WalletServices {
    // binance api instance to serve class
    public $api;

    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct()
    {
        $binance = new BinanceServices;
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
     * ! main class to use on users login !
     * TODO: this method must test with real balance because
     * TODO: test tokens has not a history to collected
     * Check if current balances and their orders saved
     * and if not then save them to the db;
     *
     * @param boolean $save
     * @return boolean
     */
    public function balanceDetails(bool $save = true )
    {
        $balances = $this->summarize($save);
        foreach($balances as $key => $detail){
            if(!Balance::whereToken($key)->whereTotal($detail['totalQty'])->exists()){
                $this->saveBalanceRecord($detail);
            }
        }
        return $balances;
    }

    public function filledBuyOrders($symbol)
    {
        // if($symbol !== 'USDT'){
        if(! in_array($symbol , $this->fiatTokens())){
            return array_filter(BinanceServices::api()->orders(addCurrency($symbol)),
                function($order){
                    return $order['side'] === 'BUY' && $order['status'] === 'FILLED';
            });
        }
    }

    /**
     * Get balances which bigger than zero
     *
     * @return array
     */
    public  function balances()
    {
        $balances = array_filter(BinanceServices::api()->balances(), function($balance){
            return $balance['available'] > 0 || $balance['onOrder'] > 0;
        });

        foreach($balances as $key => $value){
            $balances[$key]['symbol'] = $key;
        }

        return $balances;
    }

    /**
    * Get balances which has amounts above the filters
    * actually only these type can uses for the orders
    *
    * @return array
    * */
    public function realBalances()
    {
        foreach($this->balances() as $key => $balance)
        {
            if( ! in_array($balance['symbol'], $this->fiatTokens()) )
            {
                try {
                    $price = BinanceServices::market()->price(addCurrency($balance['symbol']));
                } catch (\Exception $e) {
                    $returned = getMessageFromException($e, $balance['symbol']);
                    return $returned['title']." : ".$returned['data'];;
                }

                $totalItem = $balance['available'] + $balance['onOrder'];

                if($price * $totalItem > 10){
                    $balance['price'] = $price;
                    $result[$key] = $balance;
                }
            } else {
                $result[$key]['available'] = $balance['available'];
                $result[$key]['onOrder'] = $balance['onOrder'];
            }
        }

        return $result;
    }

    /**
     * Get some useful information from balances with the orders
     *
     * @param bool $save - Should save the record? -
     * @return array
     */
    public function summarize(bool $save = true)
    {
        foreach($this->realBalances() as $key => $balance){
            $totalQty = $balance['available'] + $balance['onOrder'];
            $record = Balance::whereToken($key)->whereTotal($totalQty)->exists();

            if(  ! in_array($key, $this->fiatTokens()) && !$record ){
                $result[$key] = $this->detailInfo($key, $balance, $totalQty, $save);
                if($save){
                    $this->saveBalanceRecord($result[$key]);
                }
            }

            if( in_array($key, $this->fiatTokens()) && !$record ) {
                $result[$key] = [
                    'symbol'       => $key,
                    'qty'          => $balance['available'] + $balance['onOrder'],
                    'totalQty'     => $balance['available'] + $balance['onOrder'],
                    'availableQty' => $balance['available'],
                    'onOrderQty'   => $balance['onOrder'],
                    'estimatedPrice' => '0'
                ];
                if($save){
                    $this->saveBalanceRecord($result[$key]);
                }
            }

            if($record) {
                $result[$key] = Balance::whereToken($key)->whereTotal($totalQty)
                    ->latest()->first();
            }

        }

        return $result;
    }

    /**
     * Get orders that makes up the current balance with their quantities
     *
     * @param string $symbol
     * @param string $side
     * @return array
     */
    public function limitOrdersWithQty( $symbol, $limit )
    {
        $total = 0;
        $orders = [];

        foreach(collect($this->filledBuyOrders($symbol))->sortByDesc('time') as $key => $order){
            if(($total + $order['executedQty']) <= $limit ){
                $orders[] = $order;
                $total += $order['executedQty'];
            }
        }

        return $orders;
    }

    /**
     * Get Balance Detail Information
     *
     * @param string $key
     * @param array $balance
     * @param string $totalQty
     * @param bool $save
     * @return array
     */
    public function detailInfo($key, $balance, $totalQty,$save)
    {
        $data[$key]['qty'] = $data[$key]['quoteQty'] = $data[$key]['price'] = 0;

        foreach($this->limitOrdersWithQty($key, $totalQty) as $index => $order){
            $data[$key]['qty'] += $order['executedQty'];
            $data[$key]['quoteQty'] += $order['cummulativeQuoteQty'];
            $data[$key]['orderIds'][] = $order['orderId'];
            $data[$key]['orders'][] = $order;
        }
        $data[$key]['currentQuoteQty'] = $balance['price'] * $data[$key]['qty'];

        if($data[$key]['qty'] > 0 ){
            $avaragePrice = number_format($data[$key]['quoteQty'] / $data[$key]['qty'],8,'.','');

            return [
                'symbol'          => $key,
                'totalQty'        => $data[$key]['qty'],
                'availableQty'    => $balance['available'],
                    'onOrderQty'      => $balance['onOrder'],
                'quoteQty'        => $data[$key]['quoteQty'],
                'currentQuoteQty' => $data[$key]['qty'] * $balance['price'],
                'avaragePrice'    => $avaragePrice,
                'currentPrice'    => $balance['price'],
                'totalPNLFiat'    => $data[$key]['currentQuoteQty'] - $data[$key]['quoteQty'],
                'totalPNL'        => percentage( $balance['price'] , $avaragePrice),
                'orders'          => $data[$key]['orders'],
                'orderIds'        => $data[$key]['orderIds']
            ];
        } else {
            return $data;
        }
    }

    /**
     * Save given balance to database
     *
     * @param array $detail
     * @return void
     */
    public function saveBalanceRecord(array $detail)
    {

        $estimatedPrice = ! in_array($detail['symbol'], $this->fiatTokens()) ? $detail['avaragePrice'] : "0";
        $recordId = \App\Models\Balance::create([
            'total'          => $detail['totalQty'],
            'available'      => $detail['availableQty'],
            'onOrder'        => $detail['onOrderQty'],
            'estimatedPrice' => $estimatedPrice,
            'token'          => $detail['symbol'],
            'userId'         => 2
        ])->id;

        return $this->saveOrderRecords($detail,$recordId);
    }

    /**
     * Save order log record which belongs to balance
     *
     * @param array $balanceDetails
     * @param array $balanceRecordId
     * @return array
     */
    public function saveOrderRecords(array $balanceDetails, string $balanceRecordId)
    {
        $orderController = new OrderController;
        if(!empty($balanceDetails['orders'])){
            foreach($balanceDetails['orders'] as $key => $order){
                $order['balanceId'] = $balanceRecordId;
                $orderController->store($order);
            }
        }

        return  true;
    }
}
