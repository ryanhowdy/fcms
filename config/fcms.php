<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Version
    |--------------------------------------------------------------------------
    |
    | This value is the version of Family Connections the code is using
    |
    */

    'version' => env('FCMS_VERSION', '4.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    |
    | The email address for the webmaster of the site.
    |
    */

    'contact' => env('FCMS_CONTACT', 'noreply@mail.com'),

    /*
    |--------------------------------------------------------------------------
    | Auto Activation
    |--------------------------------------------------------------------------
    |
    | If set to true, new users can join and use the site without any 
    | administrator approval.
    |
    */

    'auto_activate' => env('FCMS_AUTO_ACTIVATE', 'false'),

    /*
    |--------------------------------------------------------------------------
    | Allow Registration
    |--------------------------------------------------------------------------
    |
    | Do you want to allow new users to register for the site.
    |
    */

    'allow_registration' => env('FCMS_ALLOW_REGISTRATION', 'true'),

    /*
    |--------------------------------------------------------------------------
    | Week Start/End
    |--------------------------------------------------------------------------
    |
    | What day is the start/end of the week. 0-6, Sunday = 0, Monday = 1, etc.
    |
    | If you would like a week to be Sunday through Saturday:
    | week_start = 0
    | week_end = 6
    |
    | If you would like a week to be Monday through Sunday:
    | week_start = 1
    | week_end = 0
    |
    */

    'week' => [
        'start' => env('FCMS_WEEK_START', 0),
        'end'   => env('FCMS_WEEK_END', 6), 
    ],

    /*
    |--------------------------------------------------------------------------
    | Full Size Photos
    |--------------------------------------------------------------------------
    |
    | Allow full sized photos to be stored in the photo gallery.
    |
    */

    'full_size_photos' => env('FCMS_FULL_SIZE_PHOTOS', false),

    /*
    |--------------------------------------------------------------------------
    | Legacy Data
    |--------------------------------------------------------------------------
    |
    | Only for sites with Legacy (<= 3.8.0) data.
    |
    | Setting to true will fix some data issues when upgrading from any version
    | prior to 4.0.0.
    |
    */

    'legacy' => env('FCMS_LEGACY', false),
];
