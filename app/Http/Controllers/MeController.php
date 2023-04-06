<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Address;
use App\Models\UserSetting;
use Image;
use DateTimeZone;

class MeController extends Controller
{
    /**
     * Show the create form for editting the user profile
     *
     * @return Illuminate\View\View
     */
    public function profileEdit()
    {
        return view('me.profile', [
            'user' => Auth()->user(),
        ]);
    }

    /**
     * Store the editted profile in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function profileUpdate(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string'],
            'displayname' => ['sometimes', 'nullable', 'string'],
            'birthday'    => ['required', 'date'],
            'bio'         => ['nullable', 'string'],
        ]);

        $user = User::findOrFail(Auth()->user()->id);

        $user->name     = $request->name;
        $user->bio      = $request->bio;
        $user->birthday = $request->bday;

        if ($request->has('displayname'))
        {
            $user->displayname = $request->displayname;
        }

        $user->save();

        return redirect()->route('my.profile');
    }

    /**
     * Show the create form for editting the user avatar
     *
     * @return Illuminate\View\View
     */
    public function avatarEdit()
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
    public function avatarUpdate(Request $request)
    {
        $validated = $request->validate([
            'avatar'       => ['required_without:avatar-other', 'mimetypes: image/bmp,image/gif,image/jpeg,image/png,image/svg+xml,image/webp'],
            'avatar-other' => ['required_without:avatar'],
        ],
        [
            'avatar.required_without'       => _gettext('Uploaded avatar must be an image.'),
            'avatar-other.required_without' => _gettext('You must upload an avatar or choose an existing avatar from the list.'),
        ]);

        // Get the right path for avatars and make sure it exists
        $fullPath = storage_path('app/avatars');    // storage/app/avatars
        $relPath  = '/avatars';

        Storage::makeDirectory($relPath);

        $filename = 'no_avatar.jpg';

        // Upload new avatar
        if ($request->has('avatar'))
        {
            $file = $request->file('avatar');

            // Resize and Save the avatar file
            $filename = uniqid("").'.'.$file->extension();

            $file->storeAs($relPath, $filename);

            $regular = Image::make($fullPath.'/'.$filename);
            $regular->fit(80, 80);
            $regular->save($fullPath.'/'.$filename);
        }
        // Choose existing avatar
        elseif ($request->has('avatar-other'))
        {
            switch ($request->input('avatar-other'))
            {
                case 'default':
                    $filename = 'no_avatar.jpg';
                    break;

                case 'gravatar':
                    $filename = 'gravatar';
                    break;

                default:
                    $filename = 'avataaars'.$request->input('avatar-other').'.png';
                    break;
            }
        }

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
    public function addressEdit()
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
    public function addressUpdate(Request $request)
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

    /**
     * settingsEdit 
     * 
     * @return Illuminate\View\View
     */
    public function settingsEdit()
    {
        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

        $languages = getListOfAvailableLanguages();

        return view('me.settings', [
            'user'      => Auth()->user(),
            'settings'  => Auth()->user()->settings,
            'timezones' => $timezones,
            'languages' => $languages,
        ]);
    }

    /**
     * settingsUpdate
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function settingsUpdate(Request $request)
    {
        $validated = $request->validate([
            'language' => ['required', 'string'],
            'timezone' => ['required', 'string'],
        ]);

        $setting = UserSetting::findOrFail(Auth()->user()->settings->id);

        $setting->language = $request->language;
        $setting->timezone = $request->timezone;

        $setting->save();

        return redirect()->route('my.settings');
    }
}
