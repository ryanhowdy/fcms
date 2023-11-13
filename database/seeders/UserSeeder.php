<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Schema::hasTable('fcms_users'))
        {
            // The migration would have created an admin user 1. We need to see if any existing users 
            // already have that id and fix it
            $duplicateId1 = false;

            $fcmsUsers = DB::table('fcms_users')
                ->where('password', '!=', 'NONMEMBER')
                ->get();

            $usersToInsert = [];

            foreach ($fcmsUsers as $fcmsUser)
            {
                if ($fcmsUser->id == 1)
                {
                    $duplicateId1 = true;
                }

                $bday = null;
                if (!empty($fcmsUser->dob_year) && !empty($fcmsUser->dob_month) && !empty($fcmsUser->dob_day))
                {
                    $bday = $fcmsUser->dob_year . '-' . $fcmsUser->dob_month . '-' . $fcmsUser->dob_day;
                }

                $usersToInsert[] = [
                    'id'             => $fcmsUser->id,
                    'access'         => $fcmsUser->access,
                    'email'          => $fcmsUser->email,
                    'password'       => 0,
                    'name'           => $fcmsUser->fname . ' ' . $fcmsUser->lname,
                    'displayname'    => $fcmsUser->username,
                    'birthday'       => $bday,
                    'avatar'         => !empty($fcmsUser->gravatar) ? 'gravatar' : $fcmsUser->avatar,
                    'bio'            => $fcmsUser->bio,
                    'activated'      => $fcmsUser->activated,
                    'login_attempts' => $fcmsUser->login_attempts,
                    'locked'         => $fcmsUser->locked,
                    'created_at'     => $fcmsUser->joindate,
                    'updated_at'     => $fcmsUser->joindate,
                ];
            }

            if ($duplicateId1)
            {
                DB::table('users')->truncate();
            }

            DB::table('users')->insert($usersToInsert);
        }
    }
}
