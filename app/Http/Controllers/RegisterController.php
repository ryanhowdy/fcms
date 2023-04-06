<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\Address;
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
            'name'     => ['required', 'max:255'],
            'bday'     => ['required', 'date'],
        ],
        [
            'email.required'     => _gettext('Email is required.'),
            'password.required'  => _gettext('Password is required.'),
            'password.confirmed' => _gettext('The passwords do not match.'),
            'name.required'      => _gettext('Full Name is required.'),
            'bday.required'      => _gettext('Birthday is required.'),
        ]);

        $user = new User;

        $user->email     = $request->email;
        $user->password  = Hash::make($request->password);
        $user->name      = $request->name;
        $user->birthday  = $request->bday;

        if ($request->has('displayname'))
        {
            $user->displayname = $request->displayname;
        }

        $user->save();

        // Create the user settings for the new user
        $settings = new UserSetting;

        $settings->user_id = $user->id;

        $settings->save();

        // Create an address for the new user
        $address = new Address;

        $address->user_id         = $user->id;
        $address->created_user_id = $user->id;
        $address->updated_user_id = $user->id;

        $address->save();

        Mail::to($user->email)->send(new Registration($user));

        return redirect()->route('index');
    }
}
