<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Cache;

class pay_category extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'pay_categories';
    static public $sysobjid = 903;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }


    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('firstread_at');
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }


    static public function lstActive()
    {
        //Cache::forget(self::$prefix . '_lstActive' );
        $data = Cache::remember(self::$prefix . '_lstActive', now()->addMinutes(15)
            , function () {
                $lst = self::select('id', 'name')
                    ->where('active', 1)
                    ->orderby('ordr')
                    ->orderby('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function usedAtInvoices()
    {
        //Cache::forget(self::$prefix . '_usedAtInvoices' );
        $data = Cache::remember(self::$prefix . '_usedAtInvoices', now()->addMinutes(15)
            , function () {
                $lst = self::from('pay_categories as pc')
                    ->where('active', 1)
                    ->whereRaw("exists(select 1 from invoices as inv where inv.categoryid=pc.id)")
                    ->select('id', 'name')
                    ->orderby('ordr')
                    ->orderby('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function is_invoice_drctpay($categoryid)
    {
        //Возвращает TRUE, если разрешена прямая оплата счета этой категории.
        // Например для категории "Материалы" счет на оплату может быть направлен только из заявки на материалы
        Cache::forget(self::$prefix . '_is_Invoice_DrctPay:' . $categoryid);
        $data = Cache::remember(self::$prefix . '_is_Invoice_DrctPay:' . $categoryid, now()->addMinutes(15)
            , function () use ($categoryid) {
                $cnt = self::where(['id' => $categoryid, 'invoice_drctpay' => 1, 'active' => 1])->count();
                return ($cnt > 0);
            }
        );
        return $data;

    }


    static public function lstFor($params)
    {
        //2021-02-25 SNS. универсальный конструктор массива с id, name категорий
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = "1=1";

            foreach ($params as $key => $val) {

                if (isset($val) and $val !== '') {

                    if (array_search($key, $used_params) == 0) {
                        $used_params[] = $key;

                        if ($key == 'in_orgplnpay_items') {
                            // использовалась в плане платежей как получатель
                            $sc .= " and " . (($val == 0) ? "not" : "")
                                . " exists (select 1 from orgplnpay_items as pi
                                    where pi.categoryid=pc.id)";

                        } elseif ($key == 'in_bills') {
                            // использовалась в счетах (не УПД)
                            $sc .= " and " . (($val == 0) ? "not" : "")
                                . " exists (select 1 from invoices as inv
                                    where inv.categoryid=pc.id and inv.doctypeid=1)";

                        } elseif ($key == 'for_user') {
                            // Пользователь включен в список доступа к записи
                            $sc .= " and " . (($val == 0) ? "not" : "")
                                . " exists (select 1 from obj_readers as ou
                                    where ou.sysobjid=903 and ou.objid=pc.id and userid={$val})";
                        }
                    }

                }
            }
            //Log::info($sc);

            $lst = self::from('pay_categories as pc')
                ->whereRaw($sc)
                ->select('id', 'name')
                ->orderBy('pc.name', 'desc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-10-14 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . $hash);
            return Cache::remember('lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }

}
