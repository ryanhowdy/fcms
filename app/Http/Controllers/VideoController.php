<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Video;
use App\Models\VideoComment;
use App\Models\ExternalVideo;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    /**
     * Show the main videos page
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $videos = Video::latest()
            ->get();

        return view('videos.index', ['videos' => $videos]);
    }

    /**
     * Show the video create
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        return view('videos.create');
    }

    /**
     * Store the new video in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required'],
            'description' => ['nullable'],
            'video'       => ['required', 'mimetypes: video/mp4,video/mpeg,video/quicktime,video/webm,video/x-msvideo'],
        ]);

        // Get the right path (storage/app/videos/X) for videos and make sure it exists
        $path = '/videos/'.Auth()->user()->id;
        Storage::makeDirectory($path);

        $file = $request->file('video');

        // Add the video to the db
        $video = new Video;

        $video->filename        = 'error';
        $video->title           = $request->title;
        $video->description     = $request->description;
        $video->created_user_id = Auth()->user()->id;
        $video->updated_user_id = Auth()->user()->id;

        $video->save();

        // Update the video db record filename
        $filename  = $video->id.'.'.$file->extension();

        $video->filename = $filename;

        $video->save();

        // Store the video
        Storage::putFileAs($path, $file, $filename);

        return redirect()->route('videos');
    }


    /**
     * Show the Video
     *
     * @return Illuminate\View\View
     */
    public function show(int $id)
    {
        $video = Video::findOrFail($id);

        $comments = VideoComment::where('video_id', $id)
            ->join('users as cu', 'video_comments.created_user_id', '=', 'cu.id')
            ->join('user_settings as cus', 'video_comments.created_user_id', '=', 'cus.user_id')
            ->select('video_comments.*', 'cu.fname', 'cu.mname', 'cu.lname', 'cus.displayname')
            ->simplePaginate(25);

        return view('videos.show', [
            'video'    => $video,
            'comments' => $comments,
        ]);
    }

    /**
     * Store the new comments in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function commentsStore(Request $request)
    {
        $validated = $request->validate([
            'discussion_id' => ['required', 'exists:discussions,id'],
            'comments'      => ['required'],
        ]);

        // Update the view count
        $discussion = Discussion::findOrFail($request->discussion_id);

        $comments = new DiscussionComment;

        $comments->comments        = $request->comments;
        $comments->discussion_id   = $discussion->id;
        $comments->created_user_id = Auth()->user()->id;
        $comments->updated_user_id = Auth()->user()->id;

        $comments->save();

        return redirect()->route('discussions');
    }

}
