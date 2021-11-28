<?php

use Illuminate\Database\Seeder;
use App\universal;

class devDataSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        print_r("------------------------- Обновление тестовых данных -----\n");
        $load=new universal('./database/seeds/src/test1','_dev_list.csv','U');
        $load->run();
    }
}
