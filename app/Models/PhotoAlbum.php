<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoAlbum extends Model
{
    use HasFactory;

    /**
     * photos 
     *
     * Will get the photos for the given album
     */
    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * comments 
     *
     * Will get the album comments
     */
    public function comments()
    {
        return $this->hasMany(PhotoAlbumComment::class)
            ->select('photo_album_comments.*', 'cu.name', 'cu.displayname', 'cu.avatar', 'cu.email')
            ->join('users as cu', 'photo_album_comments.created_user_id', '=', 'cu.id');
    }

    /**
     * users 
     *
     * Will get the unique users tagged in the given album
     */
    public function users()
    {
        return $this->hasManyThrough(PhotoUser::class, Photo::class)
            ->select('photo_users.*', 'u.name', 'u.displayname', 'u.avatar', 'u.email')
            ->join('users as u', 'photo_users.user_id', '=', 'u.id');
    }
}
