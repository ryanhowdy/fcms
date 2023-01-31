<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Event;
use App\Models\EventCategory;

class Install400 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->boolean('access')->default(false);
            $table->string('email')->unique();
            $table->string('password')->default('0');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->string('maiden', 25)->nullable();
            $table->char('dob_year', 4)->nullable();
            $table->char('dob_month', 2)->nullable();
            $table->char('dob_day', 2)->nullable();
            $table->char('dod_year', 4)->nullable();
            $table->char('dod_month', 2)->nullable();
            $table->char('dod_day', 2)->nullable();
            $table->string('token')->nullable();
            $table->string('avatar', 25)->default('no_avatar.jpg');
            $table->string('gravatar')->nullable();
            $table->string('bio', 200)->nullable();
            $table->char('activate_code', 13)->nullable();
            $table->rememberToken();
            $table->boolean('activated')->default(false);
            $table->boolean('login_attempts')->default(false);
            $table->dateTime('locked')->nullable();
            $table->dateTime('activity')->nullable();
            $table->timestamps();
        });

        $user = new User();

        $user->email = 'noreply@domain.com';
        $user->fname = 'system';
        $user->lname = 'system';
        $user->save();

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->char('country', 2)->nullable();
            $table->string('address', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('zip', 10)->nullable();
            $table->string('home', 20)->nullable();
            $table->string('work', 20)->nullable();
            $table->string('cell', 20)->nullable();
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert', 50);
            $table->foreignId('user_id');
            $table->boolean('hide');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->string('title', 50);
            $table->text('desc')->nullable();
            $table->foreignId('event_category_id');
            $table->string('repeat', 20)->nullable();
            $table->boolean('private')->default(false);
            $table->boolean('invite')->default(false);
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        $data = [
            [
                'date'  => '2007-01-01',
                'title' => __('New Year\'s Day'),
            ],
            [
                'date'  => '2007-02-02',
                'title' => __('Groundhog Day'),
            ],
            [
                'date'  => '2007-02-14',
                'title' => __('Valentine\'s Day'),
            ],
            [
                'date'  => '2007-03-17',
                'title' => __('St. Patrick\'s Day'),
            ],
            [
                'date'  => '2007-04-01',
                'title' => __('April Fools\' Day'),
            ],
            [
                'date'  => '2007-07-04',
                'title' => __('Independence Day'),
            ],
            [
                'date'  => '2007-10-31',
                'title' => __('Halloween'),
            ],
            [
                'date'  => '2007-11-11',
                'title' => __('Veterans Day'),
            ],
            [
                'date'  => '2007-12-25',
                'title' => __('Christmas'),
            ]
        ];
        foreach ($data as $d)
        {
            $event = new Event();
 
            $event->date              = $d['date'];
            $event->title             = $d['title'];
            $event->event_category_id = 4;
            $event->repeat            = 'yearly';
            $event->created_user_id   = 1;
            $event->updated_user_id   = 1;
            $event->save();
        }

        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('color', 20)->nullable();
            $table->string('description')->nullable();
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        // https://dribbble.com/shots/19770670-Dashboard-Calendar
        $data = [
            [
                'name'  => 'default',
                'color' => '#555555',
            ],
            [
                'name'  => __('Anniversary'),
                'color' => '#af85ee',
            ],
            [
                'name'  => __('Birthday'),
                'color' => '#fd764d',
            ],
            [
                'name'  => __('Holiday'),
                'color' => '#8bc48a'
            ]
        ];
        foreach ($data as $d)
        {
            $category = new EventCategory();
 
            $category->name            = $d['name'];
            $category->color           = $d['color'];
            $category->created_user_id = 1;
            $category->updated_user_id = 1;
            $category->save();
        }

        Schema::create('user_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('table', 50);
            $table->string('column', 50);
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('mime')->default('application/download');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 50);
            $table->smallInteger('views')->default(0);
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('discussion_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_id');
            $table->text('comments');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('photo_album_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_album_id');
            $table->text('comments');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('photo_albums', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('description')->nullable();
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('external_photos', function (Blueprint $table) {
            $table->id();
            $table->string('source_id');
            $table->string('thumbnail');
            $table->string('medium');
            $table->string('full');
        });

        Schema::create('photo_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_id');
            $table->text('comments');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 25)->default('noimage.gif');
            $table->integer('external_photo_id')->nullable();
            $table->text('caption')->nullable();
            $table->foreignId('photo_album_id');
            $table->smallInteger('views')->default(0);
            $table->smallInteger('votes')->default(0);
            $table->float('rating', 10, 0)->default(0);
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('photo_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('photo_id');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id');
            $table->foreignId('user_id');
            $table->string('email', 50)->nullable();
            $table->boolean('attending')->nullable();
            $table->char('code', 13)->nullable();
            $table->text('response')->nullable();
            $table->timestamps();
        });

        Schema::create('navigation_links', function (Blueprint $table) {
            $table->id();
            $table->string('link');
            $table->string('route_name')->nullable();
            $table->tinyInteger('group');
            $table->tinyInteger('order');
        });

        Schema::create('news_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id');
            $table->text('comments');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title', 50);
            $table->text('news');
            $table->string('external_type', 20)->nullable();
            $table->string('external_id')->nullable();
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('notification', 50)->nullable();
            $table->string('data', 50);
            $table->boolean('read')->default(false);
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('poll_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id');
            $table->text('comments');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id');
            $table->text('option');
            $table->integer('votes')->default(0);
        });

        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option');
            $table->foreignId('poll_id');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('prayers', function (Blueprint $table) {
            $table->id();
            $table->string('for', 50);
            $table->text('desc');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('private_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('title', 50);
            $table->text('msg');
            $table->boolean('read')->default(false);
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('recipe_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id');
            $table->text('comments');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('recipe_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('description')->nullable();
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('thumbnail')->default('no_recipe.jpg');
            $table->foreignId('recipe_category_id');
            $table->text('ingredients');
            $table->text('directions');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('relationship', 4);
            $table->foreignId('rel_user_id');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->text('status');
            $table->foreignId('parent_id')->default(0);
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('user_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('award', 100);
            $table->integer('month');
            $table->integer('item_id')->nullable();
            $table->smallInteger('count')->default(0);
            $table->timestamps();
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('theme', 25)->default('default');
            $table->set('boardsort', ['ASC', 'DESC'])->default('ASC');
            $table->tinyInteger('displayname')->default('1');
            $table->set('frontpage', ['1', '2'])->default('1');
            $table->string('timezone')->default('-5 hours');
            $table->boolean('dst')->default(false);
            $table->boolean('email_updates')->default(false);
            $table->set('uploader', ['plupload', 'java', 'basic'])->default('plupload');
            $table->boolean('advanced_tagging')->default(true);
            $table->string('language', 6)->default('en_US');
            $table->integer('fs_user_id')->nullable();
            $table->char('fs_access_token', 50)->nullable();
            $table->string('blogger')->nullable();
            $table->string('tumblr')->nullable();
            $table->string('wordpress')->nullable();
            $table->string('posterous')->nullable();
            $table->string('fb_user_id')->nullable();
            $table->string('fb_access_token')->nullable();
            $table->string('google_session_token')->nullable();
            $table->string('instagram_access_token')->nullable();
            $table->boolean('instagram_auto_upload')->nullable()->default(false);
            $table->string('picasa_session_token')->nullable();
        });

        Schema::create('video_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id');
            $table->text('comments');
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('external_videos', function (Blueprint $table) {
            $table->id();
            $table->string('source_id');
            $table->integer('duration')->nullable();
            $table->string('source', 50)->nullable();
            $table->integer('height')->default(420);
            $table->integer('width')->default(780);
            $table->boolean('active')->default(true);
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('external_video_id')->nullable();
            $table->foreignId('created_user_id');
            $table->foreignId('updated_user_id');
            $table->timestamps();
        });

        \DB::statement("
            CREATE OR REPLACE VIEW view_whats_new_updates
            AS
            (SELECT
                'DISCUSSION' AS type,   -- object type
                dc.id,                  -- object id
                dc.created_at,
                dc.updated_at,
                dc.updated_user_id,
                u.fname,
                u.mname,
                u.lname,
                u.avatar,
                u.gravatar,
                us.displayname,
                d.title,                -- object title
                dc.comments             -- object comments
            FROM
                discussion_comments AS dc
                LEFT JOIN discussions AS d ON dc.discussion_id = dc.id
                LEFT JOIN users AS u ON dc.updated_user_id = u.id
                LEFT JOIN user_settings as us ON dc.updated_user_id = us.user_id)
            UNION
            (SELECT
                'ADDRESS_ADD' AS type, a.id, a.created_at, a.updated_at, a.updated_user_id, u.fname, u.mname, u.lname, u.avatar, u.gravatar, us.displayname, 'n/a' AS title, 'n/a' as comments
            FROM
                addresses AS a, users AS u, user_settings AS us
            WHERE
                a.updated_user_id = u.id AND a.updated_user_id = us.user_id)
            UNION
            (SELECT
                'NEW_USER' AS type, u.id, u.created_at, u.updated_at, u.id, u.fname, u.mname, u.lname, u.avatar, u.gravatar, us.displayname, 'n/a', 'n/a'
            FROM
                users AS u, user_settings AS us
            WHERE
                activated > 0)
            UNION
            (SELECT
                'PHOTOS' AS type, p.filename, p.created_at, p.updated_at, p.updated_user_id, u.fname, u.mname, u.lname, u.avatar, u.gravatar, us.displayname, a.name, a.description
            FROM
                photos AS p
                LEFT JOIN photo_albums AS a ON p.photo_album_id = a.id
                LEFT JOIN users AS u ON p.updated_user_id = u.id
                LEFT JOIN user_settings as us ON p.updated_user_id = us.user_id)
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('user_changes');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('discussions');
        Schema::dropIfExists('discussion_comments');
        Schema::dropIfExists('events');
        Schema::dropIfExists('gallery_category_comments');
        Schema::dropIfExists('gallery_categories');
        Schema::dropIfExists('gallery_external_photos');
        Schema::dropIfExists('gallery_photo_comments');
        Schema::dropIfExists('gallery_photos');
        Schema::dropIfExists('gallery_photo_tags');
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('navigation_links');
        Schema::dropIfExists('news_comments');
        Schema::dropIfExists('news');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('poll_comments');
        Schema::dropIfExists('poll_options');
        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('polls');
        Schema::dropIfExists('prayers');
        Schema::dropIfExists('private_messages');
        Schema::dropIfExists('recipe_comments');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('relationships');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('user_awards');
        Schema::dropIfExists('users');
        Schema::dropIfExists('video_comments');
        Schema::dropIfExists('videos');
    }
}
