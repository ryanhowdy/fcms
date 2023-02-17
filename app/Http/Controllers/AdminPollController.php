<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Poll;

class AdminPollController extends Controller
{
    /**
     * Show the list of members
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $polls = Poll::latest()
            ->get();

        return view('admin.polls-index', [
            'polls' => $polls,
        ]);
    }
}
