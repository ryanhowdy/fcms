<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;

    public function album()
    {
        return $this->hasOne(PhotoAlbum::class);
    }

    public function comments()
    {
        return $this->hasMany(PhotoComment::class)
            ->select('photo_comments.*', 'cu.name', 'cu.displayname', 'cu.avatar', 'cu.email')
            ->join('users as cu', 'photo_comments.created_user_id', '=', 'cu.id');
    }

    public function users()
    {
        return $this->hasMany(PhotoUser::class)
            ->select('photo_users.*', 'u.name', 'u.displayname', 'u.avatar', 'u.email')
            ->join('users as u', 'photo_users.user_id', '=', 'u.id');
    }
}
