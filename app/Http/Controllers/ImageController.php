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
        return response()->file(storage_path('app/avatars') . '/' . $file);
    }

    /**
     * Show the main size photo for the given user
     */
    public function showPhoto($userId, $file)
    {
        return response()->file(storage_path('app/photos') . '/' . $userId . '/main/' . $file);
    }

    /**
     * Show the thumbnail size photo for the given user
     */
    public function showPhotoThumbnail($userId, $file)
    {
        $path = storage_path('app/photos') . '/' . $userId . '/thumbnail/';

        // Prior to fcms 4.0.0 we used to prefix the thumbnails with 'tb_'
        if (config('fcms.legacy'))
        {
            if (file_exists($path . 'tb_' . $file))
            {
                $file = 'tb_' . $file;
            }
        }

        return response()->file($path . $file);
    }

    /**
     * Show the full size photo for the given user
     */
    public function showPhotoFull($userId, $file)
    {
        return response()->file(storage_path('app/photos').'/'.$userId.'/full/'.$file);
    }

    /**
     * Show the video for the given user
     */
    public function showVideo($userId, $file)
    {
        return response()->file(storage_path('app/videos').'/'.$userId.'/'.$file);
    }
}
