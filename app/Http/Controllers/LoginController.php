<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use App\Models\NavigationLink;

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

        return view('auth.login', [
            'registrationOn' => env('FCMS_ALLOW_REGISTRATION'),
        ]);
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
        ]);

        $remember = $request->has('remember-me') ? true : false;

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->route('home');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
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
