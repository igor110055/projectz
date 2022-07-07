<?php

namespace App\Services\WebsocketServices;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;

class Client {

    public static function pumpDumpBot()
    {
        $socket = new \WebSocket\Client("wss://stream.binance.com:9443/ws/!miniTicker@arr");
        $marketActivities = new \App\Services\BotServices\MarketActivities();
        print('searching market.');
        while(true){
            try
            {
                print('.');
                $message = json_decode($socket->receive());
                $marketActivities->handle($message);
            }
            catch(\Websocket\ConnectionException $e){
                print($e->getMessage());
            }
        }
    }

    public static function tokenTracker($token)
    {
        $path = Str::lower($token)."@trade";
        $socket = new \WebSocket\Client("wss://stream.binance.com:9443/ws/{$path}");
        $marketActivities = new \App\Services\BotServices\MarketActivities();

        while(true){
            try
            {
                print_r(json_decode($socket->receive()));
            }
            catch(\Websocket\ConnectionException $e){
                print($e->getMessage());
            }
        }
    }

    public static function userData(array $listKeys)
    {
        app()->runningInConsole() ?
            $exchange = \App\Models\Exchange::find(5) :
            $exchange = Auth::user()->exchange;

        $api_key = Crypt::decrypt($exchange->key);
        $api_secret = Crypt::decrypt($exchange->secret);

        $response = Http::post('https://api.binance.com/api/v1/userDataStream', [
            'X-MBX-APIKEY' => $api_key,
        ]);
        print_r($response);
        print('searching market.');

        $socket = new \WebSocket\Client("wss://stream.binance.com:9443/ws/{$listKeys}");

        while(true){
            try
            {
                print('.');
                $message = json_decode($socket->receive());
                $marketActivities->handle($message);
            }
            catch(\Websocket\ConnectionException $e){
                print($e->getMessage());
            }
        }
    }
}
