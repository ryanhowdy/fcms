<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PhotoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Schema::hasTable('fcms_category') && Schema::hasTable('fcms_gallery_photos'))
        {
            $fcmsCategories = DB::table('fcms_category as c')
                ->where('type', 'gallery')
                ->get();

            $fcmsPhotos = DB::table('fcms_gallery_photos')
                ->get();

            $categories = [];
            $photos     = [];

            foreach ($fcmsCategories as $fcmsCategory)
            {
                $categories[] = [
                    'id'              => $fcmsCategory->id,
                    'name'            => $fcmsCategory->name,
                    'description'     => $fcmsCategory->description,
                    'created_user_id' => $fcmsCategory->user,
                    'updated_user_id' => $fcmsCategory->user,
                    'created_at'      => $fcmsCategory->date,
                    'updated_at'      => $fcmsCategory->date,
                ];
            }

            foreach ($fcmsPhotos as $fcmsPhoto)
            {
                $photos[] = [
                    'id'              => $fcmsPhoto->id,
                    'filename'        => $fcmsPhoto->filename,
                    'caption'         => $fcmsPhoto->caption,
                    'photo_album_id'  => $fcmsPhoto->category,
                    'views'           => $fcmsPhoto->views,
                    'votes'           => $fcmsPhoto->votes,
                    'rating'          => $fcmsPhoto->rating,
                    'created_user_id' => $fcmsPhoto->user,
                    'updated_user_id' => $fcmsPhoto->user,
                    'created_at'      => $fcmsPhoto->date,
                    'updated_at'      => $fcmsPhoto->date,
                ];
            }

            foreach (array_chunk($categories, 500) as $c)
            {
                DB::table('photo_albums')->insert($c);
            }
            foreach (array_chunk($photos, 500) as $p)
            {
                DB::table('photos')->insert($p);
            }
        }
    }
}
