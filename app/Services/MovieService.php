<?php

namespace App\Services;

use App\Models\Movies;
use App\Models\MoviesMetas;
use App\Models\MoviesSubtitles;
use Illuminate\Support\Str;

class MovieService
{
    public $id;

    public $seasons = [
        'S01' => 1,
        'S02' => 2,
        'S03' => 3,
        'S04' => 4,
        'S05' => 5,
        'S06' => 6,
        'S07' => 7,
        'S08' => 8,
        'S09' => 9,
        'S10' => 10,
        'S11' => 11,
        'S12' => 12,
        'S13' => 13,
        'S14' => 14,
        'S15' => 15,
        'S16' => 16,
        'S17' => 17,
        'S18' => 18,
        'S19' => 19,
        'S20' => 20,
        'S21' => 21,
        'S22' => 22,
        'S23' => 23,
        'S24' => 24,
        'S25' => 25,
        'S26' => 26,
        'S27' => 27,
        'S28' => 28,
        'S29' => 29,
        'S30' => 30,
        'S31' => 31,
        'S32' => 32,
        'S33' => 33,
        'S34' => 34,
        'S35' => 35,
        'S36' => 36,
        'S37' => 37,
        'S38' => 38,
        'S39' => 39,
        'S40' => 40,
        'S41' => 41,
        'S42' => 42,
        'S43' => 43,
        'S44' => 44,
        'S45' => 45,
    ];
    public $episodes = [
        'E01' => 1,
        'E02' => 2,
        'E03' => 3,
        'E04' => 4,
        'E05' => 5,
        'E06' => 6,
        'E07' => 7,
        'E08' => 8,
        'E09' => 9,
        'E10' => 10,
        'E11' => 11,
        'E12' => 12,
        'E13' => 13,
        'E14' => 14,
        'E15' => 15,
        'E16' => 16,
        'E17' => 17,
        'E18' => 18,
        'E19' => 19,
        'E20' => 20,
        'E21' => 21,
        'E22' => 22,
        'E23' => 23,
        'E24' => 24,
        'E25' => 25,
        'E26' => 26,
        'E27' => 27,
        'E28' => 28,
        'E29' => 29,
        'E30' => 30,
        'E31' => 31,
        'E32' => 32,
        'E33' => 33,
        'E34' => 34,
        'E35' => 35,
        'E36' => 36,
        'E37' => 37,
        'E38' => 38,
        'E39' => 39,
        'E40' => 40,
        'E41' => 41,
        'E42' => 42,
        'E43' => 43,
        'E44' => 44,
        'E45' => 45,
    ];
    public $resolutions = [
        '480p' => [
            'width' => 720,
            'height' => 480
        ],
        '576p' => [
            'width' => 720,
            'height' => 576
        ],
        '720p' => [
            'width' => 1280,
            'height' => 720
        ],
        '1080i' => [
            'width' => 1440,
            'height' => 1080
        ],
        '1080p' => [
            'width' => 1920,
            'height' => 1080
        ],
        '4K' => [
            'width' => 3840,
            'height' => 2160
        ],
    ];

    public $tracks = [
        't00' => 1,
        't01' => 1,
        't02' => 1,
        't03' => 1,
        't04' => 1,
        't05' => 1,
        't06' => 1,
        't07' => 1,
        't08' => 1,
        't09' => 1,
        't10' => 1,
    ];
    public $removeTags = [
        'DKSubs' => 1,
        'BluRay' => 1,
        'x264' => 1,
        'UNiTAiL' => 1,
        'NORDiC' => 1,
        'RAPiDCOWS' => 1,
    ];

    public function __construct($id = 0)
    {
        $this->video = new \stdClass();
        $this->id = $id;
    }

    public function isDirEmpty($dir)
    {
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
        return true;
    }

    public function saveMeta($movie)
    {
        MoviesMetas::updateOrCreate(
            [
                "movies_id" => $movie->id
            ],
            [
                "status" => Movies::STATUS_INDEX_SUCCESS,
                "year" => $this->video->year ?? 0,
                "resolution" => $this->video->resolution ?? '',
                "length" => $this->video->duration ?? 0,
                "encoding" => $this->video->encoding ?? '',
                "width" => $this->video->width ?? 0,
                "height" => $this->video->height ?? 0,
                "dataset" => json_encode($this->video ?? []),
            ]
        );

    }

