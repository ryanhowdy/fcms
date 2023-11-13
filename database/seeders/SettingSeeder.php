<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Schema::hasTable('fcms_user_settings'))
        {
            $fcmsSettings = DB::table('fcms_user_settings as s')
                ->join('fcms_users as u', 'u.id', '=', 's.user')
                ->where('u.password', '!=', 'NONMEMBER')
                ->get();

            $tzLkup = [
                '-12 hours'            => 'America/New_York',
				'-11 hours'            => 'Pacific/Midway',
				'-10 hours'            => 'Pacific/Honolulu',
				'-9 hours'             => 'America/Adak',
				'-8 hours'             => 'America/Anchorage',
				'-7 hours'             => 'America/Los_Angeeles',
				'-6 hours'             => 'America/Denver',
				'-5 hours'             => 'America/Chicago',
				'-4 hours'             => 'America/New_York',
				'-3 hours -30 minutes' => 'America/St_Johns',
				'-3 hours'             => 'America/Halifax',
				'-2 hours'             => 'America/Sao_Paulo',
				'-1 hours'             => 'Atlantic/Cape_Verde',
				'-0 hours'             => 'Atlantic/Azores',
				'+1 hours'             => 'Europe/London',
				'+2 hours'             => 'Europe/Paris',
				'+3 hours'             => 'Europe/Helsinki',
				'+3 hours +30 minutes' => 'Asia/Tehran',
				'+4 hours'             => 'Europe/Moscow',
				'+4 hours +30 minutes' => 'Asia/Kabul',
				'+5 hours'             => 'Antarctica/Mawson',
                '+5 hours +30 minutes' => 'Asia/Calcutta',
				'+6 hours'             => 'Asia/Yekaterinburg',
				'+7 hours'             => 'Asia/Novosibirsk',
				'+8 hours'             => 'Asia/Krasnoyarsk',
				'+9 hours'             => 'Asia/Chita',
				'+9 hours +30 minutes' => 'Australia/Darwin',
				'+10 hours'            => 'Antarctica/DumontDUrville',
				'+11 hours'            => 'Australia/Melbourne',
				'+12 hours'            => 'Antarctica/McMurdo',
            ];

            $settings = [];

            foreach ($fcmsSettings as $fcmsSetting)
            {
                $settings[] = [
                    'user_id'   => $fcmsSetting->user,
                    'language'  => $fcmsSetting->language,
                    'timezone'  => $tzLkup[$fcmsSetting->timezone],
                ];
            }

            DB::table('user_settings')->insert($settings);
        }
    }
}
