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
use Illuminate\Support\Str;

class IMDBMovie implements ShouldQueue
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

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $movie = Movies::with(["folder", "meta", "tmdb", "imdb"])->findOrFail($this->movie_id);

        $log = (new MoviesLogs())->saveLog($movie->id, 'imdb', [], MoviesLogs::STATUS_PENDING);

        try {
            if (in_array($movie->type, [Movies::TYPE_MOVIE, Movies::TYPE_TV_PARENT])) {
                $results = $this->imdb($movie);
                if ($results) {
                    $movie->imdb->imdb_id = $results->imdb_id ?? 0;
                }
            }
            if (in_array($movie->type, [Movies::TYPE_TV_EPISODE])) {
                $parent = Movies::with(["folder", "meta", "tmdb"])->findOrFail($movie->parent_id);
                if ($parent->tmdb->id) {
                    $results = $this->imdbEpisodes($movie, $parent);
                    if ($results) {
                        $movie->imdb->imdb_id = $results->imdb_id ?? 0;
                    }
                } else {
                    $results = ['Parent has no TMDB id'];
                }
            }

            $movie->imdb->status = MoviesLogs::STATUS_SUCCESS;
            $movie->imdb->save();
            $log->setMovie($movie->id)->closeLog(MoviesLogs::STATUS_SUCCESS, $results);
        } catch (\Exception $exception) {
            $movie->imdb->status = MoviesLogs::STATUS_FAILED;
            $movie->imdb->save();
            $log->setMovie($movie->id)->closeLog(MoviesLogs::STATUS_FAILED, [], ['error' => $exception->getMessage(), $results ?? []]);
        }
    }

    public function imdb($movie)
    {
        $client = new Client();

        $url = 'https://api.themoviedb.org/3/' . $this->types[$movie->type]['ttype'] . '/' . $movie->tmdb->tmdb_id . '/external_ids';

        $response = Cache::remember($movie->tmdb->tmdb_id . "-external_ids", 3600, function () use ($client, $url) {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'accept' => 'application/json',
                ],
            ]);
            return json_decode($response->getBody()->getContents(), false);
        });

        return $response;
    }

    public function imdbEpisodes($episode, $parent)
    {
        $client = new Client();

        $url = 'https://api.themoviedb.org/3/tv/' . $parent->tmdb->tmdb_id . '/season/' . $episode->season . '/episode/' . $episode->episode . '/external_ids';
        $response = Cache::remember($parent->tmdb->tmdb_id . "-external_ids-" . $episode->season . '-season', 3600, function () use ($client, $url) {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'accept' => 'application/json',
                ],
            ]);
            return json_decode($response->getBody()->getContents(), false);
        });

        return $response;
    }
}
