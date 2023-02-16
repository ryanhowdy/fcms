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
            ->leftJoin('user_settings as cus', 'users.id', '=', 'cus.user_id')
            ->select('users.*', 'cus.displayname')
            ->where('users.id', '!=', 1)
            ->get();

        return view('members.index', ['users' => $users]);
    }
}
