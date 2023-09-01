<?php

namespace App\Http\Controllers;

use App\Jobs\HandlerJobs\MoveMovie;
use App\Jobs\HandlerJobs\RenameMovie;
use App\Models\Movies;
use App\Models\MoviesClips;
use App\Models\MoviesFolders;
use App\Services\PlexService;
use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class MoviesController extends Controller
{
    //
    public function index(Request $request)
    {

        $direction = $request->get("direction", "desc");
        $order = $request->get("order", "meta.year");

        $settings = Settings::all();
        if ($direction == "desc") {
            $movies = Movies::with(["meta", "tmdb", "imdb", "subtitles"])->where("status", ">=", Movies::STATUS_INDEX_SUCCESS)->get()->sortByDesc($order);
        } else {
            $movies = Movies::with(["meta", "tmdb", "imdb", "subtitles"])->where("status", ">=", Movies::STATUS_INDEX_SUCCESS)->get()->sortBy($order);
        }

        $moviesfailed = Movies::where("status", "<", Movies::STATUS_PENDING_INDEX)->count();

        $folders = MoviesFolders::get();

        return view("movies", [
                'movies' => $movies,
                'moviesfailed' => $moviesfailed,
                'folders' => $folders,
                'plex_libraries' => (new PlexService())->getLibraries(),
                'settings' => $settings,
                'direction' => $direction,
                'order' => $order
            ]
        );
    }

    public function view(Request $request, $id)
    {
        $movie = Movies::with(["meta", "tmdb", "imdb", "subtitles"])->findOrFail($id);

        return response()->json($movie);
    }

    public function rename(Request $request, $id)
    {
        $newfilename = $request->post("newfilename", "");
        $movie = Movies::findOrfail($id);

        if ($newfilename == "") {
            return response()->json([], 403);
        }

        RenameMovie::dispatch($movie->id, $newfilename);

        return response()->json([]);
    }

    public function move(Request $request, $id)
    {

        $folders_id = $request->post("folders_id", 0);
        $subfolder = $request->post("subfolder", '');

        $movie = Movies::findOrfail($id);
        $folder = MoviesFolders::findOrfail($folders_id);

        if (!$folders_id) {
            return response()->json([], 403);
        }
        if ($subfolder == "") {
            $subfolder = "/";
        }

        MoveMovie::dispatch($movie->id, $folder->id, $subfolder);

        return response()->json([]);
    }
}
