<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\News;
use App\Models\NewsComment;

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
            ->select('news.*', 'cu.name', 'cu.displayname')
            ->limit(4)
            ->get();

        $recent = [];
        foreach ($recentNews as $n)
        {
            $data = $n->toArray();

            $data['summary']    = $n->news;
            $data['human_time'] = $n->created_at->diffForHumans();

            if (strlen($data['summary']) > 150)
            {
                $data['summary'] = substr($data['summary'], 0, 150);
            }

            $recent[] = $data;
        }

        $news = News::latest()
            ->join('users as cu', 'news.created_user_id', '=', 'cu.id')
            ->select('news.*', 'cu.name', 'cu.displayname')
            ->limit(8)
            ->get();

        return view('news.index', [
            'recent' => $recent,
            'news'   => $news
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

        $news->news = htmlspecialchars($news->news, ENT_QUOTES, 'UTF-8');
        $news->news = \Illuminate\Mail\Markdown::parse($news->news);

        $comments = NewsComment::where('news_id', $id)
            ->join('users as cu', 'news_comments.created_user_id', '=', 'cu.id')
            ->select('news_comments.*', 'cu.name', 'cu.displayname')
            ->simplePaginate(25);

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
}
