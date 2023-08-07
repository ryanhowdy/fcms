<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\AddressBookController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\FamilyTreeController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AdminMemberController;
use App\Http\Controllers\AdminPollController;
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
Route::post('/install/admin',    [ InstallController::class, 'adminStore' ]);

Route::get( '/login',           [ LoginController::class, 'create' ])->name('login');
Route::post('/login',           [ LoginController::class, 'store' ]);
Route::get( '/forgot-password', [ PasswordResetController::class, 'create' ])->name('auth.password.request');
Route::post('/forgot-password', [ PasswordResetController::class, 'store' ])->name('auth.password.email');
Route::get( '/register',        [ RegisterController::class, 'create' ])->name('auth.register');
Route::post('/register',        [ RegisterController::class, 'store' ]);

Route::get( '/calendar/invitation/{id}/code/{code}', [ InvitationController::class, 'show' ])->name('invitations.show');

// Must be authed
Route::middleware(['auth'])->group(function () {
    Route::any( '/home',   [ HomeController::class, 'home' ])->name('home');

    Route::post('/poll/vote', [ HomeController::class, 'vote' ])->name('poll.vote');

    Route::get( '/me/profile',         [ MeController::class, 'profileEdit' ])->name('my.profile');
    Route::post('/me/profile',         [ MeController::class, 'profileUpdate' ]);
    Route::get( '/me/profile/avatar',  [ MeController::class, 'avatarEdit' ])->name('my.avatar');
    Route::post('/me/profile/avatar',  [ MeController::class, 'avatarUpdate' ]);
    Route::get( '/me/profile/address', [ MeController::class, 'addressEdit' ])->name('my.address');
    Route::post('/me/profile/address', [ MeController::class, 'addressUpdate' ]);
    Route::get( '/me/messages',        [ MeController::class, 'messages' ])->name('my.messages');
    Route::get( '/me/notifications',   [ MeController::class, 'notifications' ])->name('my.notifications');
    Route::get( '/me/settings',        [ MeController::class, 'settingsEdit' ])->name('my.settings');
    Route::post('/me/settings',        [ MeController::class, 'settingsUpdate' ]);
    Route::get( '/me/account',         [ MeController::class, 'accountEdit' ])->name('my.account');
    Route::post('/me/account',         [ MeController::class, 'accountUpdate' ]);

    Route::get( '/calendar',                               [ CalendarController::class, 'index' ])->name('calendar');
    Route::get( '/calendar/month/{year?}/{month?}/{day?}', [ CalendarController::class, 'index' ])->name('calendar.month');
    Route::get( '/calendar/week/{year?}/{month?}/{day?}',  [ CalendarController::class, 'weekView' ])->name('calendar.week');
    Route::get( '/calendar/day/{year?}/{month?}/{day?}',   [ CalendarController::class, 'dayView' ])->name('calendar.day');
    Route::get( '/calendar/create',                        [ CalendarController::class, 'create' ])->name('calendar.create');
    Route::get( '/calendar/create/{year}/{month}/{day}',   [ CalendarController::class, 'createDate' ])->name('calendar.createDate');
    Route::post('/calendar/create',                        [ CalendarController::class, 'store' ])->name('calendar.store');
    Route::get( '/calendar/event/{id}',                    [ CalendarController::class, 'show' ])->name('calendar.show');

    Route::get( '/calendar/event/{eid}/invitation/create',    [ InvitationController::class, 'create' ])->name('invitations.create');
    Route::post('/calendar/event/{eid}/invitation/create',    [ InvitationController::class, 'store' ])->name('invitations.store');
    Route::post('/calendar/event/{eid}/invitation/{id}/edit', [ InvitationController::class, 'update' ])->name('invitations.update');

    Route::get( '/members',   [ MemberController::class, 'index' ])->name('members');

    Route::get( '/addressbook',      [ AddressBookController::class, 'index' ])->name('addressbook');
    Route::get( '/addressbook/{id}', [ AddressBookController::class, 'show' ])->name('addressbook.show');

    Route::get( '/discussions',                   [ DiscussionController::class, 'index' ])->name('discussions');
    Route::get( '/discussions/new',               [ DiscussionController::class, 'create' ])->name('discussions.create');
    Route::post('/discussions/new',               [ DiscussionController::class, 'store' ]);
    Route::get( '/discussions/{id}',              [ DiscussionController::class, 'show' ])->name('discussions.show');
    Route::get( '/discussions/{id}/edit',         [ DiscussionController::class, 'edit' ])->name('discussions.edit');
    Route::post('/discussions/{id}/edit',         [ DiscussionController::class, 'update' ]);
    Route::post('/discussions/{id}/delete',       [ DiscussionController::class, 'destroy' ]);
    Route::post('/discussions/{id}/comments/new', [ DiscussionController::class, 'commentsStore' ])->name('discussions.comments.store');

    Route::get( '/photos',              [ PhotoController::class, 'index' ])->name('photos');
    Route::get( '/photos/albums',       [ PhotoController::class, 'albumsIndex' ])->name('photos.albums');
    Route::get( '/photos/people',       [ PhotoController::class, 'usersIndex' ])->name('photos.users');
    Route::get( '/photos/places',       [ PhotoController::class, 'placesIndex' ])->name('photos.places');
    Route::get( '/photos/upload',       [ PhotoController::class, 'create' ])->name('photos.create');
    Route::post('/photos/upload',       [ PhotoController::class, 'store' ]);
    Route::get( '/photos/albums/{id}',  [ PhotoController::class, 'albumsShow' ])->name('photos.albums.show');
    Route::get( '/photos/albums/{aid}/photos/{pid}', [ PhotoController::class, 'photosShow' ])->name('photos.show');

    Route::get( '/videos',        [ VideoController::class, 'index' ])->name('videos');
    Route::get( '/videos/upload', [ VideoController::class, 'create' ])->name('videos.create');
    Route::post('/videos/upload', [ VideoController::class, 'store' ]);
    Route::get( '/videos/{id}',   [ VideoController::class, 'show' ])->name('videos.show');

    Route::get( '/contact', [ HomeController::class, 'contact' ])->name('contact');
    Route::post('/contact', [ HomeController::class, 'contactSend' ]);

    Route::get( '/help', [ HomeController::class, 'home' ])->name('help');

    Route::get( '/familynews',                   [ NewsController::class, 'index' ])->name('familynews');
    Route::get( '/familynews/add',               [ NewsController::class, 'create' ])->name('familynews.create');
    Route::post('/familynews/add',               [ NewsController::class, 'store' ]);
    Route::get( '/familynews/{id}',              [ NewsController::class, 'show' ])->name('familynews.show');
    Route::post('/familynews/{id}/comments/new', [ NewsController::class, 'commentsStore' ])->name('familynews.comments.store');

    Route::get( '/prayers', [ HomeController::class, 'home' ])->name('prayers');

    Route::get( '/recipes',                [ RecipeController::class, 'index' ])->name('recipes');
    Route::get( '/recipes/add',            [ RecipeController::class, 'create' ])->name('recipes.create');
    Route::post('/recipes/add',            [ RecipeController::class, 'store' ]);
    Route::get( '/recipes/categories/add', [ RecipeController::class, 'categoryCreate' ])->name('recipes.categories.create');
    Route::post('/recipes/categories/add', [ RecipeController::class, 'categoryStore' ]);
    Route::get( '/recipes/{id}',           [ RecipeController::class, 'show' ])->name('recipes.show');

    Route::get( '/familytree',                           [ FamilyTreeController::class, 'index' ])->name('familytree');
    Route::get( '/familytree/individual/new',            [ FamilyTreeController::class, 'create' ])->name('familytree.create');
    Route::post('/familytree/individual/new',            [ FamilyTreeController::class, 'store' ]);
    Route::get( '/familytree/individual/{id}',           [ FamilyTreeController::class, 'show' ])->name('familytree.show');
    Route::get( '/familytree/individual/{id}/tree',      [ FamilyTreeController::class, 'showTree' ])->name('familytree.showTree');
    Route::get( '/familytree/individual/{id}/edit',      [ FamilyTreeController::class, 'edit' ])->name('familytree.edit');
    Route::post('/familytree/individual/{id}/edit',      [ FamilyTreeController::class, 'update' ]);
    Route::post('/familytree/individual/{id}/event/new', [ FamilyTreeController::class, 'createEvent' ])->name('familytree.createEvent');

    Route::get( '/documents',                 [ DocumentController::class, 'index' ])->name('documents');
    Route::get( '/documents/upload',          [ DocumentController::class, 'create' ])->name('documents.create');
    Route::post('/documents/upload',          [ DocumentController::class, 'store' ]);
    Route::get( '/documents/{file}/download', [ DocumentController::class, 'download' ])->name('documents.download');

    Route::get( '/uploads/avatars/{file}',                     [ImageController::class, 'showAvatar' ])->name('avatar');
    Route::get( '/uploads/documents/{file}',                   [ImageController::class, 'showAvatar' ])->name('document');
    Route::get( '/uploads/users/{id}/photos/main/{file}',      [ImageController::class, 'showPhoto' ])->name('photo');
    Route::get( '/uploads/users/{id}/photos/thumbnail/{file}', [ImageController::class, 'showPhotoThumbnail' ])->name('photo.thumbnail');
    Route::get( '/uploads/users/{id}/photos/full/{file}',      [ImageController::class, 'showPhotoFull' ])->name('photo.full');
    Route::get( '/uploads/users/{id}/videos/{file}',           [ImageController::class, 'showVideo' ])->name('video');

    // Must have helper privilege or higher
    Route::middleware('can:help')->prefix('admin')->group(function() {
    });

    // Must have adminstrator privilege or higher
    Route::middleware('can:administrate')->prefix('admin')->group(function() {
        Route::get( '/upgrade', [ HomeController::class, 'home' ])->name('admin.upgrade');
        Route::get( '/config', [ HomeController::class, 'home' ])->name('admin.config');

        Route::get( '/members',           [ AdminMemberController::class, 'index' ])->name('admin.members');
        Route::post('/members/{id}/edit', [ AdminMemberController::class, 'update' ])->name('admin.members.update');

        Route::get( '/photos', [ HomeController::class, 'home' ])->name('admin.photos');

        Route::get( '/polls',           [ AdminPollController::class, 'index' ])->name('admin.polls');
        Route::get( '/create',          [ AdminPollController::class, 'create' ])->name('admin.polls.create');
        Route::post('/create',          [ AdminPollController::class, 'store' ]);
        Route::get( '/polls/{id}',      [ AdminPollController::class, 'show' ])->name('admin.polls.show');
        Route::get( '/polls/{id}/edit', [ AdminPollController::class, 'edit' ])->name('admin.polls.edit');
        Route::post('/polls/{id}/edit', [ AdminPollController::class, 'update' ]);

        Route::get( '/facebook', [ HomeController::class, 'home' ])->name('admin.facebook');
        Route::get( '/google', [ HomeController::class, 'home' ])->name('admin.google');
        Route::get( '/instagram', [ HomeController::class, 'home' ])->name('admin.instagram');
    });

    Route::get( '/logout', [ LoginController::class, 'logout' ])->name('auth.logout');
});

