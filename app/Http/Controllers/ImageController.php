<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function showAvatar($file)
    {
        return response()->file(storage_path('app/avatars').'/'.$file);
    }
}
