<?php

namespace App\Jobs;

use App\Events\docs1CLoadedEvent;
use App\extsystem;
use App\group;
use App\objextid;
use App\objflag;
use App\org;
use App\org_curator;
use App\orgdog;
use App\Traits\Result;
use App\User;
use App\userorg;
use App\usrsysright;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;

class importOrgs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filename;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }

    public function load_1sorg_xml_job($filename)
    {
        //Вариант для запуск через Job.
        // Быстрее в несколько раз, не зависит от тайм-аута веб-сервера

        //--- Настройки ---------------------------------
        $refModel = 'Контрагенты';

        $addUserWithoutEMail = false; //Не добавляем пользователей (представителей организации) у которых нет адреса ЭП

        $default_password = Hash::make('12345678');
        //-----------------------------------------------


        $res = new Result();
        $resMsg = '';

        if (isset($filename)) {

            $lf = '<br>';

            $resMsg .= $lf . 'Протокол обработки: <hr size="1">';
            $resMsg .= now() . ' - начало обработки';

            //Занесем в системный журнал:
            info("Начат парсинг файла с контрагентами 1С: " . $filename);

            $userid = $this->userid;


            $xml = simplexml_load_file($filename);


            $dataModel = $xml['Модель'];
            if (is_null($dataModel)) {
                $res->err = 1;
                $res->msg = 'Не задана модель данных! Ожидалось "' . $refModel . '". Импорт отменен.';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($userid, $resMsg));

                throw new \Exception($res->msg);
            }
            if (strtolower($dataModel) <> strtolower($refModel)) {
                $res->err = 1;
                $res->msg = 'Задана неизвестная модель данных: "' . $dataModel . '"! Ожидалось "' . $refModel . '".';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($userid, $resMsg));

                throw new \Exception($res->msg);
            }


            $extsyscode = $xml['КодСистемы'];

            if (is_null($extsyscode)) {

                $res->err = 1;
                $res->msg = 'Не задана внешняя система! Вероятно неподходящий файл.';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($userid, $resMsg));

                throw new \Exception($res->msg);
            }

            $resMsg .= $lf . now() . " - Внешняя система: $extsyscode";
            $this->log->info("Внешняя система: $extsyscode");
            $this->log->info("Модель данных: $dataModel");

            $extsys = extsystem::where('code', $extsyscode)->select('id')->first();
            $extsysid = isset($extsys) ? $extsys->id : null;
            if (!isset($extsysid)) {
                $res->err = 1;
                $res->msg = 'Неизвестная внешняя система: "' . $extsyscode . '"!';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($userid, $resMsg));

                throw new \Exception($res->msg);
            }

            //статистика для контрагентов
            $ins = 0;   //добавлено
            $upd = 0;   //обновлено
            $skp = 0;   //пропущено
            //статистика для пользователей/представителей
            $users_ins = 0;
            $users_upd = 0;
            $users_skp = 0;

            $parseCnt = 0;
            $npp = 0;
            $mngr_unknown = []; //Список несопоставленных менеджеров
            $grp_unknown = [];  //Список несопоставленных групп
//            $curdate = date_create_from_format('Y-m-d', now());
            $curdate = date('Y-m-d');
            info($curdate);

            $totDocCnt = count($xml->СправочникКонтрагенты->Контрагент);

            $resMsg .= $lf . now() . ' - Всего контрагентов: ' . $totDocCnt;
            $this->log->info("всего записей: $totDocCnt");

            //Для извлечения всего похожего на электронную почту
            $re_email = '/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i';
            //Для извлечения всего похожего на номера телефонов
