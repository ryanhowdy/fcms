<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\NavigationLink;
use Illuminate\Support\Facades\Mail;
use App\Mail\Contact;

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
        return view('home');
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
        Mail::to(env('FCMS_CONTACT'))->send(new Contact($request->subject, $request->message));

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
