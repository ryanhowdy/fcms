<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\Configuration;
use App\Models\NavigationLink;

class InstallController extends Controller
{
    private $totalSteps = 4;

    /**
     * Show the application dashboard.
     *
     * @return Illuminate\Support\Facades\View
     */
    public function index()
    {
        if ($this->isStepThreeComplete())
        {
            return redirect()->route('auth.login');
        }
        else if ($this->isStepTwoComplete())
        {
            return redirect()->route('install.admin');
        }
        else if ($this->isStepOneComplete())
        {
            return redirect()->route('install.config');
        }

        return redirect()->route('install.database');
    }

    /**
     * Has step one (create tables) been completed
     *
     * @return boolean
     */
    private function isStepOneComplete()
    {
        try
        {
            if (Schema::hasTable('addresses'))
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            return false;
        }
    }

    /**
     * Has step two (configuration) been completed
     *
     * @return boolean
     */
    private function isStepTwoComplete()
    {
        $config = Configuration::where('name', 'registration')
            ->select('value as registration')
            ->first();

        if (empty($config))
        {
            return false;
        }

        return true;
    }

    /**
     * Has step three (admin user) been completed
     *
     * @return boolean
     */
    private function isStepThreeComplete()
    {
        $admin = User::select()
            ->first();

        if (empty($admin))
        {
            return false;
        }

        return true;
    }

    /**
     * Show the database setup view
     *
     * @return Illuminate\View\View
     */
    public function database()
    {
        $percent = round((1 / $this->totalSteps) * 100);

        return view('install.database', [ 'progress' => $percent ]);
    }

    /**
     * Show the configuration form
     *
     * @return Illuminate\View\View
     */
    public function configurationCreate()
    {
        $percent = round((2 / $this->totalSteps) * 100);

        return view('install.config', [ 'progress' => $percent ]);
    }

