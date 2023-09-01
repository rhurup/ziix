<?php

namespace App\Console\Commands;

use App\Jobs\HandlerJobs\IdentifyMovie;
use App\Models\Movies;
use App\Models\MoviesFolders;
use App\Services\MovieFilter;
use App\Services\MovieService;
use App\Services\Settings;
use Illuminate\Console\Command;

class MoviesIdentifier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movies:identifier';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starting movie identify job';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /// TESTING
//        Movies::truncate();
//        MoviesMetas::truncate();
//        MoviesLogs::truncate();
//        MoviesTmdbs::truncate();
//        MoviesImdbs::truncate();
//        MoviesSubtitles::truncate();
//        MoviesFolders::where("id", "!=", 0)->update(['sha' => '']);

        if (Settings::get("files_identifier", 0)) {
            $this->folders = MoviesFolders::where("status", 1)->get();
            foreach ($this->folders as $folder) {
                $folder_sha = sha1(serialize((new MovieService())->map($folder->path, true)));
                if ($folder_sha != $folder->sha) {
                    Movies::where("folders_id", $folder->id)->delete();

                    if (is_dir($folder->path)) {
                        $files = new \RecursiveDirectoryIterator($folder->path, \RecursiveDirectoryIterator::SKIP_DOTS);
                        $filteredFiles = new MovieFilter($files);
                        foreach (new \RecursiveIteratorIterator($filteredFiles) as $file) {
                            IdentifyMovie::dispatch($file->getFilename(), $file->getPath(), $folder);
                        }
                    } else {
                        $folder->status = -1;
                        $folder->save();
                    }
                }
                $folder->sha = $folder_sha;
                $folder->save();
            }
        }
    }
}
