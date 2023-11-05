<?php

namespace App\Http\Controllers\Photo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PhotoAlbum;
use Illuminate\Support\Facades\DB;

class AlbumController extends Controller
{
    /**
     * index
     *
     * Show the listing of albums
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $albums = PhotoAlbum::latest()
            ->with('photos')
            ->paginate(20);

        return view('photos.albums', ['albums' => $albums]);
    }

    /**
     * Show an album
     *
     * @param  int
     * @return Illuminate\View\View
     */
    public function show(int $id)
    {
        $album = PhotoAlbum::where('photo_albums.id', $id)
            ->join('users as cu', 'photo_albums.created_user_id', '=', 'cu.id')
            ->select('photo_albums.*', 'photo_albums.name as album_name', 'cu.name', 'cu.displayname')
            ->with('photos')
            ->with('comments')      // photo album comments
            ->with('users')         // tagged users in the album
            ->first();

        // Get unique users only
        $album->users = $album->users->unique('user_id');

        return view('photos.album', ['album' => $album]);
    }

}
