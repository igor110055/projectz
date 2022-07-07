<?php

namespace App\Services\BotServices\TradeServices;

use Illuminate\Support\Str;
use App\Services\BinanceServices;

class Components
{
    public function __construct()
    {
        $this->api = BinanceServices::api();
        $this->balances = [];
        $this->prices = [];
    }

    /**
     * check if order successfully filled or not
     */
    public function isOrderFilled(array $order)
    {
        try {
            $response = $order['status'] === 'FILLED';
        } catch(\Exception $e) {
            print($e->getMessage());
        }

        return  $response;
    }

    /**
     * Kickstart the class
     */
    public function initials()
    {
        $this->fetchPrices();
        $this->fetchBalances();
    }

    /**
     * Fetch prices from binance
     */
    public function fetchPrices()
    {
        $this->prices = $this->api->prices();
        return $this->prices;
    }

    /**
     * Fetch wallet balances of authorized binance account
     */
    public function fetchBalances()
    {
        $this->balances = $this->api->balances();
        return $this->balances;
    }

    /**
     * Get the current balance of a pair
     */
    public function getBalance(string $symbol)
    {
        $balances = $this->fetchBalances();

        if( $balances[$symbol]['available'] > "0.0")
            return $this->balances[$symbol]['available'];
        else
            throw new \Exception("Wallet has not enough balance! Cancelled.");
    }

    /**
     * Get the current price of a pair from price list
     */
    public function getPrice(string $symbol)
    {
        $prices = $this->fetchPrices();

        if(!isset($prices[$symbol]))
            throw new \Exception("Price not found for the Pair! Cancelled.");

        return $this->prices[$symbol];
    }

    /**
     * Get the quantity of what we can have with current trade balance and price
     * and size order by ratio to do not waste all our balance on one order
     */
    public function getQuantity(string $symbol, string $side = 'buy', float $ratio = 1.0)
    {
        $quantity = ( $this->getBalance( ebaq($symbol)['quote'] ) * $ratio) / $this->getPrice($symbol);

        $final = quantityFormatter($symbol, $quantity);

        return $final;
    }
}
