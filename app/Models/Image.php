<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
    ];

    /**
     * Get the parent imageable model (product or category).
     *
     * @return MorphTo
     */
    public function imageable()
    {
        return $this->morphTo();
    }

    /**
     * Get the image URL.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return url('storage/'.$this->path);
    }
}
