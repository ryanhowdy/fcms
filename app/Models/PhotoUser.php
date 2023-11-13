<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoUser extends Model
{
    use HasFactory;

    public function photo()
    {
        return $this->hasOne(Photo::class, 'id', 'photo_id');
    }
}
