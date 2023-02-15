<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\Registration;

class RegisterController extends Controller
{
    /**
     * Display the register view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle the registration request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed'],
            'fname'    => ['required', 'max:255'],
            'bday'     => ['required', 'date'],
        ],
        [
            'email.required'     => __('Email is required.'),
            'password.required'  => __('Password is required.'),
            'password.confirmed' => __('The passwords do not match.'),
            'fname.required'     => __('First Name is required.'),
            'bday.required'      => __('Birthday is required.'),
        ]);

        $birthday = Carbon::createFromDate($request->bday);

        $user = new User;

        $user->email     = $request->email;
        $user->password  = Hash::make($request->password);
        $user->fname     = $request->fname;
        $user->dob_year  = $birthday->format('Y');
        $user->dob_month = $birthday->format('m');
        $user->dob_day   = $birthday->format('d');

        if ($request->has('lname'))
        {
            $user->lname = $request->lname;
        }

        $user->save();

        Mail::to($user->email)->send(new Registration($user));

        return redirect()->route('index');
    }
}
