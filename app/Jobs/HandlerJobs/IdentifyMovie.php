<?php

namespace App\Jobs\HandlerJobs;

use App\Models\Movies;
use App\Models\MoviesLogs;
use App\Services\MovieHash;
use App\Services\MovieService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class IdentifyMovie implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $file;
    public $file_path;
    public $path;

    /**
     * Create a new job instance.
     */
    public function __construct($file, $file_path, $path)
    {
        $this->file = $file;
        $this->file_path = $file_path;
        $this->path = $path;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = (new MoviesLogs())->saveLog(0, 'identify', [], MoviesLogs::STATUS_PENDING);
        try {
            $hash = MovieHash::OpenSubtitlesHash($this->file_path . "/" . $this->file);

            //$hash = hash_file('xxh128', $this->file_path."/".$this->file);
            $movie = Movies::withTrashed()->where("hash", $hash)->first();
            $parent_id = 0;

            $filteredFile = new MovieService();
            if ($TVSeries = $filteredFile->isEpisode($this->file)) {
                $movieParent = Movies::withTrashed()->where("hash", md5($TVSeries))->first();
                if (!$movieParent) {
                    $movieParent = new Movies();
                    $movieParent->hash = md5($TVSeries);
                    $movieParent->status = Movies::STATUS_PENDING_INDEX;
                    $movieParent->folders_id = $this->path->id;
                    $movieParent->title = $TVSeries;
                    $movieParent->filename = $this->file;
                    $movieParent->extension = Str::of($this->file)->explode(".")->last();
                    $movieParent->type = Movies::TYPE_TV_PARENT;
                    $movieParent->subfolder = Str::of($this->file_path . "/")->replace($this->path->path, "")->explode("/")->first();
                    $movieParent->deleted_at = null;
                    $movieParent->save();
                }
                $parent_id = $movieParent->id;
            }

            if (!$movie) {
                $movie = new Movies();
                $movie->hash = $hash;
                $movie->status = Movies::STATUS_PENDING_INDEX;
                $movie->parent_id = $parent_id;
                $movie->folders_id = $this->path->id;
                $movie->title = $this->file;
                $movie->filename = $this->file;
                $movie->extension = Str::of($this->file)->explode(".")->last();
                $movie->type = Movies::TYPE_MOVIE;
                $movie->subfolder = Str::of($this->file_path . "/")->replace($this->path->path, "")->toString();
            }

            if ($filteredFile->isEpisode($this->file)) {
                $movie->type = Movies::TYPE_TV_EPISODE;
            }

            $movie->deleted_at = null;
            $movie->save();
            $log->setMovie($movie->id)->closeLog(MoviesLogs::STATUS_SUCCESS);
        } catch (\Exception $exception) {
            $log->setMovie($movie->id)->closeLog(MoviesLogs::STATUS_FAILED, [], ['error' => $exception->getMessage()]);
        }
    }
}
