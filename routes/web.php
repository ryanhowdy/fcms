<?php

use App\Http\Controllers\LegacyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get( '/', [ HomeController::class, 'index' ])->name('index');

Route::get( '/install',          [ InstallController::class, 'index' ])->name('install');
Route::get( '/install/database', [ InstallController::class, 'database' ])->name('install.database');
Route::get( '/install/config',   [ InstallController::class, 'configurationCreate' ])->name('install.config');
Route::post('/install/config',   [ InstallController::class, 'configurationStore' ]);
Route::get( '/install/admin',    [ InstallController::class, 'adminCreate' ])->name('install.admin');
Route::post('/install/admin',    [ InstallController::class, 'adminStore' ])->name('install.admin');

Route::get( '/login',           [ LoginController::class, 'create' ])->name('login');
Route::post('/login',           [ LoginController::class, 'store' ]);
Route::get( '/forgot-password', [ PasswordResetController::class, 'create' ])->name('auth.password.request');
Route::post('/forgot-password', [ PasswordResetController::class, 'store' ])->name('auth.password.email');
Route::get( '/register',        [ RegisterController::class, 'create' ])->name('auth.register');
Route::post('/register',        [ RegisterController::class, 'store' ]);

// Must be authed
Route::middleware(['auth'])->group(function () {
    Route::any( '/home',   [ HomeController::class, 'home' ])->name('home');

    Route::any( '/me/profile',       [ HomeController::class, 'home' ])->name('my.profile');
    Route::any( '/me/messages',      [ HomeController::class, 'home' ])->name('my.messages');
    Route::any( '/me/notifications', [ HomeController::class, 'home' ])->name('my.notifications');
    Route::any( '/me/settings',      [ HomeController::class, 'home' ])->name('my.settings');

    Route::get( '/calendar', [ HomeController::class, 'home' ])->name('calendar');
    Route::get( '/members', [ HomeController::class, 'home' ])->name('members');
    Route::get( '/addresses', [ HomeController::class, 'home' ])->name('addresses');
    Route::get( '/discussions', [ HomeController::class, 'home' ])->name('discussions');
    Route::get( '/photos', [ HomeController::class, 'home' ])->name('photos');
    Route::get( '/videos', [ HomeController::class, 'home' ])->name('videos');
    Route::get( '/contact', [ HomeController::class, 'home' ])->name('contact');
    Route::get( '/help', [ HomeController::class, 'home' ])->name('help');

    Route::get( '/admin/upgrade', [ HomeController::class, 'home' ])->name('admin.upgrade');
    Route::get( '/admin/config', [ HomeController::class, 'home' ])->name('admin.config');
    Route::get( '/admin/members', [ HomeController::class, 'home' ])->name('admin.members');
    Route::get( '/admin/photos', [ HomeController::class, 'home' ])->name('admin.photos');
    Route::get( '/admin/polls', [ HomeController::class, 'home' ])->name('admin.polls');
    Route::get( '/admin/facebook', [ HomeController::class, 'home' ])->name('admin.facebook');
    Route::get( '/admin/google', [ HomeController::class, 'home' ])->name('admin.google');
    Route::get( '/admin/instagram', [ HomeController::class, 'home' ])->name('admin.instagram');

    Route::get( '/logout', [ LoginController::class, 'logout' ])->name('auth.logout');
});

