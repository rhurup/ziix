<?php

namespace App\Http\Controllers;

use App\Http\Responses\JsonResponse;
use App\Models\MoviesFolders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MoviesPathsController extends Controller
{
    public $types = [
        1 => ['type' => 'movie', 'ttype' => 'movie'],
        2 => ['type' => 'series', 'ttype' => 'tv']
    ];

    //
    public function index(Request $request)
    {

        $movies = MoviesPathsController::orderBy("title", "asc")->get();
        $paths = MoviesFolders::get();

        return view("home", ['movies' => $movies, 'path' => $paths]);
    }

    public function create(Request $request)
    {
        Cache::forget("plex_token_libraries");

        $path = new MoviesFolders();
        $path->path = $request->post("path");
        $path->type = (int)$request->post("type");
        $path->plex_key = (int)$request->post("plex_key");
        $path->status = 1;
        $path->save();

        return response()->json([]);
    }

    public function delete(Request $request, $id)
    {
        Cache::forget("plex_token_libraries");
        MoviesFolders::findOrFail($id)->delete();

        return response()->json([]);
    }

    public function deactivate(Request $request, $id)
    {
        Cache::forget("plex_token_libraries");
        MoviesFolders::where("id", $id)->update(['status' => 0]);

        return response()->json([]);
    }

    public function activate(Request $request, $id)
    {
        Cache::forget("plex_token_libraries");
        MoviesFolders::where("id", $id)->update(['status' => 1]);

        return response()->json([]);
    }

}
