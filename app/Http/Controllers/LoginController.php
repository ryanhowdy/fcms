<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\NavigationLink;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Display the login view
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        try
        {
            $links = NavigationLink::where('route_name', 'home')
                ->get();

            if ($links->isEmpty())
            {
                return redirect()->to('/install');
            }
        }
        catch (\Exception $e)
        {
            return redirect()->to('/install');
        }

        return view('auth.login');
    }

    /**
     * Handle the login request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ],
        [
            'email.required'    => __('Email is required.'),
            'password.required' => __('Password is required.'),
        ]);

        // Check if the user is activated
        $user = User::where('email', $request->email)->first();

        if (!$user->activated)
        {
            Session::flash('header',  __('Not so fast.'));
            Session::flash('message', __('Your account isn\'t active yet.  Your website administrator must activate your account before you can login and begin using the website.'));

            return back();
        }

        $remember = $request->has('remember-me') ? true : false;

        // Check credentials
        if (Auth::attempt($credentials, $remember))
        {
            $request->session()->regenerate();

            return redirect()->route('home');
        }

        Session::flash('header',  __('Oops!'));
        Session::flash('message', __("That login information wasn't quite right. Be sure and check that you typed your email/password correctly."));

        return back();
    }

    /**
     * logout
     *
     * @param Request $request
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::guard()->logout();
        $request->session()->flush();
    
        return redirect()->route('index');
    }
}
