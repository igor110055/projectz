<?php

namespace App\Services\WebsocketServices;

use App\Services\BotServices\MarketActivities;
use App\Services\BotServices\UserMarket;

class Client
{

    public $websocketData;

    public $lastStamp;

    public $rateLimit = 3;
    /**
     * Static method to use outside
     *
     * @return App\Services\WebsocketServices;
     */
    public static function miniTicker()
    {

        $socket = new Client();
        return $socket->webSocketMiniTicker();
    }

    /**
     * Websocket connection base class
     *
     * @return void
     */
    public function webSocketMiniTicker()
    {
        $marketActivities = new \App\Services\BotServices\MarketActivities();
        $loop = \React\EventLoop\Loop::get();
        $react = new \React\Socket\Connector($loop);
        $connector = new \Ratchet\Client\Connector($loop, $react);

        echo ('Market Scanning..');
        $connector("wss://stream.binance.com:9443/ws/!miniTicker@arr")->then(
            function ($ws) use ($loop, $marketActivities) {
                $ws->on('message', function ($dataset) use ($ws, $loop, $marketActivities ) {
                    $data = json_decode($dataset, true);
                    print_r($data);
                    foreach($data as $item){
                            $marketActivities->handle($item);
                    }
                });

                $ws->on('close', function ($code = null, $reason = null) use ($loop, $marketActivities) {
                    if ($this->rateLimit > 0) {
                        sleep(40);
                        print("\n" . "\n" . "\e[48;5;196m\e[38;5;159m\e[1;5mWebsocket Bağlantısı koptu\e[0m" . "\n" . "\n");
                        print("\n" . "\n" . "\e[48;5;196m\e[38;5;159m\e[1;5mBağlantı tekrar deneniyor\e[0m" . "\n" . "\n");
                        $this->rateLimit -= 1;
                        $this->webSocketMiniTicker( );
                    } elseif ($this->rateLimit === 0) {
                        $this->rateLimit = 3;
                        sleep(60);
                        print("\n" . "\n" . "\e[48;5;196m\e[38;5;159m\e[1;5mDeğişik bişi denedik\e[0m" . "\n" . "\n");
                        $this->webSocketMiniTicker( );
                    }
                    $loop->stop();
                });
            },
            function ($e) use ($loop) {
                echo "Could not connect: {$e->getMessage()}" . PHP_EOL;
                $loop->stop();
            }
        );

        $loop->run();
    }

    public static function trade($symbol, callable $callback)
    {
        $websocket = new Client();

        return $websocket->trades($symbol, $callback);
    }

    /**
     * Trades WebSocket Endpoint
     *
     * $api->trades(["BNBBTC"], function($api, $symbol, $trades) {
     * echo "{$symbol} trades update".PHP_EOL;
     * print_r($trades);
     * });
     *
     * @param $symbols
     * @param $callback callable closure
     * @return null
     */
    public static function trades($symbol, callable $callback)
    {
        $loop = \React\EventLoop\Loop::get();
        $react = new \React\Socket\Connector($loop);
        $connector = new \Ratchet\Client\Connector($loop, $react);

        // $this->info[$symbol]['tradesCallback'] = $callback;

        $endpoint = strtolower($symbol) . '@trades';
        $subscriptions[$endpoint] = true;

        $connector("wss://stream.binance.com:9443/ws/" . strtolower($symbol) . '@trade')->then(function ($ws) use ($callback, $symbol, $loop, $endpoint) {
            $ws->on('message', function ($data) use ($ws, $callback, $loop, $endpoint) {

                $json = json_decode($data, true);
                $symbol = $json['s'];
                $price = $json['p'];
                $quantity = $json['q'];
                $timestamp = $json['T'];
                $maker = $json['m'] ? 'true' : 'false';
                $trades = [
                    "price" => $price,
                    "quantity" => $quantity,
                    "timestamp" => $timestamp,
                    "maker" => $maker,
                    "eventTime" => $json["E"],
                    'id' => $json["a"],
                ];
                // $this->info[$symbol]['tradesCallback']($this, $symbol, $trades);
                call_user_func($callback, $this, $symbol, $trades);
            });
            $ws->on('close', function ($code = null, $reason = null) use ($symbol, $loop) {
                // WPCS: XSS OK.
                echo "trades({$symbol}) WebSocket Connection closed! ({$code} - {$reason})" . PHP_EOL;
                $loop->stop();
            });
        }, function ($e) use ($loop, $symbol) {
            // WPCS: XSS OK.
            echo "trades({$symbol}) Could not connect: {$e->getMessage()}" . PHP_EOL;
            $loop->stop();
        });

        $loop->run();
    }
}