//            $re_phone = '/\d{1}[\s\-(]*\d{3,4}[\s\-)]*\d{3}[\s\-]*\d{3,4}/i';
            //не очень надежно - можно отобрать номер, который превышает 11 цифр
            $re_phone = '/\d{1}[\s\-(]*\d{3,4}[\s\-)]*\d{2,3}[\s\-]*\d{2,4}[\s\-]*\d{2}/i';

            $nowDate = date('Y-m-d');

            foreach ($xml->СправочникКонтрагенты->Контрагент as $doc) {

                $npp++;

                try {
                    DB::beginTransaction();

                    $orgextid = trim($doc['КонтрагентКод']);
                    if (is_null($orgextid) or !isset($orgextid) or empty($orgextid)) {
                        $skp++;
                        $res->err = 1;
                        $res->msg = 'В записи #' . $npp . ' не задан код контрагента (аттрибут "КонтрагентКод")! Документ не загружен!';

                        throw new \Exception($res->msg);
                    }
                    $orgid = objextid::objid_by_extsysid_extid($extsysid, 111, $orgextid);
                    $orgname = trim($doc['КонтрагентНаименование']);

                    $org = null;
                    if (!isset($orgid)) {
                        //Добавим запись о новой организации по коду во внешней системе
                        $orgid = org::newOrgByExtID($extsysid, $orgextid, $orgname, $userid);
                        $ins++;

                    } else {

                        //перепроверим наличие записи о контрагенте
                        $org = org::find($orgid);

                        if (!isset($org)) {
                            //Не наашлась => создадим
                            $orgid = org::newOrgByExtID($extsysid, $orgextid, $orgname, $userid);
                            $ins++;
                        } else {
                            $upd++;
                        }
                    }

                    if (isset($orgid)) {

                        $org = org::find($orgid);

                        //Обновим что можем
                        $org->name = $orgname;
                        $org->orgtype = trim($doc['ТипОрганизации']) ?? '';
                        $org->contact_name = trim($doc['КонтактноеЛицо']) ?? '';
                        $org->contact_phone = trim($doc['КонтактныйТелефон']) ?? '';
                        $org->active = 1;
                        $org->updated_by = $userid;
                        $org->updated_at = now();
                        $org->save();
                        $orgid = $org->id;

                        //Добавим/обновим связку в OrgDogs
                        $orgdog = orgdog::where('orgid', $orgid)
                            ->where('ownorgid', 1)
                            ->where('active', 1)
                            ->first();

                        if (!isset($orgdog)) {
                            $orgdog = new orgdog([
                                'ownorgid' => 1,  //упрощаем - связываем с первой своей организацией
                                'orgid' => $orgid,
                                'dogdate' => $nowDate,
                                'dognum' => '-',
                                'active' => 1,
                            ]);
                            $orgdog->save();
                        }

                        //Добавим Тип организации в группы ---------------------------------------------------------
                        if ($org->orgtype != '') {
                            $grpextid = $org->orgtype;
                            //Если ранее уже занесли в "черный список", то не проверяем повторно
                            if (!in_array($grpextid, $grp_unknown)) {
                                $grpid = objextid::objid_by_extsysid_extid($extsysid, 822, $grpextid);
                                if (!isset($grpid)) {
                                    $res->msg = "В записи #$npp ($orgname) не найдено соответствие для группы: $grpextid";
                                    $this->log->error($res->msg);
                                    info($res->msg);
                                    $resMsg .= $lf . now() . $res->msg;
                                    $grp_unknown[] = $grpextid;
                                } else {
                                    //todo::Добавим/Обновим запись о текущей группе типа(GrpTypeID) "1" данной оргаизации
                                    group::addGrpItem($grpid, 111, $orgid, $orgname, $userid);
                                }
                            }
                        }
                        //------------------------------------------------------------------------------------------

                        //Если в имени контрагента есть аббревиатура " БПО"-----------------------------------------
                        // то добавим флаг 16 как признак клиента которому не требуется предоплата заказа
                        if (mb_strrpos($org->name,' БПО')) {
                            objflag::UpdObjFlag(111, $org->id, 16);
                        }
                        //------------------------------------------------------------------------------------------

                        //Попробуем добавить пользователей, если получится извлечь емэйл или телефон из контактной информации
                        $org_user = null;

                        if ($org->contact_name) {
                            //$this->log->info($org->contact_name);

//                                $tststr = '+7 950 286 3447 Анна Резниченко ann.chernyh.g@gmail.com Оплата по БН	sns@itqua.ruб +7(902)55512-55, +79025551235 8 (423) 240-1108, +7 4212 222-333 (89025553294)';
//                                preg_match_all($re_email, $tststr, $matches, PREG_SET_ORDER, 0);

                            preg_match_all($re_email, $org->contact_name, $matches, PREG_SET_ORDER, 0);
                            //var_dump($matches);

                            if ($matches and count($matches) > 0) {
//                                    dd($matches, $matches[0][0]);

                                $user_email = $matches[0][0];
                                //$this->log->info("email: $user_email");
                                $org_user = User::where('email', $user_email)->first();

                                if (!$org_user) {
                                    //добавим нового пользователя
                                    $org_user = new User([
                                        'name' => mb_substr($org->contact_name, 0, 255),
                                        'email' => $user_email,
                                        'password' => $default_password,
                                        'curorgid' => $org->id,
                                        'active' => 0,
                                    ]);
                                    $org_user->save();
                                    $users_ins++;

                                    //Установим права на просмотр заказов(70) и на их дублирование(213)
                                    usrsysright::setUsrSysRights($org_user->id, [70, 213]);
                                }
                            }
                        }

                        if ($org->contact_phone) {
                            preg_match_all($re_phone, $org->contact_phone, $matches, PREG_SET_ORDER, 0);
                            //var_dump($matches);
                            if ($matches and count($matches) > 0) {

                                //нормализуем номер телефона
                                $user_phone = preg_replace('/\D/i', '', $matches[0][0]);
                                if (!$org_user) {
                                    //Видимо не нашли/не создали по адресу электронной почты

                                    //Ищем по нормализованному телефону
                                    $org_user = User::where('phone', $user_phone)->first();

                                    if (!$org_user) {
                                        //Пока считаем, что если нет email, То и представителя НЕ ДОБАВЛЯЕМ
                                        if ($addUserWithoutEMail) {
                                            //добавим нового пользователя
                                            $org_user = new User([
                                                'name' => $user_phone,
                                                'email' => $user_phone,
                                                'phone' => $user_phone,
                                                'password' => $default_password,
                                                'curorgid' => $org->id,
                                                'active' => 0,
                                            ]);
                                            $org_user->save();
                                            $users_ins++;
                                        } else
                                            $users_skp++;
                                    }

                                } else {
                                    //пользователь известен но емэйлу - сохраним ему также номер телефона
                                    $org_user->phone = $user_phone;
                                    $org_user->save();
                                    $users_upd++;
                                }

                            }
                        }

                        if (isset($org_user)) {
                            //Пользователь известен/создан => проверим/добавим связку пользователя с организацией
                            userorg::addUserOrg($org_user->id, $org->id, $nowDate, $userid);
                        }

                        //                        $saleuserid = org_curator::curOrgCurator($orgid, $curdate);
//                        if (!isset($saleuserid)) {
//                            $saleuserid = org_curator::addGlobalCurator2Org($orgid, $curdate, $userid);
//                        }

                        //todo: Обработать "УровеньВложения1" и "УровеньВложения2"
                        // Для этого формата файла "УровеньВложения1" это менеджер(куратор)
                        // а "УровеньВложения2" - это группа типа "1"
                        $mngrextid = trim($doc['УровеньВложения1']);

                        //Если ранее уже занесли в "черный список", то не проверяем повторно
                        if ($mngrextid and !in_array($mngrextid, $mngr_unknown)) {

                            $mngrid = objextid::objid_by_extsysid_extid($extsysid, 3, $mngrextid);
                            if (!isset($mngrid)) {
                                $res->msg = 'В записи #' . $npp . ' не найдено соответствие для менеджера: ' . $mngrextid;
                                info($res->msg);
                                $this->log->error($res->msg);
                                $resMsg .= $lf . now() . $res->msg;
                                $mngr_unknown[] = $mngrextid;
                            } else {
                                //Добавим/Обновим запись о текущем кураторе данной оргаизации
                                org_curator::addOrgCurator($orgid, $mngrid, now(), $userid);
                            }
                        }

                        $grpextid = trim($doc['УровеньВложения2']);
                        //Если ранее уже занесли в "черный список", то не проверяем повторно
                        if ($grpextid and !in_array($grpextid, $grp_unknown)) {
                            $grpid = objextid::objid_by_extsysid_extid($extsysid, 822, $grpextid);
                            if (!isset($grpid)) {
                                $res->msg = 'В записи #' . $npp . ' не найдено соответствие для группы: ' . $grpextid;
                                $this->log->error($res->msg);
                                info($res->msg);
                                $resMsg .= $lf . now() . $res->msg;
                                $grp_unknown[] = $grpextid;
                            } else {
                                //todo::Добавим/Обновим запись о текущей группе типа(GrpTypeID) "1" данной оргаизации
                                group::addGrpItem($grpid, 111, $orgid, $orgname, $userid);
                            }
                        }

                    } else {
                        $skp++;
//                        $this->log->error("Не найдено соответствие для контрагента "
                    }
                } catch
                (\Exception $e) {
                    DB::rollback();
                    \Log::debug('------Exception---------');
                    \Log::debug($e->getMessage());
                    \Log::debug($e->getTraceAsString());

                    $this->log->error($e->getMessage());
                    $resMsg .= $lf . now() . ' - ОШИБКА: ' . $e->getMessage();

                } finally {
                    //\Log::debug('------finally---------');
                    DB::commit();   //для отображения записей через  $this->log->info/error/...
                }

                $parseCnt++;
                if ($parseCnt % 1000 == 0)
                    info('parser: ' . $parseCnt . ' - processed, ' . ($totDocCnt - $parseCnt) . ' - remained');
            }

            $res->msg = "обработка завершена: Контрагентов - добавлено : $ins; обновлено: $upd; пропущено: $skp;"
                . " Представителей - добавлено : $users_ins; обновлено: $users_upd; пропущено: $users_skp;";
            $resMsg .= $lf . now() . ' - ' . $res->msg;

            //Запустим событие об окончании обработки файла
            event(new docs1CLoadedEvent($userid, $resMsg));

        }
        return $res;
    }

}
