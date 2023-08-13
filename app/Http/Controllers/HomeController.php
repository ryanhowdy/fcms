<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\NavigationLink;
use Illuminate\Support\Facades\Mail;
use App\Mail\Contact;
use App\Models\ViewWhatsNewUpdate;
use App\Models\User;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * Redirects to install, login or home page
     *
     * @return Illuminate\Support\Facades\View
     */
    public function index()
    {
        if (!$this->isSiteInstalled())
        {
            return redirect()->to('/install');
        }

        if (!Auth()->user()) {
            return redirect()->to('/login');
        }

        return redirect()->to('/home');
    }

    /**
     * Show the home page
     *
     * @return Illuminate\View\View
     */
    public function home()
    {
        // Update the user activity
        $user = User::findOrFail(Auth()->user()->id);

        $user->activity = Carbon::now();
        $user->save();

        $updates = ViewWhatsNewUpdate::latest()
            ->orderBy('updated_at')
            ->paginate(30);

        $poll = Poll::latest()->first();

        $pollData = $poll->toArray();

        $pollData['options']     = [];
        $pollData['total_votes'] = 0;

        foreach ($poll->options as $option)
        {
            $pollData['options'][$option->id]                = $option->toArray();
            $pollData['options'][$option->id]['total_votes'] = $pollData['options'][$option->id]['votes'];
            $pollData['options'][$option->id]['votes']       = [];
        }

        foreach ($poll->votes as $vote)
        {
            $pollData['options'][$vote->option_id]['votes'][] = $vote->toArray();
            $pollData['total_votes'] += 1;

            if ($vote->created_user_id == $user->id)
            {
                $pollData['current_user_voted'] = true;
            }
        }

        return view('home', [
            'updates' => $updates,
            'poll'    => $pollData
        ]);
    }

    /**
     * vote
     *
     * @param  Illuminate\Http\Request  $request
     * @return Illuminate\View\View
     */
    public function vote(Request $request)
    {
        $request->validate([
            'option' => ['required', 'int'],
        ]);

        $option = PollOption::findOrFail($request->option);

        $option->votes++;
        $option->save();

        $vote = new PollVote();

        $vote->option_id       = $request->option;
        $vote->poll_id         = $option->poll_id;
        $vote->created_user_id = Auth()->user()->id;
        $vote->updated_user_id = Auth()->user()->id;
        $vote->save();

        return redirect()->route('home');
    }

    /**
     * Show the contact page
     *
     * @return Illuminate\View\View
     */
    public function contact()
    {
        return view('contact');
    }

    /**
     * Send the contact message
     *
     * @param  Illuminate\Http\Request  $request
     * @return Illuminate\View\View
     */
    public function contactSend(Request $request)
    {
        Mail::to(config('fcms.contact'))->send(new Contact($request->subject, $request->message));

        return redirect()->route('contact');
    }

    /**
     * Check if the site has been installed yet.
     *
     * @return boolean
     */
    private function isSiteInstalled()
    {
        try
        {
            $links = NavigationLink::where('route_name', 'home')
                ->get();

            if ($links->isEmpty())
            {
                return false;
            }
        }
        catch (\Exception $e)
        {
            return false;
        }

        return true;
    }
}
