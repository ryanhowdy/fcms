<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DiscussionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Schema::hasTable('fcms_board_threads') && Schema::hasTable('fcms_board_posts'))
        {
            $fcmsThreads = DB::table('fcms_board_threads as t')
                ->select('t.*', DB::raw('(select date from fcms_board_posts as p where p.thread = t.id order by id asc limit 1) as date'))
                ->get();

            $fcmsPosts = DB::table('fcms_board_posts')
                ->get();

            $discussions = [];
            $comments    = [];

            foreach ($fcmsThreads as $fcmsThread)
            {
                $discussions[] = [
                    'id'              => $fcmsThread->id,
                    'title'           => $fcmsThread->subject,
                    'views'           => $fcmsThread->views,
                    'created_user_id' => $fcmsThread->started_by,
                    'updated_user_id' => $fcmsThread->updated_by,
                    'created_at'      => $fcmsThread->date,
                    'updated_at'      => $fcmsThread->updated,
                ];
            }

            foreach ($fcmsPosts as $fcmsPost)
            {
                $comments[] = [
                    'id'              => $fcmsPost->id,
                    'discussion_id'   => $fcmsPost->thread,
                    'comments'        => $fcmsPost->post,
                    'created_user_id' => $fcmsPost->user,
                    'updated_user_id' => $fcmsPost->user,
                    'created_at'      => $fcmsPost->date,
                    'updated_at'      => $fcmsPost->date,
                ];
            }

            foreach (array_chunk($discussions, 500) as $d)
            {
                DB::table('discussions')->insert($d);
            }

            foreach (array_chunk($comments, 500) as $c)
            {
                DB::table('discussion_comments')->insert($c);
            }
        }
    }
}
