<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MoviesFolders extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the comments for the blog post.
     */
    public function movies(): HasMany
    {
        return $this->hasMany(Movies::class);
    }
}
