<?php
namespace App;
use App\Services\TableLoader;

class universal
{
    protected $tableLoad;
    protected $dir4list;
    private $act_name =array("U" => "обновление", "I" => "перезапись");

    public function __construct($dir, $file, $method='U'){
        $this->tableLoad = new TableLoader($dir, $file, $method);
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->tableLoad->load();
    }
}
