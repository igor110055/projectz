<?php

namespace App\Services\CalculationServices;

use App\Models\User;
use App\Models\Price\DailyPrice;
use App\Services\BinanceServices;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class UserWalletCalculations
{

    /**
     * Create a new service class constructor
     * * TODO: 2 timer job must created, 1 for daily token prices and other for
     * * every minute, but every minute job will delete old records to keep db small
     */
    public function __construct()
    {
        $this->user = app()->runningInConsole() ? User::first() : Request::user();
        $this->balance_values = $this->user->total_balances;
    }


    /**
     * Return token's last X Day Price for each day
     *
     * @param string $token
     * @param int $days
     * @param string $period
     * @return array
     */
    public function tokenLastXDayPrices($token, $days, $period = "d")
    {
        $dayAndPrice = [];
        $symbol = $token . $this->user->quote_preference;

        if ($period = 'd') {
            $start = \Carbon\Carbon::now()->subDays($days)->timestamp * 1000;
        } elseif ($period = 'm') {
            $start = \Carbon\Carbon::now()->subMonths($days)->timestamp * 1000;
        }
        //! yama! veri tabanı dolu olmalı!
        // veri tabanında istenen tarih aralığına dair kayıt yoksa api üzerinden çekip veri tabanına işle
        $prices = DailyPrice::where('symbol', $symbol)->where('timestamp', '>', $start)->get();
        if (count($prices) < $days) {
            Log::info($prices . '\'e ait istenen kayıt veri tabanında yok, api\'den isteniyor ');
            $data = BinanceServices::api()->candlesticks($symbol, '1d', $days, $start, now()->timestamp * 1000);
            foreach ($data as $key => $day) {
                DailyPrice::create([
                    'symbol' => $symbol,
                    'price' => $day['close'],
                    'timestamp' => $key,
                ]);
                $dayAndPrice[] = [
                    'price' => $day['close'],
                    'date' => createFromTimestamp($key)
                        ->toDateTimeString()
                ];
            }
        } else {
            foreach ($prices as $key => $price) {
                $dayAndPrice[] = [
                    'price' => $price->price,
                    'date' => createFromTimestamp($price->timestamp)
                        ->toDateTimeString()
                ];
            }
        }

        return $dayAndPrice;
    }

    /**
     * Return token's last X Month Price for each month
     *
     * @param string $token
     * @param int $days
     * @param string $period
     * @return array
     */
    public function tokenLastXMonthPrices($token, $months, $period = "m")
    {
        return $this->tokenLastXDayPrices($token, $months, $period);
    }

    /**
     * Return total wallet value for x days to each day
     *
     * @param integer $days
     * @return void
     */
    public function totalWalletValue($days = 7)
    {
        $snapshots = BinanceServices::api()->accountSnapshot('SPOT', $days);
        $totalBalanceBTC = [];

        foreach ($snapshots['snapshotVos'] as $snapshot) {
            $totalBalanceBTC[] = $snapshot['data']['totalAssetOfBtc'];
        }

        $btcPrices = $this->tokenLastXDayPrices('BTC', $days);

        foreach ($btcPrices as $key => $btc) {
            $quote = $btc['price'] * $totalBalanceBTC[$key];
            $quotePrices[] = '$' . $quote;
        }

        return  [ 'btcQuantities' => $quotePrices,
            'priceValues' => $totalBalanceBTC,
            'lastDays' => $btcPrices ];
    }

    public function tokenPercentOfWallet($token)
    {
        $totalWalletValue = floatval(str_replace([',','$'], ['',''], $this->balance_values['TOTAL']));
        $totalTokenValue = floatval(str_replace([',','$'], ['',''], $this->balance_values['tokens'][$token]));

        return '%' . number_format(($totalTokenValue / $totalWalletValue * 100), '2', '. ', '');
    }

    public function tokenPercentOfSelf($token)
    {
        $balance = $this->user->balances()->where('token', $token)->first();
        return '%' . number_format($balance->total_revenue / $balance->total_cost * 100, '2', '.', '');
    }

    /**
    * // tokenin son 1 haftalık gün gün fiyatları   tokenLastXDaysPrices
    * // tokenin son 6 aylık ay ay fiyatları        tokenLastXMonthsPrices
    * // cüzdanın son 1 haftalık gün gün tutarı                   +
    * // cüzdanın son 1 aylık gün gün tutarı                      +
    * // tokenin toplam kar - zarar durumu                        +
    * // token maliyetinin kaç katı kazanmış                      +
    * // tokenin cüzdanın % kaçını kapsadığı                      +
    * // en çok kazandıran ve kaybettiren tokenler sıralaması     +
    * // tokenin toplam ve ortalama maliyetleri                   +
    * // tokenin toplam getirisi                                  +
    * // tokenin alım tarih ve fiyatları                          +
     *
     * @return void
     */
    public function detailStatistics()
    {
        $walletTotalCost = $walletTotalValue = $walletTotalRevenue = 0;

        foreach ($this->user->total_balances['tokens'] as $key => $value) {
            $token = $this->user->balances()->whereToken($key)->first();

            $walletTotalCost += $token->total_cost;
            $walletTotalValue += $token->current_value;
            $walletTotalRevenue = $walletTotalValue - $walletTotalCost;

            $last7days = $this->tokenLastXDayPrices($key, 7);
            $details[$key] = [
                'quantity'       => $token->total,
                'currentPrice'   => $token->current_price,
                'totalCost'      => $token->total_cost,
                'avarageCost'    => $token->total_cost / $token->total,
                'currentValue'   => $token->current_value,
                'totalRevenue'   => $value,
                'last24Hours'    => percentage($token->current_price, $last7days[5]['price']),
                'volumeOfWallet' => $this->tokenPercentOfWallet($key),
                'volumeOfItself' => $this->tokenPercentOfSelf($key),
                'last7dayPrices' => $last7days,
                'tokenOrders'    => $token->token_orders
            ];
        }
        $days = $this->totalWalletValue();

        $details['wallet'] = [
            'currentValue' => $walletTotalValue,
            'totalRevenue' => $walletTotalRevenue,
            'totalCost'    => $walletTotalCost,
            'last24Hours'  => percentage($days['priceValues'][6], $days['priceValues'][5])
        ];

        return collect($details)->sortBy('totalRevenue');
    }
}
