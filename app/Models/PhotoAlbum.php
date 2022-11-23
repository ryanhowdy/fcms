<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoAlbum extends Model
{
    use HasFactory;

    /**
     * Get the comments for the discussion
     */
    public function photos()
    {
        return $this->hasMany(Photo::class);
    }
}
