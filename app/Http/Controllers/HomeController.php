<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Configuration;
use App\Models\NavigationLink;

class HomeController extends Controller
{
    /**
     * Redirects to install, login or home page
     *
     * @return Illuminate\Support\Facades\View
     */
    public function index()
    {
        if (!$this->isSiteInstalled())
        {
            return redirect()->to('/install');
        }

        if (!Auth()->user()) {
            return redirect()->to('/login');
        }

        return redirect()->to('/home');
    }

    /**
     * Show the home page
     *
     * @return Illuminate\View\View
     */
    public function home()
    {
        $navigationLinks = NavigationLink::get();

        $links = [];

        foreach ($navigationLinks as $k => $link)
        {
            $links[$link->group][$link->order] = $link->toArray();

            $links[$link->group][$link->order]['icon'] = $link->icon;
        }

        return view('home', [ 'links' => $links ]);
    }

    /**
     * Check if the site has been installed yet.
     *
     * @return boolean
     */
    private function isSiteInstalled()
    {
        try
        {
            $config = Configuration::where('name', 'registration')
                ->select('value as registration')
                ->first();

            if (empty($config))
            {
                return false;
            }
        }
        catch (\Exception $e)
        {
            return false;
        }

        return true;
    }
}
