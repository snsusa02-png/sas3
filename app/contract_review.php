<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class contract_review extends Model
{
    //
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'contract_reviews';
    static public $sysobjid = 156;

    protected $table = 'contract_reviews';
    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function contract()
    {
        //return $this->hasOne(contract::class, 'id', 'contractid');
        return $this->belongsTo(contract::class, 'contractid');
    }

    static public function statuses()
    {
        return [0 => 'черновик', 1 => 'в процессе', 2 => 'завершено'];
    }

    public function contrrev_users()
    {
        return $this->hasMany(contrrev_user::class, 'contrrevid', 'id');
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
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

    static public function lstFor($params)
    {
        //2021-04-17 SNS. универсальный конструктор массива с id, name согласований договора
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

                        if ($key == 'contractid') {
                            $sc .= " and contractid={$val}";
                        }
                    }

                }
            }
            //Log::info($sc);

            $lst = self::from('contract_reviews as cr')
                ->whereRaw($sc)
                ->select('id', 'descript as name')
                ->orderBy('cr.begdt', 'desc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


}
