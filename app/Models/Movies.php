<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movies extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING_INDEX = 0;
    const STATUS_INDEX_SUCCESS = 10;
    const TYPE_MOVIE = 1;
    const TYPE_TV_PARENT = 2;
    const TYPE_TV_EPISODE = 3;
    protected $guarded = [];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        parent::boot();

        static::created(function (Movies $movie) {
            MoviesMetas::updateOrCreate(["movies_id" => $movie->id], ["year" => 0]);
            MoviesTmdbs::updateOrCreate(["movies_id" => $movie->id], ["tmdb_id" => '', 'poster' => '/posters/missing.svg']);
            MoviesImdbs::updateOrCreate(["movies_id" => $movie->id], ["imdb_id" => '']);
        });
    }

    /**
     * Get the folder.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MoviesFolders::class, "folders_id", "id");
    }

    /**
     * Get the meta data.
     */
    public function meta(): HasOne
    {
        return $this->hasOne(MoviesMetas::class);
    }

    /**
     * Get the meta data.
     */
    public function imdb(): HasOne
    {
        return $this->hasOne(MoviesImdbs::class);
    }

    /**
     * Get the meta data.
     */
    public function tmdb(): HasOne
    {
        return $this->hasOne(MoviesTmdbs::class);
    }

    /**
     * Get the meta data.
     */
    public function subtitles(): HasOne
    {
        return $this->hasOne(MoviesSubtitles::class);
    }

    /**
     * Get the meta data.
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(MoviesLogs::class);
    }
}
