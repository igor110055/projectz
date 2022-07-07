<?php

namespace App\Observers;

use App\Models\OrderLogger;
use Illuminate\Support\Facades\Log;

class OrderLoggerObserver
{
    /**
     * Handle the OrderLogger "created" event.
     *
     * @param  \App\Models\OrderLogger  $orderLogger
     * @return void
     */
    public function created(OrderLogger $orderLogger)
    {
        Log::channel('job')->info("OrderLogger created ");

        for($x = 0; $x < 5; $x++){
            sleep(4);
            print('uykum var');
        }

    }

    /**
     * Handle the OrderLogger "updated" event.
     *
     * @param  \App\Models\OrderLogger  $orderLogger
     * @return void
     */
    public function updated(OrderLogger $orderLogger)
    {
        //
    }

    /**
     * Handle the OrderLogger "deleted" event.
     *
     * @param  \App\Models\OrderLogger  $orderLogger
     * @return void
     */
    public function deleted(OrderLogger $orderLogger)
    {
        //
    }

    /**
     * Handle the OrderLogger "restored" event.
     *
     * @param  \App\Models\OrderLogger  $orderLogger
     * @return void
     */
    public function restored(OrderLogger $orderLogger)
    {
        //
    }

    /**
     * Handle the OrderLogger "force deleted" event.
     *
     * @param  \App\Models\OrderLogger  $orderLogger
     * @return void
     */
    public function forceDeleted(OrderLogger $orderLogger)
    {
        //
    }
}
