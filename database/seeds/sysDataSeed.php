<?php

use Illuminate\Database\Seeder;
use App\universal;

class sysDataSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        print_r("------------------------- Обновление системных справочников -----\n");
        $load=new universal('./database/seeds/src/sys','_list.csv','U');
        $load->run();
    }
}
