<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $url;

    protected $product;

    protected $type;

    public function __construct($url, Product $product, $type)
    {
        $this->url = $url;
        $this->product = $product;
        $this->type = $type;
    }

    public function handle()
    {
        $directory = $this->type === 'image' ? 'public/avatars/' : 'products/';
        $extension = $this->type == 'file' ? 'glb' : 'png';
        $filename = Str::random(40).'.'.$extension;

        $contents = file_get_contents($this->url);
        Storage::put($directory.$filename, $contents);

        // Save the file path to the database
        if ($this->type === 'image') {
            $this->product->images()->create([
                'path' => 'avatars/'.$filename,
            ]);
        } else {
            $this->product->files()->create([
                'name' => $filename,
                'path' => $directory.$filename,
            ]);
        }
    }
}
