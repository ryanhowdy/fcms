<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PhotoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedPhotos();

        $this->seedComments();

        $this->seedPhotoUsers();
    }

    /**
     * seedPhotos 
     * 
     * @return null
     */
    private function seedPhotos()
    {
        if (Schema::hasTable('fcms_category') && Schema::hasTable('fcms_gallery_photos'))
        {
            $fcmsPhotos = DB::table('fcms_gallery_photos as p')
                ->select(
                    'p.id', 'filename', 'caption', 'views', 'votes', 'rating', 'p.user', 'p.date', 
                    'c.id as category_id', 'c.name as category', 'c.description', 'c.user as category_user', 'c.date as category_date'
                )
                ->leftJoin('fcms_category as c', 'c.id', '=', 'p.category')
                ->get();

            $categories = [];
            $photos     = [];

            foreach ($fcmsPhotos as $fcmsPhoto)
            {
                if (is_null($fcmsPhoto->category_id))
                {
                    continue;
                }

                if (!isset($categories[$fcmsPhoto->category_id]))
                {
                    $categories[$fcmsPhoto->category_id] = [
                        'id'              => $fcmsPhoto->category_id,
                        'name'            => $fcmsPhoto->category,
                        'description'     => $fcmsPhoto->description,
                        'created_user_id' => $fcmsPhoto->category_user,
                        'updated_user_id' => $fcmsPhoto->category_user,
                        'created_at'      => $fcmsPhoto->date,
                        'updated_at'      => $fcmsPhoto->date,
                    ];
                }

                $photos[] = [
                    'id'              => $fcmsPhoto->id,
                    'filename'        => $fcmsPhoto->filename,
                    'caption'         => $fcmsPhoto->caption,
                    'photo_album_id'  => $fcmsPhoto->category_id,
                    'views'           => $fcmsPhoto->views,
                    'votes'           => $fcmsPhoto->votes,
                    'rating'          => $fcmsPhoto->rating,
                    'created_user_id' => $fcmsPhoto->user,
                    'updated_user_id' => $fcmsPhoto->user,
                    'created_at'      => $fcmsPhoto->date,
                    'updated_at'      => $fcmsPhoto->date,
                ];
            }

            foreach (array_chunk($categories, 50) as $c)
            {
                try
                {
                    DB::table('photo_albums')->insert($c);
                }
                catch (\Illuminate\Database\QueryException $e)
                {
                    print_r($c);
                }
            }
            foreach (array_chunk($photos, 50) as $p)
            {
                DB::table('photos')->insert($p);
            }
        }
    }

    /**
     * seedComments 
     * 
     * @return null
     */
    private function seedComments()
    {
        if (Schema::hasTable('fcms_gallery_category_comment') && Schema::hasTable('fcms_gallery_photo_comment'))
        {
            $categoryComments = [];
            $photoComments    = [];

            $fcmsCategoryComments = DB::table('fcms_gallery_category_comment')
                ->get();

            $fcmsPhotoComments = DB::table('fcms_gallery_photo_comment')
                ->get();

            foreach ($fcmsCategoryComments as $fcmsComment)
            {
                $categoryComments[] = [
                    'photo_album_id'  => $fcmsComment->category_id,
                    'comments'        => $fcmsComment->comment,
                    'created_user_id' => $fcmsComment->created_id,
                    'updated_user_id' => $fcmsComment->created_id,
                    'created_at'      => $fcmsComment->created,
                    'updated_at'      => $fcmsComment->created,
                ];
            }

            foreach ($fcmsPhotoComments as $fcmsComment)
            {
                $photoComments[] = [
                    'photo_id'        => $fcmsComment->photo,
                    'comments'        => $fcmsComment->comment,
                    'created_user_id' => $fcmsComment->user,
                    'updated_user_id' => $fcmsComment->user,
                    'created_at'      => $fcmsComment->date,
                    'updated_at'      => $fcmsComment->date,
                ];
            }

            foreach (array_chunk($categoryComments, 500) as $c)
            {
                DB::table('photo_album_comments')->insert($c);
            }
            foreach (array_chunk($photoComments, 500) as $c)
            {
                DB::table('photo_comments')->insert($c);
            }
        }
    }

    /**
     * seedPhotoUsers 
     * 
     * @return null
     */
    private function seedPhotoUsers()
    {
        if (Schema::hasTable('fcms_gallery_photos_tags'))
        {
            $photoUsers = [];

            $fcmsPhotoUsers = DB::table('fcms_gallery_photos_tags')
                ->get();

            foreach ($fcmsPhotoUsers as $fcmsPhotoUser)
            {
                $photoUsers[] = [
                    'photo_id'        => $fcmsPhotoUser->photo,
                    'user_id'         => $fcmsPhotoUser->user,
                    'created_user_id' => $fcmsPhotoUser->user,
                    'updated_user_id' => $fcmsPhotoUser->user,
                    'created_at'      => Carbon::now(),
                    'updated_at'      => Carbon::now(),
                ];
            }

            foreach (array_chunk($photoUsers, 500) as $u)
            {
                DB::table('photo_users')->insert($u);
            }
        }
    }
}
