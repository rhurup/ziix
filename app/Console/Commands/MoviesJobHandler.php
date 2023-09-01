<?php

namespace App\Console\Commands;

use App\Jobs\HandlerJobs\IMDBMovie;
use App\Jobs\HandlerJobs\IndexMovie;
use App\Jobs\HandlerJobs\TMDBMovie;
use App\Models\MoviesImdbs;
use App\Models\MoviesMetas;
use App\Models\MoviesTmdbs;
use Illuminate\Console\Command;

class MoviesJobHandler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movies:jobs:handle';

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
        $globalcounter = 0;
        // Indexing files
        foreach (MoviesMetas::where("status", 0)->skip(0)->take(10)->get() as $movie) {
            IndexMovie::dispatch($movie->id);
            $globalcounter++;
        }

        // Indexing files
        foreach (MoviesTmdbs::where("status", 0)->skip(0)->take(10)->get() as $movie) {
            TMDBMovie::dispatch($movie->id);
            $globalcounter++;
        }

        // Indexing files
        foreach (MoviesImdbs::where("status", 0)->skip(0)->take(10)->get() as $movie) {
            IMDBMovie::dispatch($movie->id);
            $globalcounter++;
        }
    }
}

