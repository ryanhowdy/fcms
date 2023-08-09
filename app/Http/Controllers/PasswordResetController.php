<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    /**
     * Display the login view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create($token)
    {
        return view('auth.password-reset', ['token' => $token]);
    }

    /**
     * Handle the login request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'email', 'exists:users,email'],
            'token'    => ['required', 'exists:password_resets,token'],
            'password' => ['required', 'confirmed'],
        ]);

        $user = User::where('email', $request->email)
            ->first();

        $user->password = Hash::make($request->password);

        $user->save();

        return redirect()->route('login');
    }
}
