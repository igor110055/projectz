<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PumpDumpBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pumpDumpBot:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'runs a volatility trading bot which scan market and buy whoever best suit the setted criterias';

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
        return \App\Services\WebsocketServices\Client::pumpDumpBot();
    }
}
