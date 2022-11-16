<?php

use App\Http\Controllers\LegacyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DiscussionController;
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

    Route::get( '/calendar',                               [ CalendarController::class, 'index' ])->name('calendar');
    Route::get( '/calendar/month/{year?}/{month?}/{day?}', [ CalendarController::class, 'index' ])->name('calendar.month');
    Route::get( '/calendar/week/{year?}/{month?}/{day?}',  [ CalendarController::class, 'weekView' ])->name('calendar.week');
    Route::get( '/calendar/day/{year?}/{month?}/{day?}',   [ CalendarController::class, 'dayView' ])->name('calendar.day');

    Route::get( '/members', [ HomeController::class, 'home' ])->name('members');
    Route::get( '/addresses', [ HomeController::class, 'home' ])->name('addresses');

    Route::get( '/discussions',                   [ DiscussionController::class, 'index' ])->name('discussions');
    Route::get( '/discussions/new',               [ DiscussionController::class, 'create' ])->name('discussions.create');
    Route::post('/discussions/new',               [ DiscussionController::class, 'store' ]);
    Route::get( '/discussions/{id}',              [ DiscussionController::class, 'show' ])->name('discussions.show');
    Route::get( '/discussions/{id}/edit',         [ DiscussionController::class, 'edit' ])->name('discussions.edit');
    Route::post('/discussions/{id}/edit',         [ DiscussionController::class, 'update' ]);
    Route::post('/discussions/{id}/delete',       [ DiscussionController::class, 'destroy' ]);
    Route::post('/discussions/{id}/comments/new', [ DiscussionController::class, 'commentsStore' ])->name('discussions.comments.store');

    Route::get( '/photos', [ HomeController::class, 'home' ])->name('photos');
    Route::get( '/videos', [ HomeController::class, 'home' ])->name('videos');
    Route::get( '/contact', [ HomeController::class, 'home' ])->name('contact');
    Route::get( '/help', [ HomeController::class, 'home' ])->name('help');

    Route::get( '/familynews', [ HomeController::class, 'home' ])->name('familynews');
    Route::get( '/prayers', [ HomeController::class, 'home' ])->name('prayers');
    Route::get( '/recipes', [ HomeController::class, 'home' ])->name('recipes');
    Route::get( '/familytree', [ HomeController::class, 'home' ])->name('familytree');
    Route::get( '/documents', [ HomeController::class, 'home' ])->name('documents');

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

