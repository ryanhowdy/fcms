<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use App\Models\Configuration;

class LoginController extends Controller
{
    /**
     * Display the login view
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        $config = Configuration::where('name', 'registration')
            ->select('value as registration')
            ->first();

        return view('auth.login', [
            'registrationOn' => $config->registration == 1 ? true : false,
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

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->route('home');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
}
