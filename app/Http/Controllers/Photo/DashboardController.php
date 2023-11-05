<?php

namespace App\Http\Controllers\Photo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PhotoAlbum;

class DashboardController extends Controller
{
    /**
     * Show the main photos
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $albums = PhotoAlbum::latest()
            ->with('photos')
            ->limit(4)
            ->get();

        return view('photos.index', ['albums' => $albums]);
    }
}
