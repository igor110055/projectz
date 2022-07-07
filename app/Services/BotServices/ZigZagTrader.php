<?php

namespace App\Services\BotServices;

use App\Services\BinanceServices;

class ZigZagTrader
{

    /**
     * the class constructor
     *
     * @param string $symbol
     */
    public function __construct($symbol)
    {
        $this->symbol    = $symbol;
        $this->avg       = $this->avarage();
        $this->active    = false;
        $this->direction = '';
        $this->pprice    = 0;
        $this->ratio     = 1;
        $this->long      = $this->avg - $this->ratio;
        $this->short     = $this->avg + $this->ratio;
        $this->balance   = 0.00046;
        $this->quantity  = 0;
        $this->lastprice = 0;
    }

    /**
     * Open a websocket connection and watch the price
     *
     * @return void
     */
    public function trade()
    {
        return BinanceServices::api()->trade($this->symbol, function ($api, $symbol, $trade) {
            echo('.');
            $this->decide(substr($trade['price'],8,10));
        });
    }

    /**
     * Get avarage price from last 1 hours trade
     *
     * @return string
     */
    public function avarage()
    {
        $all = BinanceServices::api()->candlesticks(
            $this->symbol,
            '1m',
            60,
            now()->subHour()->timestamp * 1000,
            now()->timestamp * 1000
        );

        $total = 0;

        foreach ($all as $single) {
            $total += substr($single['close'], 8, 10);
        }

        return number_format($total / count($all), '0', '.', ',');
    }

    /**
     * Decide about action
     *
     * @param string $price
     * @return void
     */
    public function decide($price)
    {
        if ($this->active) {
            if ($price == $this->pprice) {
                echo ("current price is {$price} the same as the position price {$this->pprice}, taking not any action" . "\n");
                return;
            }
            $this->inPosition($price);
        } elseif (!$this->active && $this->avg == $price) {
            echo ("looking for trade but price {$price} currently at 'no trade' zone..." . "\n");
            return;
        } elseif (!$this->active) {
            $this->takeShot($price);
        }
        $this->lastprice = $price;
    }

    public function takeShot($price)
    {
        if ($price <= $this->long || $price >= $this->short) {
            $cprice = ($price/100000000);
            $this->quantity = $this->balance / $cprice;
            $this->active = true;
            $this->pprice = $price;

            if ($price <= $this->long) {
                $this->direction = 'long';
                echo ("\e[31;7;1m NEW POSITION \e[0m \e[33;7;3mfor long order {$this->symbol}".
                    " price at {$price} total {$this->quantity} unit\e[0m"."\n");
            } elseif ($price >= $this->short) {
                $this->direction = 'short';
                echo ("\e[31;7;1m NEW POSITION \e[0m \e[33;7;3mfor short order {$this->symbol}".
                    " price at {$price} total {$this->quantity} unit\e[0m"."\n");
            }
        }
    }

    /**
     * Define what should we do in both way
     * if we are in the position and current price is equal the avarage price
     * where no trade point for us, if we are in position then sell all
     *
     * @param string $price
     * @return string
     */
    public function inPosition($price)
    {
        if ($this->avg == $price) {
            $this->active = false;
            $this->pprice = $price;
            //$sell = BinanceServices::api()->sellTest('SCBTC', $this->quantity, $price, 'LIMIT', []);
            echo ("\e[33;7;3m POSITION CLOSED \e[0m \e[33;7;3mwe sold out our position.\e[0m"."\n");
            //echo ($sell);
        }
    }
}
