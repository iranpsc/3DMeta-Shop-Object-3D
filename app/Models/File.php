<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'path',
        'type',
        'size',
    ];

    protected static function booted(): void
    {
        static::deleting(function (File $file) {
            $fullPath = storage_path('app/' . $file->path);

            if (is_file($fullPath)) {
                unlink($fullPath);
            }
        });
    }

    /**
     * Get the product that owns the File
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Generate a signed download url for the file.
     *
     * Relative (path-only) so the browser keeps the current page scheme.
     * Absolute http:// URLs from APP_URL cause Mixed Content blocks on HTTPS.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return URL::signedRoute('files.download', ['file' => $this->id], absolute: false);
    }
}
