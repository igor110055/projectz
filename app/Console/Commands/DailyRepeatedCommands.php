<?php

namespace App\Console\Commands;

use App\Models\Price\DailyPrice;
use Illuminate\Console\Command;
use App\Services\BinanceServices;
use Illuminate\Support\Facades\Log;

class DailyRepeatedCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dailyTasks:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Job for get done to the daily tasks';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \App\Jobs\ScheduleJobs\DailyPriceJobs::run();
        \App\Jobs\ScheduleJobs\SaveExchangeInfo::run();
        Log::channel('job')->info('SaveDailyPrice command has been done by cronjob.' );
    }
}
