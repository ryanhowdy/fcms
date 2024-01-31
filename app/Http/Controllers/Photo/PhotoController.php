<?php

namespace App\Http\Controllers\Photo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Photo;
use App\Models\PhotoAlbum;
use Image;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    /**
     * Show the photo upload screen
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        $albums = PhotoAlbum::where('created_user_id', Auth()->user()->id)
            ->orderBy('name')
            ->get();

        return view('photos.create', [ 'albums' => $albums ]);
    }

    /**
     * Store the new discussion in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return json
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'album-name'        => ['required_without:album-id'],
            'album-descreption' => ['nullable'],
            'album-id'          => ['sometimes', 'exists:photo_albums,id'],
            'photo'             => ['required', 'mimetypes: image/bmp,image/gif,image/jpeg,image/png,image/svg+xml,image/webp'],
        ]);

        // Get the right path for photos and make sure it exists
        $fullPath = storage_path('app/photos').'/'.Auth()->user()->id;     // storage/app/photos/X
        $relPath  = '/photos/'.Auth()->user()->id;     // storage/app/photos/X
        Storage::makeDirectory($relPath);
        Storage::makeDirectory($relPath.'/full');
        Storage::makeDirectory($relPath.'/main');
        Storage::makeDirectory($relPath.'/thumbnail');

        // Get existing photo album
        if ($request->has('album-id'))
        {
            $album = PhotoAlbum::findOrFail($request->input('album-id'));
        }
        // Create a new photo album
        else
        {
            $album = new PhotoAlbum;

            $album->name            = $request->input('album-name');
            $album->description     = $request->input('album-description');
            $album->created_user_id = Auth()->user()->id;
            $album->updated_user_id = Auth()->user()->id;

            $album->save();
        }

        $return = [
            'album' => $album->toArray(),
        ];

        $file = $request->file('photo');

        // Add the photo to the db
        $photo = new Photo;

        $photo->photo_album_id  = $album->id;
        $photo->created_user_id = Auth()->user()->id;
        $photo->updated_user_id = Auth()->user()->id;

        $photo->save();

        // Update the photo db record filename
        $filename  = $photo->id.'.'.$file->extension();

        $photo->filename = $filename;

        $photo->save();

        // Store the full size photo
        $file->storeAs($relPath.'/full/', $filename);

        // Store the main photo
        $regular = Image::make($fullPath.'/full/'.$filename);

        $regular->resize(800, 800, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $regular->save($fullPath.'/main/'.$filename);

        // Store the thumbnail
        $thumb = Image::make($fullPath.'/main/'.$filename);

        $thumb->fit(200, 200);
        $thumb->save($fullPath.'/thumbnail/'.$filename);

        $return['photo'] = $photo->toArray();

        return response()->json($return);
    }

    /**
     * Show a photo from an album
     *
     * @param int $albumId 
     * @param int $photoId 
     * @return Illuminate\View\View
     */
    public function show(int $albumId, int $photoId)
    {
        $album = PhotoAlbum::where('photo_albums.id', $albumId)
            ->join('users as cu', 'photo_albums.created_user_id', '=', 'cu.id')
            ->select('photo_albums.*', 'photo_albums.name as album_name', 'cu.name', 'cu.displayname')
            ->with('photos')
            ->with('photos.comments')
            ->first();

        $photoIds = [];
        $exif     = [];

        foreach ($album->photos as $photo)
        {
            $photoIds[] = $photo->id;

            $path = storage_path('app/photos') . '/' . $photo->created_user_id . '/';

            if (file_exists($path . 'full/' . $photo->filename))
            {
                $exif[ $photo->id ] = Image::make($path . 'full/' . $photo->filename)->exif();
            }
        }

        return view('photos.photo', [
            'activePhoto' => $photoId,
            'album'       => $album,
            'exif'        => $exif
        ]);
    }
}