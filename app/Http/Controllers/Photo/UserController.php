<?php

namespace App\Http\Controllers\Photo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PhotoUser;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * index
     *
     * Show the listing of users in photos
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $users = PhotoUser::distinct()
            ->select('u.*')
            ->join('users as u', 'photo_users.user_id', '=', 'u.id')
            ->orderBy('u.name')
            ->get();

        return view('photos.users', ['users' => $users]);
    }

    /**
     * show 
     *
     * Shows the photos of a given user
     * 
     * @param int $id 
     * @return Illuminate\View\View
     */
    public function show(int $id)
    {
        $user = User::find($id);

        $photos = PhotoUser::where('user_id', $id)
            ->with('photo')
            ->get();

        return view('photos.user', [
            'user'       => $user,
            'userPhotos' => $photos
        ]);
    }

    /**
     * Show a photo from an album
     *
     * @param int $userId 
     * @param int $photoId 
     * @return Illuminate\View\View
     */
    public function showPhoto(int $userId, int $photoId)
    {
        $user = User::find($userId);

        $photos = PhotoUser::where('user_id', $userId)
            ->join('users as u', 'photo_users.user_id', '=', 'u.id')
            ->select('photo_users.*', 'u.name', 'u.displayname')
            ->with('photo')
            ->with('photo.comments')
            ->get();

        $exif = [];

        //foreach ($photos as $userPhoto)
        //{
        //    $photo = $userPhoto->photo;

        //    $path = storage_path('app/photos') . '/' . $photo->created_user_id . '/';

        //    if (file_exists($path . 'full/' . $photo->filename))
        //    {
        //        $exif[ $photo->id ] = Image::make($path . 'full/' . $photo->filename)->exif();
        //    }
        //}

        return view('photos.user-photo', [
            'activePhoto' => $photoId,
            'user'        => $user,
            'userPhotos'  => $photos,
            'exif'        => $exif
        ]);
    }
}
