<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Upgrade400 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade:400';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will upgrade any 3.8.0 database to 4.0.0 database';

    private $skippedTables = [];
    private $errors        = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->confirm('Are you sure you want to upgrade to 4.0.0 (this will permanently alter tables)?'))
        {
            DB::beginTransaction();

            $this->fixAddressTable();

            $this->fixDiscussionTables();

            DB::commit();
// [x] addresses
// [_] alerts
// [_] discussion_comments
// [_] discussions
// [_] documents
// [_] event_categories
// [_] events
// [_] external_photos
// [_] external_videos
// [_] invitations
// [_] migrations
// [_] navigation_links
// [_] news
// [_] news_comments
// [_] notifications
// [_] photo_album_comments
// [_] photo_albums
// [_] photo_comments
// [_] photo_tags
// [_] photos
// [_] poll_comments
// [_] poll_options
// [_] poll_votes
// [_] polls
// [_] prayers
// [_] private_messages
// [_] recipe_categories
// [_] recipe_comments
// [_] recipes
// [_] relationships
// [_] statuses
// [_] user_awards
// [_] user_changes
// [_] user_settings
// [_] users
// [_] video_comments
// [_] videos
// [_] view_whats_new_updates
        }

        if (count($this->errors))
        {
            $this->error('The following Errors have occured:');
            foreach ($this->errors as $e)
            {
                $this->info($e);
            }
        }

        return Command::SUCCESS;
    }

    private function fixAddressTable()
    {
        try
        {
            // remove indexes
            DB::statement('alter table fcms_address drop index user_ind');
            DB::statement('alter table fcms_address drop index create_ind');
            DB::statement('alter table fcms_address drop index update_ind');

            // remove fk constraint
            DB::statement('alter table fcms_address drop foreign key fcms_address_ibfk_1');

            // fix columns
            DB::statement('alter table fcms_address modify column id bigint unsigned not null auto_increment');
            DB::statement('alter table fcms_address modify column user bigint unsigned not null');
            DB::statement('alter table fcms_address modify column country char(2) collate utf8mb4_unicode_ci default null');
            DB::statement('alter table fcms_address modify column address varchar(50) collate utf8mb4_unicode_ci default null');
            DB::statement('alter table fcms_address modify column city varchar(50) collate utf8mb4_unicode_ci default null');
            DB::statement('alter table fcms_address modify column state varchar(50) collate utf8mb4_unicode_ci default null');
            DB::statement('alter table fcms_address modify column zip varchar(10) collate utf8mb4_unicode_ci default null');
            DB::statement('alter table fcms_address modify column home varchar(20) collate utf8mb4_unicode_ci default null');
            DB::statement('alter table fcms_address modify column work varchar(20) collate utf8mb4_unicode_ci default null');
            DB::statement('alter table fcms_address modify column cell varchar(20) collate utf8mb4_unicode_ci default null');
            DB::statement('alter table fcms_address modify column created_id bigint unsigned not null');
            DB::statement('alter table fcms_address modify column updated_id bigint unsigned not null');
            DB::statement('alter table fcms_address modify column created timestamp null default null');
            DB::statement('alter table fcms_address modify column updated timestamp null default null');

            // rename columns
            DB::statement('alter table fcms_address rename column user to user_id');
            DB::statement('alter table fcms_address rename column created_id to created_user_id');
            DB::statement('alter table fcms_address rename column updated_id to updated_user_id');
            DB::statement('alter table fcms_address rename column created to created_at');
            DB::statement('alter table fcms_address rename column updated to updated_at');
        }
        catch(\Illuminate\Database\QueryException $e)
        {
            $this->skippedTables[] = 'fcms_address';

            $this->errors[] = $e->errorInfo[2];
        }
    }

    private function fixDiscussionTables()
    {
        try
        {
            // remove indexes
            DB::statement('alter table fcms_board_threads drop index start_ind');
            DB::statement('alter table fcms_board_threads drop index up_ind');

            // remove fk constraint
            DB::statement('alter table fcms_board_threads drop foreign key fcms_threads_ibfk_1');
            DB::statement('alter table fcms_board_threads drop foreign key fcms_threads_ibfk_2');

            // fix columns
            DB::statement('alter table fcms_board_threads modify column id bigint unsigned not null auto_increment');
            DB::statement('alter table fcms_board_threads modify column subject varchar(50) collate utf8mb4_unicode_ci default null');
            DB::statement('alter table fcms_board_threads modify column started_by bigint unsigned not null');
            DB::statement('alter table fcms_board_threads modify column updated timestamp null default null');
            DB::statement('alter table fcms_board_threads modify column updated_by bigint unsigned not null');
            DB::statement('alter table fcms_board_threads modify column views smallint not null default 0');

            // rename columns
            DB::statement('alter table fcms_board_threads rename column subject to title');
            DB::statement('alter table fcms_board_threads rename column started_by to created_user_id');
            DB::statement('alter table fcms_board_threads rename column updated to updated_at');
            DB::statement('alter table fcms_board_threads rename column updated_by to updated_user_id');

            // add missing columns
            DB::statement('alter table fcms_board_threads add column created_at timestamp null default null');
            DB::statement('update fcms_board_threads set created = updated');
        }
        catch(\Illuminate\Database\QueryException $e)
        {
            $this->skippedTables[] = 'fcms_board_threads';

            $this->errors[] = $e->errorInfo[2];
        }

        try
        {
            // remove indexes
            DB::statement('alter table fcms_board_posts drop index thread_ind');
            DB::statement('alter table fcms_board_posts drop index user_ind');

            // remove fk constraint
            DB::statement('alter table fcms_board_posts drop foreign key fcms_posts_ibfk_1');
            DB::statement('alter table fcms_board_posts drop foreign key fcms_posts_ibfk_2');

            // fix columns
            DB::statement('alter table fcms_board_posts modify column id bigint unsigned not null auto_increment');
            DB::statement('alter table fcms_board_posts modify column date timestamp null default null');
            DB::statement('alter table fcms_board_posts modify column thread bigint unsigned not null');
            DB::statement('alter table fcms_board_posts modify column user bigint unsigned not null');
            DB::statement('alter table fcms_board_posts modify column post text collate utf8mb4_unicode_ci not null');

            // rename columns
            DB::statement('alter table fcms_board_posts rename column date to created_at');
            DB::statement('alter table fcms_board_posts rename column thread to discussion_id');
            DB::statement('alter table fcms_board_posts rename column user to created_user_id');
            DB::statement('alter table fcms_board_posts rename column post to comments');

            // add missing columns
            DB::statement('alter table fcms_board_posts add column updated_at timestamp null default null');
            DB::statement('alter table fcms_board_posts add column updated_user_id timestamp null default null');
            DB::statement('update fcms_board_posts set created_at = updated_at');
            DB::statement('update fcms_board_posts set created_user_id = updated_user_id');
        }
        catch(\Illuminate\Database\QueryException $e)
        {
            $this->skippedTables[] = 'fcms_board_posts';

            $this->errors[] = $e->errorInfo[2];
        }
    }
}
