<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;
    protected $primaryKey = 'media_id';
    protected $appends = ['thumbnail_url', 'extension'];

    protected $fillable = [
        'file_name',
        'file_path',
        'mime_type',
        'thumbnail_path',
    ];


    protected $hidden = [
        'mediaable_id',
        'mediaable_type',

        'created_at',
        'updated_at'
    ];
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail_path !== null) {
            return url(Storage::url($this->thumbnail_path)); // Generates the full URL
        }
    }


    // Define an accessor for the file extension
    public function getExtensionAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION); // Gets the file extension
    }

    public function mediaable()
    {
        return $this->morphTo();
    }
}
