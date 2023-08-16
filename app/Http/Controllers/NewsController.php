<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\News;
use App\Models\NewsComment;
use App\Models\User;

class NewsController extends Controller
{
    /**
     * Show the main news page
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $recentNews = News::latest()
            ->join('users as cu', 'news.created_user_id', '=', 'cu.id')
            ->select('news.*', 'cu.name', 'cu.displayname', 'cu.avatar', 'cu.email')
            ->limit(6)
            ->get();

        $myNews = News::latest()
            ->join('users as cu', 'news.created_user_id', '=', 'cu.id')
            ->select('news.*', 'cu.name', 'cu.displayname', 'cu.avatar', 'cu.email')
            ->where('created_user_id', Auth()->user()->id)
            ->limit(4)
            ->get();

        $users = News::select('cu.id', 'cu.name', 'cu.displayname', 'cu.avatar', 'cu.email')
            ->join('users as cu', 'news.created_user_id', '=', 'cu.id')
            ->orderBy('news.updated_at')
            ->get()
            ->unique('id');

        foreach ($recentNews as $n)
        {
            $n->summary    = $n->news;
            $n->human_time = _gettext('Unknown');

            if ($n->created_at)
            {
                $n->human_time = $n->created_at->diffForHumans();
            }

            if (strlen($n->summary) > 150)
            {
                $n->summary = substr($n->summary, 0, 150);
            }
        }

        foreach ($myNews as $n)
        {
            $n->summary       = $n->news;
            $n->formattedTime = _gettext('Unknown');

            if ($n->created_at)
            {
                $n->formattedTime = $n->created_at->format('M jS');
            }

            if (strlen($n->summary) > 400)
            {
                $n->summary = substr($n->summary, 0, 400);
            }
        }

        return view('news.index', [
            'recent' => $recentNews,
            'news'   => $myNews,
            'users'  => $users
        ]);
    }

    /**
     * Show the family news create
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        return view('news.create');
    }

    /**
     * Store the new news in the db
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

        $news = new News;

        $news->title           = $request->title;
        $news->news            = $request->comments;
        $news->created_user_id = Auth()->user()->id;
        $news->updated_user_id = Auth()->user()->id;

        $news->save();

        return redirect()->route('familynews');
    }

    /**
     * Show the family news
     *
     * @param int $id
     * @return Illuminate\View\View
     */
    public function show(int $id)
    {
        $news = News::findOrFail($id);

        $comments = NewsComment::where('news_id', $id)
            ->join('users as cu', 'news_comments.created_user_id', '=', 'cu.id')
            ->select('news_comments.*', 'cu.name', 'cu.displayname', 'cu.avatar', 'cu.email')
            ->paginate(25);

        return view('news.show', [
            'news'     => $news,
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
            'news_id'  => ['required', 'exists:news,id'],
            'comments' => ['required'],
        ]);

        $comments = new NewsComment;

        $comments->comments        = $request->comments;
        $comments->news_id         = $request->news_id;
        $comments->created_user_id = Auth()->user()->id;
        $comments->updated_user_id = Auth()->user()->id;

        $comments->save();

        return redirect()->route('familynews');
    }

    /**
     * usersIndex
     *
     * Show the family news for a given user
     *
     * @param int $id
     * @return Illuminate\View\View
     */
    public function usersIndex(int $id)
    {
        $user = User::findOrFail($id);

        $news = News::where('created_user_id', $id)
            ->join('users as cu', 'news.created_user_id', '=', 'cu.id')
            ->select('news.*', 'cu.name', 'cu.displayname', 'cu.avatar', 'cu.email')
            ->paginate(25);

        $users = News::select('cu.id', 'cu.name', 'cu.displayname', 'cu.avatar', 'cu.email')
            ->join('users as cu', 'news.created_user_id', '=', 'cu.id')
            ->orderBy('news.updated_at')
            ->get()
            ->unique('id');

        foreach ($news as $n)
        {
            $n->summary       = $n->news;
            $n->formattedTime = _gettext('Unknown');

            if ($n->created_at)
            {
                $n->formattedTime = $n->created_at->format('M jS');
            }

            if (strlen($n->summary) > 400)
            {
                $n->summary = substr($n->summary, 0, 400);
            }
        }

        return view('news.usersIndex', [
            'user'  => $user,
            'news'  => $news,
            'users' => $users
        ]);
    }
}