    public function saveSubtitles($movie)
    {
        foreach ($this->video->subs as $sub) {
            MoviesSubtitles::updateOrCreate(["movies_id" => $movie->id, "path" => $movie->filename, "language" => $sub], ["status" => Movies::STATUS_INDEX_SUCCESS]);
        }
    }

    public function saveData($movie)
    {
        $movie->status = Movies::STATUS_INDEX_SUCCESS;
        $movie->title = $this->video->movie ?? $movie->title;
        $movie->season = $this->video->season ?? 0;
        $movie->episode = $this->video->episode ?? 0;
        $movie->extension = $this->video->extension ?? $movie->extension;
        $movie->save();
    }

    public function getMovieData($path, $movie_path, $movie_filename)
    {
        $this->getFFProbeData($path, $movie_path, $movie_filename);

        $videoStream = $this->findVideoStream();

        $this->findSubs();
        $this->getResolution($videoStream);
        $this->getMovieName();

        return $this;
    }

    public function getFFProbeData($path, $movie_path, $movie_filename)
    {

        $command = Settings::get("ffprobe_path", "ffprobe") . " -v quiet -print_format json -show_format -show_streams -safe 0 -i";
        $filepath = str_replace(' ', '\ ', $path . '/' . $movie_path . '' . $movie_filename);
        $this->video = json_decode(shell_exec($command . ' ' . $filepath));

        unset($this->video->format->filename);

        $this->video->hash = hash_hmac('ripemd160', json_encode($this->video), 'secret');

        $this->video->name = $movie_filename;
        $this->video->path = $path . '/' . $movie_path;

        return $this;
    }

    public function findVideoStream()
    {
        foreach ($this->video->streams as $stream) {
            if ($stream->codec_type == 'video') {
                return $stream;
            }
        }
    }

    public function findSubs()
    {
        $subs = [];
        foreach ($this->video->streams as $stream) {
            if ($stream->codec_type == 'subtitle') {
                $subs[] = $stream->tags->language;
            }
        }
        $this->video->subs = array_filter($subs);
    }

    public function getResolution($stream)
    {
        $res = '';

        foreach ($this->resolutions as $resolutionType => $resolution) {
            if ($stream->width >= $resolution['width'] || $stream->height >= $resolution['height']) {
                $res = $resolutionType;
            }
        }

        $this->video->resolution = $res;
        $this->video->encoding = $stream->codec_name;
        $this->video->format = $stream->codec_long_name;
        $this->video->height = $stream->height;
        $this->video->width = $stream->width;
        $this->video->duration = $stream->tags->DURATION ?? 0;
    }

    public function getMovieName()
    {
        $name = Str::of($this->video->name)
            ->replace(" ", ".")
            ->replace("_", ".")
            ->replace("-", ".")
            ->toString();

        $this->video->extension = $this->getMovieExtension($name);
        $this->video->year = $this->getMovieYear($name);
        $this->video->season = 0;
        $this->video->episode = 0;

        $this->video->movie = $this->getName($name);

        if (Settings::get("files_rename", 0)) {
            $this->video->new_name = Str::of($this->video->movie)->replace("-", ".")->replace(" ", ".")->toString() . '.'
                . $this->video->year . '.'
                . $this->video->resolution . "."
                . $this->video->extension;
        } else {
            $this->video->new_name = $this->video->movie;
        }

        return $this;
    }

    public function getMovieExtension($name)
    {
        return Str::of($name)
            ->explode(".")
            ->last();
    }

    public function getMovieYear($name)
    {
        return (int)Str::of($name)
            ->explode(".")
            ->skip(1)
            ->map(function ($value) {
                if ((int)$value > 1900) {
                    return $value;
                }
                return null;
            })
            ->implode("");
    }

    public function map($path, $recursive = false)
    {
        $result = array();

        if (is_dir($path) === true) {
            $path = $this->path($path);
            $files = array_diff(scandir($path), array('.', '..'));

            foreach ($files as $file) {
                if (is_dir($path . $file) === true) {
                    $result[$file] = ($recursive === true) ? $this->map($path . $file, $recursive) : $this->size($path . $file, true);
                } else if (is_file($path . $file) === true) {
                    $result[$file] = $this->size($path . $file);
                }
            }
        } else if (is_file($path) === true) {
            $result[basename($path)] = $this->size($path);
        }

        return $result;
    }

