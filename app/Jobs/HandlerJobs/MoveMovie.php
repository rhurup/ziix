<?php

namespace App\Jobs\HandlerJobs;

use App\Models\Movies;
use App\Models\MoviesFolders;
use App\Models\MoviesLogs;
use App\Services\MovieService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MoveMovie implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $movies_id;
    public $folder_id;
    public $path;

    /*
     * Create a new job instance.
     */
    public function __construct($movies_id, $folder_id, $path)
    {
        $this->movies_id = $movies_id;
        $this->folder_id = $folder_id;
        $this->path = $path;
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
        $movie = Movies::with(["folder", "meta", "tmdb", "imdb"])->findOrFail($this->movies_id);
        $newfolder = MoviesFolders::findOrFail($this->folder_id);

        $log = (new MoviesLogs())->saveLog($movie->id, "move", []);
        try {
            $this->path = Str::of($this->path)->replaceFirst("/", "")->replaceLast("/", "")->toString();
            $this->path = '/' . $this->path . '/';

            if ($this->path == '//') {
                $this->path = '/';
            }

            if (!is_dir($newfolder->path . '' . $this->path)) {
                File::makeDirectory($newfolder->path . '' . $this->path, 0755, true);
            }

            $from = $movie->folder->path . '' . $movie->subfolder . '' . $movie->filename;
            $to = $newfolder->path . '' . $this->path . '' . $movie->filename;

            rename($from, $to);

            if ((new MovieService())->isDirEmpty($movie->folder->path . '' . $movie->subfolder)) {
                File::deleteDirectory($movie->folder->path . '' . $movie->subfolder);
            }

            $movie->folders_id = $newfolder->id;
            $movie->subfolder = $this->path;
            $movie->save();

            $movie->meta->status = Movies::STATUS_PENDING_INDEX;
            $movie->meta->save();

            $movie->tmdb->status = Movies::STATUS_PENDING_INDEX;
            $movie->tmdb->save();

            $movie->imdb->status = Movies::STATUS_PENDING_INDEX;
            $movie->imdb->save();

            $log->closeLog(MoviesLogs::STATUS_SUCCESS);
        } catch (\Exception $exception) {
            $log->closeLog(MoviesLogs::STATUS_FAILED, [], ["error" => $exception->getMessage() . "\n" . $exception->getTraceAsString()]);
        }
    }
}
