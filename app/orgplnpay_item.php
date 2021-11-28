<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class orgplnpay_item extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'orgplnpay_items';
    static public $sysobjid = 902;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function doc()
    {//
        return $this->hasOne(orgplnpay::class, 'id', 'docid');
    }

    public function inituser()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function org()
    {//
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

    public function agr1_user()
    {//
        return $this->hasOne(User::class, 'id', 'agr1_by')->withDefault();
    }

    public function agr2_user()
    {//
        return $this->hasOne(User::class, 'id', 'agr2_by')->withDefault();
    }

    public function initpay_user()
    {//
        return $this->hasOne(User::class, 'id', 'initpay_by')->withDefault();
    }

    public function payreg_user()
    {//
        return $this->hasOne(User::class, 'id', 'fctpay_by')->withDefault();
    }

    public function category()
    {//
        return $this->hasOne(pay_category::class, 'id', 'categoryid')
            ->withDefault();
    }

    public function equiprqst()
    {//связь с заявкой на материалы
        return $this->hasOne(equiprqst::class, 'id', 'equiprqst_id')
            ->withDefault();
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')->where('sysobjid', self::$sysobjid);
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->leftJoin('roletypes as rt', 'rt.id', 'obj_readers.roletypeid')
            ->where('sysobjid', self::$sysobjid)
            ->select('obj_readers.*', 'rt.name as roletype_name')
            ->orderby('mustread', 'desc')
            ->orderby('firstread_at');
    }

    public function real_readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->where('read_cnt', '>', 0)
            ->orderby('firstread_at');
    }

    static public function split_rest($rec)
    {
        //разделяет запись с неполной оплатой на две части - одну обжимает до суммы факт. оплаты. Вторую - на сумму остатка
        if (isset($rec) and $rec->fctpaysum < $rec->plnpaysum) {

            //$delta = $rec->plnpaysum - $rec->agr1_sum;
            $delta = $rec->plnpaysum - $rec->fctpaysum;

            $new_rec = $rec->replicate();

            //обожмем плановую сумму до суммы факт. оплаты:
            //$rec->plnpaysum = $rec->agr1_sum;
            $rec->plnpaysum = $rec->fctpaysum;


            //запись остатка подчиним текущему дню
            $doc = orgplnpay::updDateRestSum($rec->doc->ownorgid, now());
            //для новой записи сумма плановой оплаты = сумме неоплаченного остатка
            $new_rec->id = null;
            $new_rec->docid = $doc->id;
            $new_rec->plnpaysum = $delta;
            $new_rec->fctpaysum = null;
            $new_rec->fctpay_by = null;
            $new_rec->fctpay_at = null;
            $new_rec->approved = 0;

            $new_rec->agr1_sum = null;
            $new_rec->agr1_by = null;
            $new_rec->agr1_at = null;
            $new_rec->agr1_notes = null;

            $new_rec->agr2_sum = null;
            $new_rec->agr2_by = null;
            $new_rec->agr2_at = null;
            $new_rec->agr2_notes = null;

            $rec->save();
            $new_rec->save();
            //dd($rec, $new_rec);

            //objlog::log_info();
        }
    }

}