    public function path($path)
    {
        if (file_exists($path) === true) {
            $path = rtrim(str_replace('\\', '/', realpath($path)), '/');

            if (is_dir($path) === true) {
                $path .= '/';
            }

            return $path;
        }

        return false;
    }

    function size($path, $recursive = true)
    {
        $result = 0;

        if (is_dir($path) === true) {
            $path = $this->path($path);
            $files = array_diff(scandir($path), array('.', '..'));

            foreach ($files as $file) {
                if (is_dir($path . $file) === true) {
                    $result += ($recursive === true) ? $this->size($path . $file, $recursive) : 0;
                } else if (is_file() === true) {
                    $result += sprintf('%u', filesize($path . $file));
                }
            }
        } else if (is_file($path) === true) {
            $result += sprintf('%u', filesize($path));
        }

        return $result;
    }

    public function getName($name)
    {
        return Str::of($name)
            ->explode(".")
            ->map(function ($value) {
                if (key_exists($value, $this->tracks)) {
                    return null;
                }
                if (key_exists($value, $this->seasons)) {
                    return null;
                }
                if (key_exists($value, $this->episodes)) {
                    return null;
                }
                if (key_exists($value, $this->resolutions)) {
                    return null;
                }
                if (key_exists($value, $this->removeTags)) {
                    return null;
                }
                if ($value == $this->video->extension) {
                    return null;
                }
                if ($value == $this->video->year) {
                    return null;
                }
                return $value;
            })
            ->filter()
            ->implode(" ");
    }

    public function getSeriesData($path, $movie_path, $movie_filename)
    {
        $this->getFFProbeData($path, $movie_path, $movie_filename);

        $videoStream = $this->findVideoStream();

        $this->findSubs();
        $this->getResolution($videoStream);
        $this->getSeriesName();

        return $this;
    }

    public function getSeriesName()
    {
        $name = Str::of($this->video->name)
            ->replace(" ", ".")
            ->replace("_", ".")
            ->replace("-", ".")
            ->toString();

        $this->video->extension = $this->getMovieExtension($name);
        $this->video->year = $this->getMovieYear($name);
        $this->video->season = $this->getMovieSeason($name);
        $this->video->episode = $this->getMovieEpisode($name);

        $this->video->movie = $this->getName($name);

        if (Settings::get("files_rename", 0)) {
            $this->video->new_name = Str::of($this->video->movie)->replace("-", ".")->replace(" ", ".")->toString() . '.'
                . $this->video->season . '.'
                . $this->video->episode . '.'
                . $this->video->year . '.'
                . $this->video->resolution . "."
                . $this->video->extension;
        } else {
            $this->video->new_name = $this->video->movie;
        }

        return $this;
    }

    public function getMovieSeason($name)
    {
        return (int)Str::of($name)
            ->explode(".")
            ->skip(1)
            ->map(function ($value) {
                if (key_exists($value, $this->seasons)) {
                    return $this->seasons[$value];
                }
                return null;
            })
            ->implode("");
    }

    public function getMovieEpisode($name)
    {
        return (int)Str::of($name)
            ->explode(".")
            ->skip(1)
            ->map(function ($value) {
                if (key_exists($value, $this->episodes)) {
                    return $this->episodes[$value];
                }
                return null;
            })
            ->implode("");
    }

    public function isEpisode($name)
    {
        $this->video->extension = $this->getMovieExtension($name);
        $this->video->year = $this->getMovieYear($name);

        $name = Str::of($name)
            ->replace(" ", ".")
            ->replace("_", ".")
            ->replace("-", ".")
            ->toString();

        $isEpisode = false;
        Str::of($name)->contains(array_keys($this->seasons)) ? $isEpisode = true : null;
        Str::of($name)->contains(array_keys($this->episodes)) ? $isEpisode = true : null;

        if ($isEpisode) {
            return $this->getName($name);
        }

        return $isEpisode;
    }
}
