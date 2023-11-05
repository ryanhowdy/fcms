<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class NewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Schema::hasTable('fcms_news') && Schema::hasTable('fcms_news_comments'))
        {
            $fcmsNews = DB::table('fcms_news')
                ->get();

            $fcmsNewsComments = DB::table('fcms_news_comments')
                ->get();

            $news     = [];
            $comments = [];

            foreach ($fcmsNews as $n)
            {
                $created = $n->created;
                $updated = $n->updated;

                if ($created == '0000-00-00 00:00:00')
                {
                    $created = null;
                }
                if ($updated == '0000-00-00 00:00:00')
                {
                    $updated = null;
                }

                $news[] = [
                    'id'              => $n->id,
                    'title'           => $n->title,
                    'news'            => $n->news,
                    'created_user_id' => $n->user,
                    'updated_user_id' => $n->user,
                    'created_at'      => $created ? $created : $updated,
                    'updated_at'      => $updated ? $updated : $created,
                ];
            }

            foreach ($fcmsNewsComments as $c)
            {
                $date = $c->date;
                if ($date == '0000-00-00 00:00:00')
                {
                    $date = null;
                }

                $comments[] = [
                    'id'              => $c->id,
                    'news_id'         => $c->news,
                    'comments'        => $c->comment,
                    'created_user_id' => $c->user,
                    'updated_user_id' => $c->user,
                    'created_at'      => $date,
                    'updated_at'      => $date,
                ];
            }

            DB::table('news')->insert($news);
            DB::table('news_comments')->insert($comments);
        }
    }
}
