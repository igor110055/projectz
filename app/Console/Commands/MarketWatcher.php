<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MarketWatcher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketwatcher:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a actively stock market analysis on the pair\'s tickers';

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
        return \App\Services\WebsocketServices\Client::miniTicker();
    }
}
