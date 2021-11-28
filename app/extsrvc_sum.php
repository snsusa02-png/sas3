<?php

namespace App;

use App\extsrvc_sum;
use App\Traits\DeleteTrait;
use App\Traits\Result;
use Log;
use Illuminate\Database\Eloquent\Model;
use Goutte\Client;
use App\Events\notifyEvent;


class extsrvc_sum extends Model
{
    use DeleteTrait;

    static public $prefix = 'extsrvc_sums';
    static public $sysobjid = 982;

    protected $guarded = [];

    public function service()
    {
        return $this->hasOne(org_extservice::class, 'id', 'srvcid')->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public static function upddaysums($srvcid)
    {
        //расчет и заполнение среднего расхода за день - для заданного сервиса

        $recs = self::where('srvcid', $srvcid)
            ->orderby('ondate', 'asc')
            ->get();
        //dd($recs);

        $pre_restdate = null;
        $pre_restsum = null;
        $tot_daysum = 0;
        $avg_cnt = 0;
        $avg_daysum = null;

        foreach ($recs as $rec) {
            //dd($rec->pre_restdate, $pre_restdate, $rec->pre_restdate !== $pre_restdate);
            //dd($rec->pre_restdate, $pre_restdate, $rec->pre_restdate !== $pre_restdate);
            if (1 == 0 or $rec->pre_restdate !== $pre_restdate
                or $rec->pre_restsum !== $pre_restsum) {
                $rec->pre_restdate = $pre_restdate;
                $rec->pre_restsum = $pre_restsum;
//dd(!is_null($rec->pre_restdate), !is_null($rec->pre_restsum), $rec->pre_restdate !== $rec->ondate);
                if (!is_null($rec->pre_restdate) and !is_null($rec->pre_restsum) and $rec->pre_restdate !== $rec->ondate) {
                    $days = date_diff(date_create($rec->pre_restdate), date_create($rec->ondate))->d;
                    //dd($days);
                    if ($days > 0) {
                        $rec->daysum = ($rec->restsum - $rec->pre_restsum) / $days;
                        if ($rec->daysum < 0) {
                            //для рачета среднего учитываем только расход - чтобы не ошибиться на пополнении счета
                            $tot_daysum += $rec->daysum;
                            $avg_cnt += 1;
                            $avg_daysum = $tot_daysum / $avg_cnt;
                        }
                    }
                }
                $rec->avg_daysum = $avg_daysum;
                $rec->save();
            }
            $pre_restdate = $rec->ondate;
            $pre_restsum = $rec->restsum;
        }
    }


    public static function avg_daysum($srvcid, $days_cnt = 30)
    {
        $daysums = self::where('srvcid', $srvcid)
            ->whereRaw("ondate >= date_sub(curdate(), INTERVAL 30 day)")
            ->where('daysum', '<', 0)
            ->orderby('ondate', 'desc')
            ->selectraw("sum(daysum) as sums, count(*) as cnt")
            ->first();
        return (isset($daysums) and $daysums->cnt > 0)
            ? $daysums->sums / $daysums->cnt
            : null;
    }

    public static function last_restsum($srvcid)
    {
        //самый свежий остаток (не из будущего)
        return self::where('srvcid', $srvcid)
                ->whereRaw("info_dt <= now()")
                ->orderby('info_dt', 'desc')
                ->select('restsum')
                ->first()->restsum ?? null;
    }

    public static function import_megafon()
    {
        //Обновление данных по остатку средств на субсчетах "Мегафон"
        // extsysid=105

        $result = new Result();

        $recs = org_extservice::where(['extsysid' => 105, 'active' => 1])
            ->whereNotNull('lk_url')
            ->whereNotNull('username')
            ->whereNotNull('password')
            ->orderBy('updated_at', 'asc')
            ->get();

        if (count($recs) > 0) {

            $client = new Client();

            foreach ($recs as $rec) {

                if (1 == 1) {
                    try {

                        $crawler = $client->request('GET', $rec->lk_url);
                        $html = $client->getResponse()->getContent();
                        dd($crawler,$html);

                        //Проверим, может мы уже вошли в ИС ПНП - тогда нужно выйти
                        $btn = $crawler->selectButton('Продолжить');

                        if (null !== $btn->getNode(0)) {
                            $form = $btn->form();
                            $crawler = $client->submit($form, []);

                            //переоткроем страницу с логином
                            $crawler = $client->request('GET', $rec->lk_url);
                        }


                        $form = $crawler->selectButton('Продолжить')->form();
                        $crawler = $client->submit($form, array('login' => $rec->username, 'password' => $rec->password));

                        $crawler->filter('.errors')->each(function ($node) {
                            var_dump($node->text());
                        });
                        dd(222,$crawler);


                        //$crawler = $client->click($crawler->selectLink('Sign in')->link());


                        //$infodts = $crawler->filter('#actual')->each(function ($node) {
                        $infodts = $crawler->filter('.label--update')->each(function ($node) {
                            print $node->text() . "<br>";
                            $words = explode(' ', $node->text());
                            var_dump($words);
                            if (isset($words[0])) {
                                $dmy = explode('.', $words[0]);
//                            var_dump($dmy);
                                if (isset($words[1])) {
                                    $infodt = date_create_from_format('Y-m-d H:i', substr($dmy[2], 0, 4)
                                        . '-' . $dmy[1] . '-' . $dmy[0] . ' ' . $words[1]);
                                } else
                                    $infodt = date_create_from_format('Y-m-d', substr($dmy[2], 0, 4)
                                        . '-' . $dmy[1] . '-' . $dmy[0]);
                                return ($infodt);
                            }
                        });
                        //dd(333,$infodts,$infodts[0]);

//                    $sums = $crawler->filter('.infotab tr')->each(function ($node) {
                        $sums = $crawler->filter('.text--currency')->each(function ($node, $i) {
                            //var_dump($i, $node->text(), floatval(str_replace(' ', '', $node->text())));
                            return floatval(str_replace(' ', '', $node->text()));
                        });
                        //dd(555, $sums);

                        // выйдем из ИС ПНП --------------------------------------------------------------
                        if (1 == 0) {
                            $btn = $crawler->selectButton('Выйти');
                            if (null !== $btn->getNode(0)) {
                                $form = $btn->form();
                                $crawler = $client->submit($form, []);
                            }
                        }
                        //--------------------------------------------------------------------------------
//                    var_dump($infodts);
//                    var_dump($sums);
                        //dd($infodts, $sums);

                        if (count($infodts) > 0 and count($sums) > 0) {

                            $infodt = $infodts[0]->format('Y-m-d H:i:s');
                            $infodate = $infodts[0]->format('Y-m-d');
                            //dd($infodt, $infodate);

                            $extsrvc_sum = \App\extsrvc_sum::where(['srvcid' => $rec->id, 'ondate' => $infodate])->first();
                            if (!isset($extsrvc_sum)) {
                                $extsrvc_sum = new \App\extsrvc_sum([
                                    'srvcid' => $rec->id,
                                    'ondate' => $infodate
                                ]);
                            }
                            $extsrvc_sum->restsum = $sums[0] ?? null;
                            $extsrvc_sum->info_dt = $infodt;
                            $extsrvc_sum->updated_at = $infodt;
                            $extsrvc_sum->updated_by = 1;
                            $extsrvc_sum->save();


                            //Пересчитаем дневной расход/приход -------------------------------
                            self::upddaysums($rec->id);
                            //-----------------------------------------------------------------

                            //Рассчитаем средний дневной расход за последние 30 дней ----------
                            $avg_daysum = self::avg_daysum($rec->id, 30);
                            //-----------------------------------------------------------------

                            //обновим лимиты
                            $rec->rest_sum = $sums[0];
                            $rec->notify_limsum = $sums[1];
                            $rec->lock_limsum = $sums[2];
                            $rec->rest_dt = $infodt;
                            $rec->avg_daysum = $avg_daysum;

                            //$rec->pre_rest_sum = $pre_rest_sum;
                            //$rec->day_sum_diff = $day_sum_diff;

                            $rec->updated_at = now();
                            $rec->updated_by = 1;
                            $rec->save();

                            if ($rec->avg_daysum < 0) {
                                $est_days2lock = ($rec->rest_sum - $rec->lock_limsum) / -$rec->avg_daysum;
                                if ($est_days2lock < 3) {
                                    //сгенерим событие о низком остатке оплаты у провайдера
                                    event(new notifyEvent('org_extservices.low_sum', 981, $rec->id, 0));
                                }
                            }
                            //dd($pre_rec, $pre_rest_sum, $day_sum_diff);

                            $result->err = 0;
                            $result->msg = 'Обновлены данные сервисов, доступных для обновления данных!';
                        }
                    } catch (\Exception $e) {
                        Log::debug($e->getMessage());
                        //return $e->getMessage();

                    } finally {
                    }
                }
            }
        } else {
            $result->err = 1;
            $result->msg = 'Нет сервисов, доступных для обновления данных!';
        }

        return $result;
    }

    public static function import_pnp()
    {
        //Обновление данных по остатку средств на субсчетах ОАО "Приморнефтепродукт"
        // extsysid=104

        $result = new Result();

        $recs = org_extservice::where(['extsysid' => 104, 'active' => 1])
            ->whereNotNull('username')
            ->whereNotNull('password')
            ->orderBy('updated_at', 'asc')
            ->get();

        if (count($recs) > 0) {

            $client = new Client();

            foreach ($recs as $rec) {

                if (1 == 1) {
                    try {

                        //$crawler = $client->request('GET', 'http://www.pnp.aoil.ru/system/login.html');
                        $crawler = $client->request('GET', 'http://lk.primornp.ru/');


                        //Проверим, может мы уже вошли в ИС ПНП - тогда нужно выйти
                        $btn = $crawler->selectButton('Выйти');

                        if (null !== $btn->getNode(0)) {
                            $form = $btn->form();
                            $crawler = $client->submit($form, []);

                            //переоткроем страницу с логином
                            $crawler = $client->request('GET', 'http://lk.primornp.ru/');
                        }


                        $form = $crawler->selectButton('Войти')->form();
                        $crawler = $client->submit($form, array('login' => $rec->username, 'password' => $rec->password));

                        $crawler->filter('.errors')->each(function ($node) {
                            var_dump($node->text());
                        });
                        //dd(222,$crawler);


                        //$crawler = $client->click($crawler->selectLink('Sign in')->link());


                        //$infodts = $crawler->filter('#actual')->each(function ($node) {
                        $infodts = $crawler->filter('.label--update')->each(function ($node) {
                            print $node->text() . "<br>";
                            $words = explode(' ', $node->text());
                            var_dump($words);
                            if (isset($words[0])) {
                                $dmy = explode('.', $words[0]);
//                            var_dump($dmy);
                                if (isset($words[1])) {
                                    $infodt = date_create_from_format('Y-m-d H:i', substr($dmy[2], 0, 4)
                                        . '-' . $dmy[1] . '-' . $dmy[0] . ' ' . $words[1]);
                                } else
                                    $infodt = date_create_from_format('Y-m-d', substr($dmy[2], 0, 4)
                                        . '-' . $dmy[1] . '-' . $dmy[0]);
                                return ($infodt);
                            }
                        });
                        //dd(333,$infodts,$infodts[0]);

//                    $sums = $crawler->filter('.infotab tr')->each(function ($node) {
                        $sums = $crawler->filter('.text--currency')->each(function ($node, $i) {
                            //var_dump($i, $node->text(), floatval(str_replace(' ', '', $node->text())));
                            return floatval(str_replace(' ', '', $node->text()));
                        });
                        //dd(555, $sums);

                        // выйдем из ИС ПНП --------------------------------------------------------------
                        if (1 == 0) {
                            $btn = $crawler->selectButton('Выйти');
                            if (null !== $btn->getNode(0)) {
                                $form = $btn->form();
                                $crawler = $client->submit($form, []);
                            }
                        }
                        //--------------------------------------------------------------------------------
//                    var_dump($infodts);
//                    var_dump($sums);
                        //dd($infodts, $sums);

                        if (count($infodts) > 0 and count($sums) > 0) {

                            $infodt = $infodts[0]->format('Y-m-d H:i:s');
                            $infodate = $infodts[0]->format('Y-m-d');
                            //dd($infodt, $infodate);

                            $extsrvc_sum = \App\extsrvc_sum::where(['srvcid' => $rec->id, 'ondate' => $infodate])->first();
                            if (!isset($extsrvc_sum)) {
                                $extsrvc_sum = new \App\extsrvc_sum([
                                    'srvcid' => $rec->id,
                                    'ondate' => $infodate
                                ]);
                            }
                            $extsrvc_sum->restsum = $sums[0] ?? null;
                            $extsrvc_sum->info_dt = $infodt;
                            $extsrvc_sum->updated_at = $infodt;
                            $extsrvc_sum->updated_by = 1;
                            $extsrvc_sum->save();


                            //Пересчитаем дневной расход/приход -------------------------------
                            self::upddaysums($rec->id);
                            //-----------------------------------------------------------------

                            //Рассчитаем средний дневной расход за последние 30 дней ----------
                            $avg_daysum = self::avg_daysum($rec->id, 30);
                            //-----------------------------------------------------------------

                            //обновим лимиты
                            $rec->rest_sum = $sums[0];
                            $rec->notify_limsum = $sums[1];
                            $rec->lock_limsum = $sums[2];
                            $rec->rest_dt = $infodt;
                            $rec->avg_daysum = $avg_daysum;

                            //$rec->pre_rest_sum = $pre_rest_sum;
                            //$rec->day_sum_diff = $day_sum_diff;

                            $rec->updated_at = now();
                            $rec->updated_by = 1;
                            $rec->save();

                            if ($rec->avg_daysum < 0) {
                                $est_days2lock = ($rec->rest_sum - $rec->lock_limsum) / -$rec->avg_daysum;
                                if ($est_days2lock < 3) {
                                    //сгенерим событие о низком остатке оплаты у провайдера
                                    event(new notifyEvent('org_extservices.low_sum', 981, $rec->id, 0));
                                }
                            }
                            //dd($pre_rec, $pre_rest_sum, $day_sum_diff);

                            $result->err = 0;
                            $result->msg = 'Обновлены данные сервисов, доступных для обновления данных!';
                        }
                    } catch (\Exception $e) {
                        Log::debug($e->getMessage());
                        //return $e->getMessage();

                    } finally {
                    }
                }
            }
        } else {
            $result->err = 1;
            $result->msg = 'Нет сервисов, доступных для обновления данных!';
        }

        return $result;
    }

    public static function import_pnp0()
    {
        //Обновление данных по остатку средств на субсчетах ОАО "Приморнефтепродукт"
        // extsysid=104

        $result = new Result();

        $recs = org_extservice::where(['extsysid' => 104, 'active' => 1])
            ->whereNotNull('username')
            ->whereNotNull('password')
            ->orderBy('updated_at', 'asc')
            ->get();

        if (count($recs) > 0) {

            $client = new Client();

            foreach ($recs as $rec) {

                if (1 == 1) {
                    try {

                        $crawler = $client->request('GET', 'http://www.pnp.aoil.ru/system/login.html');

                        //$crawler = $client->click($crawler->selectLink('Sign in')->link());

                        //Проверим, может мы уже вошли в ИС ПНП - тогда нужно выйти
                        $btn = $crawler->selectButton('Выйти');
                        if (null !== $btn->getNode(0)) {
                            $form = $btn->form();
                            $crawler = $client->submit($form, []);

                            //переоткроем страницу с логином
                            $crawler = $client->request('GET', 'http://www.pnp.aoil.ru/system/login.html');
                        }

                        $form = $crawler->selectButton('Войти')->form();
                        $crawler = $client->submit($form, ['username' => $rec->username, 'password' => $rec->password]);

                        $infodts = $crawler->filter('#actual')->each(function ($node) {
//                        print $node->text() . "<br>";
                            $words = explode(' ', $node->text());
//                        var_dump($words);
                            if (isset($words[3])) {
                                $dmy = explode('.', $words[3]);
//                            var_dump($dmy);
                                if (isset($words[4])) {
                                    $infodt = date_create_from_format('Y-m-d H:i', substr($dmy[2], 0, 4)
                                        . '-' . $dmy[1] . '-' . $dmy[0] . ' ' . $words[4]);
                                } else
                                    $infodt = date_create_from_format('Y-m-d', substr($dmy[2], 0, 4)
                                        . '-' . $dmy[1] . '-' . $dmy[0]);
                                return ($infodt);
                            }
                        });

                        $sums = $crawler->filter('.infotab tr')->each(function ($node) {
                            $tds = explode(':', $node->text());
                            //print $node->text()."<br>";
//                        var_dump($tds);
                            if ($tds[0] == 'Остаток на счёте') {
                                return floatval($tds[1]);
                            } elseif ($tds[0] == 'Уведомление') {
                                return floatval($tds[1]);
                            } elseif ($tds[0] == 'Блокировка') {
                                return floatval($tds[1]);
                            }
                        });

                        // выйдем из ИС ПНП --------------------------------------------------------------
                        $crawler = $client->request('GET', 'http://www.pnp.aoil.ru/system/login.html');

                        //Проверим, может мы уже вошли в ИС ПНП - тогда нужно выйти
                        $btn = $crawler->selectButton('Выйти');
                        if (null !== $btn->getNode(0)) {
                            $form = $btn->form();
                            $crawler = $client->submit($form, []);
                        }
                        //--------------------------------------------------------------------------------
//                    var_dump($infodts);
//                    var_dump($sums);

                        if (count($infodts) > 0 and count($sums) > 0) {

                            $infodt = $infodts[0]->format('Y-m-d H:i:s');
                            $infodate = $infodts[0]->format('Y-m-d');
                            //dd($infodt, $infodate);

                            $extsrvc_sum = \App\extsrvc_sum::where(['srvcid' => $rec->id, 'ondate' => $infodate])->first();
                            if (!isset($extsrvc_sum)) {
                                $extsrvc_sum = new \App\extsrvc_sum([
                                    'srvcid' => $rec->id,
                                    'ondate' => $infodate
                                ]);
                            }
                            $extsrvc_sum->restsum = $sums[0] ?? null;
                            $extsrvc_sum->info_dt = $infodt;
                            $extsrvc_sum->updated_at = $infodt;
                            $extsrvc_sum->updated_by = 1;
                            $extsrvc_sum->save();


                            //Пересчитаем дневной расход/приход -------------------------------
                            self::upddaysums($rec->id);
                            //-----------------------------------------------------------------

                            //Рассчитаем средний дневной расход за последние 30 дней ----------
                            $avg_daysum = self::avg_daysum($rec->id, 30);
                            //-----------------------------------------------------------------

                            //обновим лимиты
                            $rec->notify_limsum = $sums[2];
                            $rec->lock_limsum = $sums[3];
                            $rec->rest_sum = $sums[0];
                            $rec->rest_dt = $infodt;
                            $rec->avg_daysum = $avg_daysum;

                            //$rec->pre_rest_sum = $pre_rest_sum;
                            //$rec->day_sum_diff = $day_sum_diff;

                            $rec->updated_at = now();
                            $rec->updated_by = 1;
                            $rec->save();

                            if ($rec->avg_daysum < 0) {
                                $est_days2lock = ($rec->rest_sum - $rec->lock_limsum) / -$rec->avg_daysum;
                                if ($est_days2lock < 3) {
                                    //сгенерим событие о низком остатке оплаты у провайдера
                                    event(new notifyEvent('org_extservices.low_sum', 981, $rec->id, 0));
                                }
                            }
                            //dd($pre_rec, $pre_rest_sum, $day_sum_diff);

                            $result->err = 0;
                            $result->msg = 'Обновлены данные сервисов, доступных для обновления данных!';
                        }
                    } catch (\Exception $e) {
                        Log::debug($e->getMessage());
                        //return $e->getMessage();

                    } finally {
                    }
                }
            }
        } else {
            $result->err = 1;
            $result->msg = 'Нет сервисов, доступных для обновления данных!';
        }

        return $result;
    }

}
