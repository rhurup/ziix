<?php

namespace App\Jobs\HandlerJobs;

use App\Models\Movies;
use App\Models\MoviesLogs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class RenameMovie implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $movies_id;
    public $newfilename;

    /*
     * Create a new job instance.
     */
    public function __construct($movies_id, $newfilename)
    {
        $this->movies_id = $movies_id;
        $this->newfilename = $newfilename;
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
        $log = (new MoviesLogs())->saveLog($movie->id, "rename", []);
        try {
            $from = $movie->folder->path . '' . $movie->subfolder . '' . $movie->filename;
            $to = $movie->folder->path . '' . $movie->subfolder . '' . $this->newfilename;

            rename($from, $to);

            $movie->filename = $this->newfilename;
            $movie->save();

            $movie->meta->status = Movies::STATUS_PENDING_INDEX;
            $movie->meta->save();

            $movie->tmdb->status = Movies::STATUS_PENDING_INDEX;
            $movie->tmdb->save();

            $movie->imdb->status = Movies::STATUS_PENDING_INDEX;
            $movie->imdb->save();

            $log->closeLog(MoviesLogs::STATUS_SUCCESS);
        } catch (\Exception $exception) {
            $log->closeLog(MoviesLogs::STATUS_FAILED, '', $exception->getMessage() . "\n" . $exception->getTraceAsString());
        }
    }
}
