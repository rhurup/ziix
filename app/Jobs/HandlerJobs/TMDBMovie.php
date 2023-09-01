<?php

namespace App\Jobs\HandlerJobs;

use App\Models\Movies;
use App\Models\MoviesLogs;
use App\Services\Settings;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TMDBMovie implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $movie_id;

    public $token = '';

    public $types = [
        1 => ['type' => 'movie', 'ttype' => 'movie'], // Movies
        2 => ['type' => 'series', 'ttype' => 'tv'], // Parent for series
        3 => ['type' => 'series', 'ttype' => 'tv']  // Episode for series
    ];

    /**
     * Create a new job instance.
     */
    public function __construct($movie_id)
    {
        $this->movie_id = $movie_id;
        $this->token = Settings::get("tmdb_token", "");
    }

    public function middleware()
    {
        return [new WithoutOverlapping("tmdb_" . $this->movie_id)];
    }

    public function authenticate()
    {
        $client = new Client();

        $response = Cache::remember("themoviedb-authentication", 3600, function () use ($client) {
            $response = $client->request('GET', 'https://api.themoviedb.org/3/authentication', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'accept' => 'application/json',
                ],
            ]);
            return $response;
        });

        return $response;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $movie = Movies::with(["folder", "meta", "tmdb"])->findOrFail($this->movie_id);

        $log = (new MoviesLogs())->saveLog($movie->id, 'tmdb', [], MoviesLogs::STATUS_PENDING);
        try {
            if (in_array($movie->type, [Movies::TYPE_MOVIE, Movies::TYPE_TV_PARENT])) {
                $results = $this->search($movie);
                if ($results) {
                    if ($results[0]) {
                        $movie->tmdb->tmdb_id = $results[0]->id;
                        $movie->tmdb->original_title = $results[0]->original_title ?? $results[0]->original_name;
                        $movie->tmdb->release_date = $results[0]->release_date ?? $results[0]->first_air_date;
                        $movie->tmdb->genre_ids = implode(",", $results[0]->genre_ids ?? []);
                        $movie->tmdb->poster = $this->downloadPoster($results[0]->poster_path);
                    }
                }
            }
            if (in_array($movie->type, [Movies::TYPE_TV_EPISODE])) {
                $parent = Movies::with(["folder", "meta", "tmdb"])->findOrFail($movie->parent_id);
                if ($parent->tmdb->id) {
                    $results = $this->searchEpisode($movie, $parent);
                    if ($results) {
                        $movie->tmdb->tmdb_id = $results->id;
                        $movie->tmdb->original_title = $results->original_title ?? $results->original_name ?? $results->name;
                        $movie->tmdb->release_date = $results->release_date ?? $results->first_air_date ?? $results->air_date;
                        $movie->tmdb->genre_ids = implode(",", $results->genre_ids ?? []);
                        $movie->tmdb->poster = $this->downloadPoster($results->poster_path ?? $results->poster);
                    }
                } else {
                    $results = ['Parent has no TMDB id'];
                }
            }

            $movie->tmdb->status = MoviesLogs::STATUS_SUCCESS;
            $movie->tmdb->save();
            $log->setMovie($movie->id)->closeLog(MoviesLogs::STATUS_SUCCESS, $results);
        } catch (\Exception $exception) {
            $movie->tmdb->status = MoviesLogs::STATUS_FAILED;
            $movie->tmdb->save();
            $log->setMovie($movie->id)->closeLog(MoviesLogs::STATUS_FAILED, [], ['error' => $exception->getMessage(), $results ?? []]);
        }
    }

    public function search($movie)
    {
        $client = new Client();
        $q = Str::of($movie->title)->replace(" ", "%20")->toString() . '&include_adult=false&language=en-US&page=1';

        if ($movie->meta->year) {
            $url = 'https://api.themoviedb.org/3/search/' . $this->types[$movie->type]['ttype'] . '?query=' . $q . '&year=' . $movie->meta->year;
        } else {
            $url = 'https://api.themoviedb.org/3/search/' . $this->types[$movie->type]['ttype'] . '?query=' . $q;
        }

        $response = Cache::remember(Str::slug($movie->title) . "-tmdb", 3600, function () use ($client, $url) {
            $resp = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'accept' => 'application/json',
                ],
            ]);
            return json_decode($resp->getBody()->getContents(), false);
        });

        if ($response->total_results) {
            return $response->results;
        } else {
            return false;
        }
    }

    public function downloadPoster($path)
    {

        $file = file_get_contents("https://image.tmdb.org/t/p/w185" . $path);
        $filepath = Str::of($path)->replace("/", "")->substr(0, 4)->toString();
        $filename = Str::of($path)->replace("/", "")->substr(4)->toString();

        if (!is_dir(public_path("posters/" . $filepath))) {
            File::makeDirectory(public_path("posters/" . $filepath), 0755, true);
        }

        File::put(public_path("posters/" . $filepath . "/" . $filename), $file);

        return "/posters/" . $filepath . "/" . $filename;
    }

    public function searchEpisode($episode, $parent)
    {
        $client = new Client();

        $url = 'https://api.themoviedb.org/3/tv/' . $parent->tmdb->tmdb_id . '/season/' . $episode->season . '?language=en-US';

        $response = Cache::remember($parent->meta->tmdb_id . "-" . $episode->season, 3600, function () use ($client, $url) {
            $resp = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'accept' => 'application/json',
                ],
            ]);
            return json_decode($resp->getBody()->getContents(), false);
        });

        foreach ($response->episodes as $_episode) {
            if ($_episode->episode_number == $episode->episode) {
                $_episode->poster = $response->poster_path;
                return $_episode;
            }
        }

        return "";
    }
}
