<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deleteOldData:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'deletes delisted and older than 1 hour data from ticker_highlights table';

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
     * Simple remove delisted pairs with oldest records than 15 minutes
     *
     * @return int
     */
    public function handle()
    {
        \App\Models\Price\TickerHighlights::where('eventType', 'delist')
            ->orWhereBetween('created_at', [now()->subDay(), now()->subMinutes(30)])
            ->delete();
        Log::channel('job')->info('TickerHighlights cleaned from old data');
    }
}