    /**
     * Store the configuration in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function configurationStore(Request $request)
    {
        $validated = $request->validate([
            'sitename' => ['required', 'max:255'],
            'contact'  => ['required', 'email'],
        ]);

        Configuration::insert([
            [
                'name'  => 'current_version',
                'value' => '4.0.0',
            ],
            [
                'name'  => 'sitename',
                'value' => $request->sitename,
            ],
            [
                'name'  => 'contact',
                'value' => $request->contact,
            ],
            [
                'name'  => 'auto_activate',
                'value' => 0,
            ],
            [
                'name'  => 'registration',
                'value' => 1,
            ],
            [
                'name'  => 'full_size_photos',
                'value' => 0,
            ],
            [
                'name'  => 'site_off',
                'value' => 0,
            ],
            [
                'name'  => 'country',
                'value' => 'US',
            ],
        ]);

        NavigationLink::insert([
            [
                'link'       => __('Home'),
                'route_name' => null,
                'group'      => 1,
                'order'      => 1,
            ],
            [
                'link'       => __('Home'),
                'route_name' => 'home',
                'group'      => 1,
                'order'      => 2,
            ],
            [
                'link'       => __('Calendar'),
                'route_name' => 'calendar',
                'group'      => 1,
                'order'      => 3,
            ],
            [
                'link'       => __('Members'),
                'route_name' => 'members',
                'group'      => 1,
                'order'      => 4,
            ],
            [
                'link'       => __('Addresses'),
                'route_name' => 'addresses',
                'group'      => 1,
                'order'      => 5,
            ],
            [
                'link'       => __('Communicate'),
                'route_name' => null,
                'group'      => 2,
                'order'      => 1,
            ],
            [
                'link'       => __('Discussions'),
                'route_name' => 'discussions',
                'group'      => 2,
                'order'      => 2,
            ],
            [
                'link'       => __('Share'),
                'route_name' => null,
                'group'      => 3,
                'order'      => 1,
            ],
            [
                'link'       => __('Photos'),
                'route_name' => 'photos',
                'group'      => 3,
                'order'      => 2,
            ],
            [
                'link'       => __('Videos'),
                'route_name' => 'videos',
                'group'      => 3,
                'order'      => 3,
            ],
            [
                'link'       => __('Misc.'),
                'route_name' => null,
                'group'      => 4,
                'order'      => 1,
            ],
            [
                'link'       => __('Contact'),
                'route_name' => 'contact',
                'group'      => 4,
                'order'      => 2,
            ],
            [
                'link'       => __('Help'),
                'route_name' => 'help',
                'group'      => 4,
                'order'      => 3,
            ],
            [
                'link'       => __('Admin'),
                'route_name' => null,
                'group'      => 5,
                'order'      => 1,
            ],
            [
                'link'       => __('Upgrade'),
                'route_name' => 'admin.upgrade',
                'group'      => 5,
                'order'      => 2,
            ],
            [
                'link'       => __('Configuration'),
                'route_name' => 'admin.config',
                'group'      => 5,
                'order'      => 3,
            ],
            [
                'link'       => __('Members'),
                'route_name' => 'admin.members',
                'group'      => 5,
                'order'      => 3,
            ],
            [
                'link'       => __('Photo Gallery'),
                'route_name' => 'admin.photos',
                'group'      => 5,
                'order'      => 4,
            ],
            [
                'link'       => __('Polls'),
                'route_name' => 'admin.polls',
                'group'      => 5,
                'order'      => 5,
            ],
            [
                'link'       => __('Facebook'),
                'route_name' => 'admin.facebook',
                'group'      => 5,
                'order'      => 6,
            ],
            [
                'link'       => __('Google'),
                'route_name' => 'admin.google',
                'group'      => 5,
                'order'      => 7,
            ],
            [
                'link'       => __('Instagram'),
                'route_name' => 'admin.instagram',
                'group'      => 5,
                'order'      => 8,
            ],
        ]);

        $communicatePlugins = [
            'familynews' => [
                'link'       => __('Family News'),
                'route_name' => 'familynews',
                'group'      => 2,
            ],
            'prayers' => [
                'link'       => __('Prayer Concerns'),
                'route_name' => 'prayers',
                'group'      => 2,
            ],
        ];
        $sharePlugins = [
            'recipes' => [
                'link'       => __('Recipes'),
                'route_name' => 'recipes',
                'group'      => 3,
            ],
            'familytree' => [
                'link'       => __('Family Tree'),
                'route_name' => 'familytree',
                'group'      => 3,
            ],
            'documents' => [
                'link'       => __('Documents'),
                'route_name' => 'documents',
                'group'      => 3,
            ],
        ];

        $communicateOrder = 3;
        $shareOrder       = 4;

        $insertParams = [];

        foreach ($request->sections as $key => $section)
        {
            if (in_array($section, $communicatePlugins))
            {
                $insertParams[] = [
                    'link'       => $communicatePlugins[$section]['link'],
                    'route_name' => $communicatePlugins[$section]['route_name'],
                    'group'      => $communicatePlugins[$section]['group'],
                    'order'      => $communicateOrder,
                ];
                $communicateOrder++;
            }
            else if (in_array($section, $sharePlugins))
            {
                $insertParams[] = [
                    'link'       => $sharePlugins[$section]['link'],
                    'route_name' => $sharePlugins[$section]['route_name'],
                    'group'      => $sharePlugins[$section]['group'],
                    'order'      => $shareOrder,
                ];
                $shareOrder++;
            }
        }

        return redirect()->route('install.admin');
    }

    /**
     * Show the admin form
     *
     * @return Illuminate\View\View
     */
    public function adminCreate()
    {
        $percent = round((3 / $this->totalSteps) * 100);

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

        return view('install.admin', [
            'progress' => $percent,
            'days'     => $days,
            'months'   => $months,
            'years'    => array_reverse($years),
        ]);
    }

    /**
     * Store the admin user in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed'],
            'fname'    => ['required'],
            'bday'     => ['required', 'integer'],
            'bmonth'   => ['required', 'integer'],
            'byear'    => ['required', 'integer'],
        ]);

        // Create the admin user
        $admin = new User;

        $admin->fname     = $request->fname;
        $admin->email     = $request->email;
        $admin->password  = Hash::make($request->password);
        $admin->dob_year  = $request->byear;
        $admin->dob_month = $request->bmonth;
        $admin->dob_day   = $request->bday;

        if ($request->has('lname'))
        {
            $admin->lname = $request->lname;
        }

        $admin->save();

        // Log the admin user in
        Auth::login($admin);

        // Create the user settings for the admin user
        $settings = new UserSetting;

        $settings->user_id = $admin->id;

        $settings->save();

        return redirect()->route('home');
    }
}
