<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Schema::hasTable('fcms_address'))
        {
            $fcmsAddresses = DB::table('fcms_address')
                ->get();

            $inserts = [];

            foreach ($fcmsAddresses as $fcmsAddress)
            {
                $inserts[] = [
                    'id'              => $fcmsAddress->id,
                    'user_id'         => $fcmsAddress->user,
                    'country'         => $fcmsAddress->country,
                    'address'         => $fcmsAddress->address,
                    'city'            => $fcmsAddress->city,
                    'state'           => $fcmsAddress->state,
                    'zip'             => $fcmsAddress->zip,
                    'home'            => $fcmsAddress->home,
                    'work'            => $fcmsAddress->work,
                    'cell'            => $fcmsAddress->cell,
                    'created_user_id' => $fcmsAddress->created_id,
                    'updated_user_id' => $fcmsAddress->updated_id,
                    'created_at'      => $fcmsAddress->created,
                    'updated_at'      => $fcmsAddress->updated,
                ];
            }

            DB::table('addresses')->insert($inserts);
        }
    }
}
