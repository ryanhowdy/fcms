<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Mail\PasswordReset;

class ForgotPasswordController extends Controller
{
    /**
     * Display the login view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create()
    {
        return view('auth.lostpw');
    }

    /**
     * Handle the forget password request
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $user = User::where('email', $request->email)
            ->first();

        if (is_null($user))
        {
            return redirect()->back()->withErrors(['email' => $request->email]);
        }

        $token = Str::random(64);

        DB::table('password_resets')->insert([
            'email'      => $request->email,
            'token'      => $token,
            'created_at' => Carbon::now(),
        ]);

        Mail::to($request->email)->send(new PasswordReset($user, $token));

        return redirect()->back()->with(['message' => _gettext('Email has been sent.')]);
    }
}
