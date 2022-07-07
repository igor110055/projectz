<?php

namespace App\Services\BotServices\TechnicalBot;

use Illuminate\Support\Facades\Http;
use App\Models\Price\CurrentPrice;

class ChartService
{
    public static function get($symbol)
    {
        $sr = new SupResTrader();
        $data = $sr->handle($symbol,'hours',72);
        $resistances = $data['resistances'];
        $supports = $data['supports'];

        $str = "symbol/{$symbol}/resistances/";
        $str .= implode(',', $resistances);

        $str .= "/supports/";
        $str .= implode(',', $supports);


        // POST http://localhost:5000/create_chart/symbol/BTCUSDT/resistances/2.08000000/supports/2.0250000,2.111
        $response = Http::withOptions(['debug' => true])->post("http://localhost:5000/create_chart/{$str}");

        return env('APP_URL') . $response->json()['file'];
    }
}
