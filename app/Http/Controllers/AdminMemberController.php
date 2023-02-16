<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminMemberController extends Controller
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

        $levels = config('constants.ACCESS_LEVELS');

        return view('admin.members-index', [
            'users'  => $users,
            'levels' => $levels,
        ]);
    }

    /**
     * update 
     * 
     * Edit a member record
     * 
     * @param string $id 
     * @param Request $request 
     * @return null
     */
    public function update(string $id, Request $request)
    {
        $validated = $request->validate([
            'activated' => ['sometimes', 'boolean'],
        ]);

        $user = User::findOrFail($id);

        if ($request->has('activated'))
        {
            $user->activated = $request->activated;
        }
        if ($request->has('access'))
        {
            $user->access = $request->access;
        }

        $user->save();

        if ($request->wantsJson())
        {
            $user = $user->toArray();

            $user['text'] = $user['activated'] == 1 ? __('Yes') : __('No');

            return response()->json($user);
        }
    }
}
