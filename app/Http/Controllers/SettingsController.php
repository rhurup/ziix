<?php

namespace App\Http\Controllers;

use App\Models\MoviesClips;
use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SettingsController extends Controller
{
    //
    public function index(Request $request)
    {

        $settings = Settings::all();

        return response()->json($settings);
    }

    public function update(Request $request, $key)
    {

        Settings::set($key, $request->post("value", false));
        $settings = Settings::all();

        return response()->json($settings);
    }

}
