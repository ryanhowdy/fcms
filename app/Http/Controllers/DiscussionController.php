<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Discussion;
use App\Models\DiscussionComment;

class DiscussionController extends Controller
{
    /**
     * Show the main discussions
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $discussions = Discussion::latest()
            ->join('users as cu', 'discussions.created_user_id', '=', 'cu.id')
            ->select('discussions.*', 'cu.name', 'cu.displayname')
            ->simplePaginate(25);

        return view('discussions.index', ['discussions' => $discussions]);
    }

    /**
     * Show the discussion create
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        return view('discussions.create');
    }

    /**
     * Store the new discussion in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'    => ['required'],
            'comments' => ['required'],
        ]);

        $discussion = new Discussion;

        $discussion->title           = $request->title;
        $discussion->created_user_id = Auth()->user()->id;
        $discussion->updated_user_id = Auth()->user()->id;

        $discussion->save();

        $comments = new DiscussionComment;

        $comments->comments        = $request->comments;
        $comments->discussion_id   = $discussion->id;
        $comments->created_user_id = Auth()->user()->id;
        $comments->updated_user_id = Auth()->user()->id;

        $comments->save();

        return redirect()->route('discussions');
    }

    /**
     * Show the discussion
     *
     * @return Illuminate\View\View
     */
    public function show(int $id)
    {
        $discussion = Discussion::findOrFail($id);

        // Update the view count
        $discussion->views++;
        $discussion->save();

        $comments = DiscussionComment::where('discussion_id', $id)
            ->join('users as cu', 'discussion_comments.created_user_id', '=', 'cu.id')
            ->select('discussion_comments.*', 'cu.name', 'cu.displayname')
            ->simplePaginate(25);

        return view('discussions.show', [
            'discussion' => $discussion,
            'comments'   => $comments,
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
