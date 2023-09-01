<?php

namespace App\Http\Controllers;

use App\Http\Responses\JsonResponse;
use App\Models\MoviesLogs;
use App\Services\Settings;
use Illuminate\Http\Request;

class MoviesJobsController extends Controller
{
    //
    public function index(Request $request)
    {

        $skip = $request->get("skip", 0);
        $take = $request->get("take", 50);
        $jobs = MoviesLogs::with("movie")->skip($skip)->take($take)->orderBy("created_at", "desc")->get();

        $states = [
            -1 => "Failed",
            0 => "Pending",
            5 => "Paused",
            10 => "Success",
        ];

        return response()->json(['jobs' => $jobs, "states" => $states]);
    }

    //
    public function show(Request $request, $id)
    {

        $jobs = MoviesLogs::where("movies_id", $id)->orderBy("created_at", "asc")->get();

        return response()->json($jobs);
    }

    public function pause(Request $request)
    {

        MoviesLogs::where("status", MoviesLogs::STATUS_PENDING)->update(['status' => MoviesLogs::STATUS_PAUSED]);
        Settings::set("files_finder", false);
        Settings::set("files_index", false);
        Settings::set("paused", true);

        return response()->json([]);
    }

    public function resume(Request $request)
    {

        MoviesLogs::where("status", MoviesLogs::STATUS_PAUSED)->update(['status' => MoviesLogs::STATUS_PENDING]);
        Settings::set("files_finder", true);
        Settings::set("files_index", true);
        Settings::set("paused", false);

        return response()->json([]);
    }

}
