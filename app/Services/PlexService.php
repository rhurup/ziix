<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PlexService
{

    public function __construct()
    {
        $this->token = Settings::get("plex_token", false);
        $this->url = Settings::get("plex_url", false);
    }

    public function getLibraries()
    {

        $response = Cache::remember("plex_token_libraries", 3600, function () {
            $client = new Client();
            $resp = $client->request('GET', $this->url . 'library/sections?X-Plex-Token=' . $this->token, [
                'headers' => [
                    'Accept' => 'application/xml'
                ]
            ]);
            $response = $resp->getBody()->getContents();
            return $response;
        });

        $responseXml = simplexml_load_string($response);

        if ($responseXml) {
            return $responseXml;
        }

        return [];
    }

}
