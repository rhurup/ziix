<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MoviesLogs extends Model
{
    use HasFactory;

    const STATUS_FAILED = -1;
    const STATUS_PENDING = 0;
    const STATUS_SUCCESS = 10;
    const STATUS_PAUSED = 5;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function saveLog(int $movie_id, string $type, $params = [])
    {

        $log = new $this;
        $log->movies_id = $movie_id;
        $log->type = $type;
        $log->status = MoviesLogs::STATUS_PENDING;
        $log->params = json_encode($params);
        $log->save();

        return $log;
    }

    public function setMovie($movie_id)
    {
        $this->movies_id = $movie_id;

        return $this;
    }

    public function closeLog($status, $success = [], $error = [])
    {
        $this->status = $status;
        $this->success = json_encode($success);
        $this->error = json_encode($error);
        $this->save();
    }

    /**
     * Get the comments for the blog post.
     */
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movies::class, "movies_id");
    }
}
