<?php

namespace App\Services\BotServices\ArbitrageBot;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class GateLocal
{
    public function __construct()
    {
        $this->data = collect(Http::get('https://data.gateapi.io/api2/1/marketlist')->json()['data']);
        $this->marketInfo = collect(Http::get('https://data.gateapi.io/api2/1/marketinfo')->json()['pairs']);
        $this->activeSymbols = [];
        $this->onlyActiveSymbols();
        $this->initial = [ 'balance' => number_format(100,8,'.',','), 'asset' => 'usdt'];
        $this->print = new \League\CLImate\CLImate;
        $this->fee = 1.003;
    }

    /**
     * Return only active symbols
     *
     * @return array
     */
    public function onlyActiveSymbols()
    {
        foreach( $this->marketInfo as $key => $info ) {
            if( $info[array_keys($info)[0]]['trade_disabled'] == 0){
                array_push($this->activeSymbols, array_keys($info)[0]);
            }
        }
    }

    public function run()
    {
        foreach($this->activeSymbols as $pair)
        {
            $this->resetWallet();
            $value = $this->data->where('pair',$pair)->first();

            if(null !== $value && $this->notSameAsInitial($value))
            {
                $symbol1 = $this->buildSymbol($value['curr_b'], $this->initial['asset']);
                $price1 = $this->priceWithFee($symbol1);
                $symbol2 = $value['pair'];
                $price2 = $this->priceWithFee($symbol2);
                $symbol3 = $this->buildSymbol($value['curr_a'], $this->initial['asset']);
                $price3 = $this->priceWithFee($symbol3);

                $path[0] = [
                    'trade' => "-",
                    "price" => "-",
                    "balance" => $this->initial['balance'],
                    'base volume' => "-",
                    'quote volume' => "-",
                    'asset' => $this->initial['asset']
                ];
                $path[1] = [
                    'trade' => $symbol1,
                    'price' => $price1,
                    'balance' => $price1 > 0 ? $this->initial['balance'] / $price1 : 0,
                    'base volume' => $this->getVolumes($symbol1)['vol Base'],
                    'quote volume' => $this->getVolumes($symbol1)['vol Quote'],
                    'asset' => $value['curr_b']
                ];
                $path[2] = [
                    'trade' => $value['pair'],
                    'price' => $value['rate'],
                    'balance' => $value['rate'] > 0 ? $path[1]['balance'] / $value['rate'] : 0,
                    'base volume' => $this->getVolumes($symbol2)['vol Base'],
                    'quote volume' => $this->getVolumes($symbol2)['vol Quote'],
                    'asset' => $value['curr_a']
                ];
                $path[3] = [
                    'trade' => $symbol3,
                    'price' => $price3,
                    'balance' => $price3 > 0 ? number_format($path[2]['balance'] * $price3,'10','.',',') : 0,
                    'base volume' => $this->getVolumes($symbol3)['vol Base'],
                    'quote volume' => $this->getVolumes($symbol3)['vol Quote'],
                    'asset' => $this->initial['asset']
                ];

                if( ($path[3]['balance'] > 102) && ($path[2]['base volume'] > 0) && ($path[2]['quote volume'] > 0) ){
                    $this->print->redTable($path);
                }
            }
        }
    }

    public function getVolumes($symbol)
    {
        if ( is_null($this->pair($symbol)['vol_a']) || is_null($this->pair($symbol)['vol_b'])) return;
        return [
            'vol Base' => $this->pair($symbol)['vol_a'],
            'vol Quote' => $this->pair($symbol)['vol_b']
        ];
    }

    public function resetWallet()
    {
        $this->initial = [
            'balance' => number_format(100,8,'.',','),
            'asset' => 'USDT'
        ];
    }

    public function pair($symbol)
    {
        return $this->data->where('pair',$symbol)->first();
    }

    /**
     * Get the price with additional trade fee
     *
     * @param string $symbol
     * @return int
     */
    public function priceWithFee($symbol)
    {
        if(null !== $this->pair($symbol)){
            $price = $this->pair($symbol)['rate'];
            $price =+ $price * 1.003;
        }

        return $this->pair($symbol)['rate'];
    }

    /**
     * Check if the pair is including initial asset
     *
     * @param array $pair
     * @return bool
     */
    public function notSameAsInitial($value)
    {
        return $value['curr_b'] !== $this->initial['asset'] &&
                $value['curr_a'] !== $this->initial['asset'];
    }

    /**
     * Build a pair based on given tokens
     *
     * @param string $a
     * @param string $b
     * @return string
     */
    public function buildSymbol($a,$b)
    {
        return Str::lower($a.'_'.$b);
    }
}

