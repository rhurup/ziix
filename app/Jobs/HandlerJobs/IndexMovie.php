<?php

namespace App\Jobs\HandlerJobs;

use App\Models\Movies;
use App\Models\MoviesLogs;
use App\Services\MovieService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class IndexMovie implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $movies_id;

    /*
     * Create a new job instance.
     */
    public function __construct($movies_id)
    {
        $this->movies_id = $movies_id;
    }

    public function middleware()
    {
        return [new WithoutOverlapping("index_" . $this->movies_id)];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $movie = Movies::with("folder")->findOrFail($this->movies_id);
        $log = (new MoviesLogs())->saveLog($movie->id, "index", []);
        try {
            if ($movie->type == 1) {
                $this->handleMovie($movie, $movie->folder);
            }
            if ($movie->type == 2 && $movie->parent_id == 0) {
                $this->handleParentEpisode($movie);
            }
            if ($movie->type == 3 && $movie->parent_id != 0) {
                $this->handleEpisode($movie, $movie->folder);
            }

            $log->closeLog(MoviesLogs::STATUS_SUCCESS);
        } catch (\Exception $exception) {
            $log->closeLog(MoviesLogs::STATUS_FAILED, '', $exception->getMessage() . "\n" . $exception->getTraceAsString());
        }
    }

    public function handleMovie($movie, $path)
    {
        $filteredFile = new MovieService($movie->id);
        $filteredFile->getMovieData($path->path, $movie->subfolder, $movie->filename);
        $filteredFile->saveData($movie);
        $filteredFile->saveMeta($movie);
        $filteredFile->saveSubtitles($movie);

    }

    public function handleParentEpisode($movie)
    {
        $filteredFile = new MovieService($movie->id);
        $filteredFile->saveData($movie);
        $filteredFile->saveMeta($movie);
    }

    public function handleEpisode($movie, $path)
    {
        $filteredFile = new MovieService($movie->id);
        $filteredFile->getSeriesData($path->path, $movie->subfolder, $movie->filename);
        $filteredFile->saveData($movie);
        $filteredFile->saveMeta($movie);
        $filteredFile->saveSubtitles($movie);
    }
}
