<?php

namespace App\Services\BotServices\TradeServices;

class TrailingStop
{
    /**
     * Ticarete takip eden stoploss yeteneği kazandıran sınıf.
     * Sınıf yapıcısına fiyatı, stoploss (default %1) değerini
     * ve pozisyonun (short -1, long =1) yönünü alır. Koşullar
     * tetiklendi/stop patladıysa false döner.
     *
     * @param string $price
     * @param float $stoploss
     * @param integer $position
     */
    public function __construct(
        string $symbol, string $price, float $quantity, bool $bull = true
    ){
        $this->bestPrice = $this->currentPrice = $price;
        $this->stopLossRatio = $bull ? 0.98 : 1.02;
        $this->stopPrice = $price * $stopLossRatio;
        $this->quantity = $position;
        $this->print = new \League\CLImate\CLImate;
    }

    /**
     * Tanımlamaları geçilmeleri durumunda geçen ile değiştirerek
     * işlem süresi içinde görülen en yüksek ve alçak fiyatları saklar
     *
     * @param string $price
     * @return true
     */
    public function newTicker($price)
    {
        if( $this->bestPrice < $price ){
            $this->bestPrice = $price;
            $this->stopPrice = $price * $stopLossRatio;
            $this->print->out(
                "Update Best Price: {$this->bestPrice},
                Updated Stop Loss: {$this->stopLoss}"
            );
        }
        return true;
    }

    /**
     * According to position is a long or shot position,
     * Check if stop limit price exceeded for new price
     *
     * @param float $price
     * @return float
     */
    public function stopExceeded($price)
    {
        $this->print->out("Stop Loss Triggered {$price}");

        return $this->bull ?
            $price < $this->stopPrice :
            $price > $this->stopPrice;
    }


    /**
     * Run a stoploss Order
     *
     * @param string $price
     * @return bool
     */
    public function run($price)
    {
        $this->newTicker($price);

        return $this->stopExceeded($price);
    }

}
