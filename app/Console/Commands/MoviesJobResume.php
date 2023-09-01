<?php

namespace App\Console\Commands;

use App\Models\MoviesLogs;
use Illuminate\Console\Command;

class MoviesJobResume extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movies:jobs:resume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starting processing jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        MoviesLogs::where("status", MoviesLogs::STATUS_PAUSED)->update(['status' => MoviesLogs::STATUS_PENDING]);
    }
}

