<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class MemberController extends Controller
{
    /**
     * Show the list of members
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $users = User::latest()
            ->join('user_settings as cus', 'users.id', '=', 'cus.user_id')
            ->select('users.*', 'cus.displayname')
            ->get();

        return view('members.index', ['users' => $users]);
    }
}
