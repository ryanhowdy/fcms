<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LegacyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Capture the echo output from our legacy code.
     */
    public function __invoke()
    {
        ob_start();
        require app_path('Http') . '/legacy.php';
        $output = ob_get_clean();

        // be sure to import Illuminate\Http\Response
        return new Response($output);
    }
}
