<?php

namespace App;

use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;

class ri_altname extends Model
{
    use \App\Traits\DeleteTrait;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid');
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function translittype()
    {
        return $this->hasOne(translittype::class, 'id', 'translittypeid');
    }


    public static function SearchNameByName($name)
    {
        //Возвращает альтернативный вариант для заданного названия
        $resultname = strtolower($name);

        if ($resultname <> "") {

            //Определены ли способы транслитерации?
            $convs = translittype::select('id')->where('active', 1)->get();

            if ($convs->count() > 0) {

                // символы '(', ')', '.', '/' заменим на пробел
                $ri_altname = preg_replace("/[\(\)\.\/]+/", " "
                    , $resultname);

                //если в названии встречаются конструкции типа "СЛОВО-ЧИСЛО",
                // то добавим их к исходному названию в варианте без "-"
                //чтобы "не напрягать" пользователя:
                $a1 = explode(" ", preg_replace("/([A-zА-я]+)-(\d+)+/", "\$1\$2", $ri_altname));
                $a2 = explode(" ", $ri_altname);
                $diff = join(' ', array_diff($a1, $a2));
                if ($diff <> "") {
                    $addname = ' ' . $diff;
                    foreach ($convs as $conv) {

                        //Конвертируем по правилам:
                        $altname = str_replace($conv->src, $conv->tgt, $diff);

                        //определим, есть ли отличия от оригинала
                        $a1 = explode(' ', $altname);
                        $a2 = explode(' ', $addname);
                        $altname = join(' ', array_diff($a1, $a2));

                        //сохраним только отличия от оригинала
                        if ($altname <> "") {
                            //$addname = $addname .' ::'.$conv->id.'| '.$altname;
                            $addname .= ' ' . $altname;

                            ++$conv->updCnt;
                        }
                    }
                }

                //для каждого варианта транслитерации протестируем - добавил ли он что-то новое
                // и если да - накопим в $addname для последующего сохранения
                $addname = $ri_altname;

                foreach ($convs as $conv) {

                    $translittypeid = $conv->id;

                    $lst = translit::select('src', 'tgt')
                        ->selectraw('if( isnull(translittypeid),2,1) as ordr')
                        ->whereraw('ifnull( translittypeid,' . $translittypeid . ')=' . $translittypeid)
                        ->orderbyraw('length(src) desc')
                        ->orderby('ordr')
                        ->orderby('id')
                        ->get()->pluck("tgt", "src")->toArray();

                    $conv->src = array_keys($lst);
                    $conv->tgt = array_values($lst);
                    $conv->updCnt = 0;

                    //Конвертируем по правилам:
                    $resultname = str_replace($conv->src, $conv->tgt, $ri_altname);

                    //определим, есть ли отличия от оригинала
                    $a1 = explode(' ', $resultname);
                    $a2 = explode(' ', $addname);
                    $resultname = join(' ', array_diff($a1, $a2));

                    //сохраним только отличия от оригинала
                    if ($resultname <> "") {
                        //$addname = $addname .' ::'.$conv->id.'| '.$altname;
                        $addname .= ' ' . $resultname;

                        ++$conv->updCnt;
                    }
                }
                // уберем двойные пробелы, и сохраним
                $resultname = preg_replace("/\s+/", " ", $addname);

            }
        }
        return $resultname;
    }

    public static function UpdAllRI_SearchName()
    {
        //Заполнение refitems.altname для всех записей, содержащих Английские буквы

        //Определены ли способы транслитерации?
        $convs = translittype::select('id')->where('active', 1)->get();

        $updCnt = 0;

        if ($convs->count() > 0) {

            //инициализируем таблицы перекодировки
            foreach ($convs as $conv) {
                $translittypeid = $conv->id;

                $lst = translit::select('src', 'tgt')
                    ->selectraw('if( isnull(translittypeid),2,1) as ordr')
                    ->whereraw('ifnull( translittypeid,' . $translittypeid . ')=' . $translittypeid)
                    ->orderbyraw('length(src) desc')
                    ->orderby('ordr')
                    ->orderby('id')
                    ->get()->pluck("tgt", "src")->toArray();

                $conv->src = array_keys($lst);
                $conv->tgt = array_values($lst);
                $conv->updCnt = 0;
//            dd($lst, $src, $tgt);
            }

            //Есть ли записи для перекодировки?
            $itms = refitem::select('id', 'name', 'altname')
                ->whereraw("name REGEXP '[a-zA-Z]'=1")
                ->get();

            if ($itms->count() > 0) {

                foreach ($itms as $ri) {

                    // символы '(', ')', '.', '/' заменим на пробел
                    $ri_altname = preg_replace("/[\(\)\.\/]+/", " "
                        , strtolower(trim($ri->name . ' ' . $ri->altname)));

                    //если в названии встречаются конструкции типа "СЛОВО-ЧИСЛО",
                    // то добавим их к исходному названию в варианте без "-"
                    //чтобы "не напрягать" пользователя:
                    $a1 = explode(" ", preg_replace("/([A-zА-я]+)-(\d+)+/", "\$1\$2", $ri_altname));
                    $a2 = explode(" ", $ri_altname);
                    $diff = join(' ', array_diff($a1, $a2));
                    $addname = "";
                    if ($diff <> "") {
//                        $ri_altname = $ri_altname . '| ' . $diff;

                        $addname = ' ' . $diff;

                        foreach ($convs as $conv) {

                            //Конвертируем по правилам:
                            $altname = str_replace($conv->src, $conv->tgt, $diff);

                            //определим, есть ли отличия от оригинала
                            $a1 = explode(' ', $altname);
                            $a2 = explode(' ', $addname);
                            $altname = join(' ', array_diff($a1, $a2));

                            //сохраним только отличия от оригинала
                            if ($altname <> "") {
                                //$addname = $addname .' ::'.$conv->id.'| '.$altname;
                                $addname .= ' ' . $altname;

                                ++$conv->updCnt;
                            }
                        }
                    }

                    //для каждого варианта транслитерации протестируем - добавил ли он что-то новое
                    // и если да - накопим в $addname для последующего сохранения
//                    $addname = $ri_altname;
                    foreach ($convs as $conv) {

                        //Конвертируем по правилам:
                        $altname = str_replace($conv->src, $conv->tgt, $ri_altname);

                        //определим, есть ли отличия от оригинала
                        $a1 = explode(' ', $altname);
                        $a2 = explode(' ', $ri_altname . ' ' . $addname);
                        $altname = join(' ', array_diff($a1, $a2));

                        //сохраним только отличия от оригинала
                        if ($altname <> "") {
                            //$addname = $addname .' ::'.$conv->id.'| '.$altname;
                            $addname .= ' ' . $altname;

                            ++$conv->updCnt;
                        }
                    }
                    // уберем двойные пробелы, и сохраним
                    $ri->searchname = preg_replace("/\s+/", " ", $addname);
                    $ri->save();
                }
            }

            //отметим событие
            foreach ($convs as $conv) {
                objlog::log_info(805, $conv->id,
                    'Преобразование произведено для ' . $conv->updCnt . ' элементов', 5);

                $updCnt += $conv->updCnt;
            }
        }
//        echo('Добавлено вариантов названий: ' . $updCnt);
        return $updCnt;
    }

}
