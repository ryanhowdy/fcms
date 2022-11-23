<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;

    /**
     * Get the album for this photo
     */
    public function album()
    {
        return $this->hasOne(PhotoAlbum::class);
    }
}
