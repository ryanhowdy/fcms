<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Address;
use Image;

class MeController extends Controller
{
    /**
     * Show the create form for editting the user profile
     *
     * @return Illuminate\View\View
     */
    public function profileCreate()
    {
        $user = User::findOrFail(Auth()->user()->id);

        $days   = [];
        $months = [];
        $years  = [];

        $d = 1;
        while ($d <= 31)
        {
            $days[$d] = $d;
            $d++;
        }
        $m = 1;
        while ($m <= 12)
        {
            $months[$m] = date('F', mktime(0, 0, 0, $m));
            $m++;
        }
        $y = 1900;
        while ($y + 13 <= gmdate('Y'))
        {
            $years[$y] = $y;
            $y++;
        }

        return view('me.profile', [
            'days'     => $days,
            'months'   => $months,
            'years'    => array_reverse($years, true),
            'user'     => $user,
        ]);
    }

    /**
     * Store the editted profile in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function profileStore(Request $request)
    {
        $validated = $request->validate([
            'fname'  => ['required', 'string'],
            'lname'  => ['required', 'string'],
            'bday'   => ['required', 'integer', 'min:1', 'max:31'],
            'bmonth' => ['required', 'integer', 'min:1', 'max:12'],
            'byear'  => ['required', 'integer', 'min:1900'],
            'mname'  => ['nullable', 'string'],
            'maiden' => ['nullable', 'string'],
            'bio'    => ['nullable', 'string'],
        ]);

        $user = User::findOrFail(Auth()->user()->id);

        $user->fname     = $request->fname;
        $user->mname     = $request->mname;
        $user->lname     = $request->lname;
        $user->maiden    = $request->maiden;
        $user->bio       = $request->bio;
        $user->dob_day   = $request->bday;
        $user->dob_month = $request->bmonth;
        $user->dob_year  = $request->byear;

        $user->save();

        return redirect()->route('my.profile');
    }

    /**
     * Show the create form for editting the user avatar
     *
     * @return Illuminate\View\View
     */
    public function avatarCreate()
    {
        $user = User::findOrFail(Auth()->user()->id);

        $avatarType = 'default';

        if (!is_null($user->gravatar))
        {
            $avatarType = 'gravatar';
        }
        elseif ($user->avatar !== 'no_avatar.jpg')
        {
            $avatarType = 'avatar';
        }

        return view('me.avatar', [
            'avatarType' => $avatarType,
            'user'       => $user
        ]);
    }

    /**
     * Store the avatar in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function avatarStore(Request $request)
    {
        $validated = $request->validate([
            'avatar' => ['required', 'mimetypes: image/bmp,image/gif,image/jpeg,image/png,image/svg+xml,image/webp'],
        ]);

        // Get the right path for avatars and make sure it exists
        $fullPath = storage_path('app/avatars');    // storage/app/avatars
        $relPath  = '/avatars';

        Storage::makeDirectory($relPath);

        $file = $request->file('avatar');

        // Resize and Save the avatar file
        $filename = uniqid("").'.'.$file->extension();

        $file->storeAs($relPath, $filename);

        $regular = Image::make($fullPath.'/'.$filename);
        $regular->fit(80, 80);
        $regular->save($fullPath.'/'.$filename);

        // Save the avatar in the db
        $user = User::findOrFail(Auth()->user()->id);

        $user->avatar = $filename;
        $user->save();

        return redirect()->route('my.avatar');
    }

    /**
     * Show the create form for editting the user ddress
     *
     * @return Illuminate\View\View
     */
    public function addressCreate()
    {
        $user    = User::findOrFail(Auth()->user()->id);
        $address = Address::where('user_id', Auth()->user()->id)->get();

        $names     = file_get_contents('http://country.io/names.json');
        $countries = json_decode($names, true);

        asort($countries);

        return view('me.address', [
            'user'      => $user,
            'address'   => $address[0],
            'countries' => $countries,
        ]);
    }

    /**
     * Store the editted address in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function addressStore(Request $request)
    {
        $validated = $request->validate([
            'country' => ['nullable', 'string', 'min:2', 'max:2'],
            'address' => ['nullable', 'string'],
            'city'    => ['nullable', 'string'],
            'state'   => ['nullable', 'string'],
            'zip'     => ['nullable', 'string'],
            'cell'    => ['nullable', 'string'],
            'home'    => ['nullable', 'string'],
            'work'    => ['nullable', 'string'],
        ]);

        $address = Address::where('user_id', Auth()->user()->id)->get();

        $address[0]->country = $request->country;
        $address[0]->address = $request->address;
        $address[0]->city    = $request->city;
        $address[0]->state   = $request->state;
        $address[0]->zip     = $request->zip;
        $address[0]->cell    = $request->cell;
        $address[0]->home    = $request->home;
        $address[0]->work    = $request->work;

        $address[0]->save();

        return redirect()->route('my.address');
    }
}
