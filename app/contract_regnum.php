<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class contract_regnum extends Model
{
    use DeleteTrait;
    protected $guarded = [];
    protected $fillable = ['orgid', 'categoryid', 'regnum'];

    static public $prefix = 'contract_regnums';

    //static public $sysobjid = 121;

    public static function get_regnum($orgid, $categoryid)
    {
        $result = "";
        try {

            if (isset($orgid) and isset($categoryid)) {
                $itm = contract_regnum::from('contract_regnums as rn')
                    ->where('rn.orgid', $orgid)
                    ->where('rn.categoryid', $categoryid)
                    ->lockForUpdate()
                    ->first();
                //dd($itm, isset($itm));

                if (!isset($itm)) {
                    $itm = contract_regnum::create([
                        'orgid' => $orgid,
                        'categoryid' => $categoryid,
                        'regnum' => 1,
                    ]);
                }
                if (isset($itm)) {

                    $result = contract_category::find($categoryid)->code . $itm->regnum;
                    $itm->regnum++;
                    $itm->save();
                }
                return $result;
            }

        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage());
        }

    }
}
