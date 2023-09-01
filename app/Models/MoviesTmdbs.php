<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MoviesTmdbs extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the user that owns the phone.
     */
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movies::class);
    }
}
