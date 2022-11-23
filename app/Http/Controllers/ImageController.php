<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * Show the avatar file
     */
    public function showAvatar($file)
    {
        return response()->file(storage_path('app/avatars').'/'.$file);
    }

    /**
     * Show the main size photo for the given user
     */
    public function showPhoto($userId, $file)
    {
        return response()->file(storage_path('app/photos').'/'.$userId.'/main/'.$file);
    }

    /**
     * Show the thumbnail size photo for the given user
     */
    public function showPhotoThumbnail($userId, $file)
    {
        return response()->file(storage_path('app/photos').'/'.$userId.'/thumbnail/'.$file);
    }

    /**
     * Show the fulle size photo for the given user
     */
    public function showPhotoFull($userId, $file)
    {
        return response()->file(storage_path('app/photos').'/'.$userId.'/full/'.$file);
    }
}
