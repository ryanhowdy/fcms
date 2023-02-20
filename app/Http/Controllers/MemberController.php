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
            ->where('users.id', '!=', 1)
            ->get();

        return view('members.index', ['users' => $users]);
    }
}
