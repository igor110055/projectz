<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use App\Services\BinanceServices as Binance;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class CheckOrderStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Order and Binance Services instances
     *
     * @param $order App\models\Order;
     * @param $services App\Services\BinanceServices;
     */
    protected $order, $services;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        sleep(3);
        $orderStatus = Binance::api()->orderStatus(
            $this->order['symbol'], $this->order['orderId']
        );

        if($orderStatus['status'] !== 'FILLED'){
            Binance::api()->cancel($this->order['symbol'], $this->order['orderId']);
            $price = Binance::market()->price($this->order['symbol']);

            switch ($orderStatus['status']) {
                case 'BUY':
                    $price = $price * 1.01;
                    break;

                default:
                    $price = $price * 0.99;
                    break;
            }
        }
    }
}
