<?php

namespace App\Http\Controllers;

use App\ocl_item;
use Illuminate\Support\Facades\Auth;
use App\ac;
use App\buildopertype;
use App\contract;
use App\doctype;
use App\document;
use App\equiprqst;
use App\meeting;
use App\meeting_item;
use App\meeting_staff;
use App\mimetype;
use App\news;
use App\obj_reader;
use App\objlog;
use App\org;
use App\buildobj;
use App\orgstaff;
use App\qcheck_item;
use App\sysobj;
use App\Traits\UploadFileTrait;
use App\user_template;
use App\usrsysright;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Log;
use DB;
use App\objfile;
use App\sysfiletype;
use Illuminate\Http\Request;
use App\refitem;
use App\budget;
use App\budget_item;
use App\Post;
use App\orgplnpay;
use App\orgplnpay_item;
use App\invoice;
use App\doc;
use doc101;
use App\m15doc;
use App\Jobs\SendNotify;
use File;
use Response;

class ObjfileController extends Controller
{
    use UploadFileTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 11;
        $this->objcode = 'objfiles';
    }

    protected function setInterfaceRight($id, $sysobjid)
    {
        /*
         * Формирует массив прав пользователя на текущий объект ($id)
         * на основании прав пользователя на родительский объект ($sysobjid)
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = false;
        $usrrights['create'] = false;
        $usrrights['save'] = false;
        $usrrights['delete'] = false;

        $sysobj = sysobj::find($sysobjid);
        if (isset($sysobj)) {

            $sysobjcode = $sysobj->code;

            //замена на родительскую модель/таблицу
            //Заляпуха - todo: ввести в sysobjs поле src_acl
            if ($sysobjcode == 'jts_items')
                $sysobjcode = 'jobtimesheets';
            if ($sysobjcode == 'jts_machines')
                $sysobjcode = 'jobtimesheets';
            if ($sysobjcode == 'jts_violations')
                $sysobjcode = 'jobtimesheets';
            elseif ($sysobjcode == 'jts_mchn_visits')
                $sysobjcode = 'jobtimesheets';
            elseif ($sysobjcode == 'orgdeps')
                $sysobjcode = 'org_acnts';
            elseif ($sysobjcode == 'orgposts')
                $sysobjcode = 'org_acnts';

            $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.read');
            $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.create');

            if ($id == -1) {
                $usrrights['save'] = $usrrights['create'];
                $usrrights['delete'] = false;
            } else {
                $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.update');
                $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.delete');
            }
        }

        return $usrrights;
    }

    protected function setInterfaceRight0($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        if ($id == -1) {
            $usrrights['save'] = true;
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = false;
            $usrrights['delete'] = true;
        }

        return $usrrights;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function load($sysobjid, $objid)
    {

        $obj = null;
        $returl = null;
        if ($sysobjid == 151) {
            $obj = contract::find($objid);
            $returl = route('contracts.edit', $objid);
        } elseif ($sysobjid == 105) {
            $obj = refitem::find($objid);
            $returl = route('refitems.edit', $objid);
        } elseif ($sysobjid == 111) {
            $obj = org::find($objid);
            $returl = route('orgs.edit', $objid);
        } elseif ($sysobjid == 121) {
            $obj = orgstaff::find($objid);
            $returl = route('orgstaff.edit', $objid);
        } elseif ($sysobjid == 466) {
            $obj = buildobj::find($objid);
            $returl = route('buildobjs.edit', $objid);
        } elseif ($sysobjid == 856) {
            $obj = meeting::find($objid);
            $returl = route('meetings.edit', $objid);
        } elseif ($sysobjid == 858) {
            $obj = meeting_item::find($objid);
            $obj->name = 'Вопрос совещания: ' . $obj->question;
            $returl = route('meeting_items.edit', $objid);
        } elseif ($sysobjid == 863) {
            $obj = qcheck_item::find($objid);
            $obj->name = $obj->itmtype->name . ': ' . $obj->chkreport . ' / Основание, Факт:' . $obj->proof;
            $returl = route('qcheck_items.edit', $objid);
        } elseif ($sysobjid == 870) {
            $obj = equiprqst::find($objid);
            //$obj->name = $obj->itmtype->name.': ' . $obj->chkreport. ' / Основание, Факт:' . $obj->proof;
            $returl = route('equiprqsts.edit', $objid);
        } elseif ($sysobjid == 876) {
            $obj = budget::find($objid);
            //$obj->name = $obj->itmtype->name.': ' . $obj->chkreport. ' / Основание, Факт:' . $obj->proof;
            $returl = route('budgets.edit', $objid);
        } elseif ($sysobjid == 877) {
            $obj = budget_item::find($objid);
            //$obj->name = $obj->itmtype->name.': ' . $obj->chkreport. ' / Основание, Факт:' . $obj->proof;
            $returl = route('budget_items.edit', $objid);
        } elseif ($sysobjid == 895) {
            $obj = post::find($objid);
            $returl = route('posts.edit', $objid);
        } elseif ($sysobjid == 901) {
            $obj = orgplnpay::find($objid);
            $returl = route('orgplnpays.edit', $objid);
        } elseif ($sysobjid == 902) {
            $obj = orgplnpay_item::find($objid);
            $obj->name = "Оплата";
            $returl = route('orgplnpay_items.edit', $objid);
        } elseif ($sysobjid == 904) {
            $obj = news::find($objid);
            $obj->name = "Новость";
            $returl = route('news.edit', $objid);
        } elseif ($sysobjid == 915) {
            $obj = invoice::find($objid);
            $obj->name = "Счет на оплату №" . $obj->docnum;
            $obj->accept = ".pdf,.jpg,.jpeg";   //ограничение принимаемых типов файлов
            $returl = route('invoices.edit', $objid);

        } elseif ($sysobjid == 931) {
            $obj = doc::find($objid);
            $obj->name = "Журнал производства работ №" . $obj->docnum;
            $returl = route('docs101.edit', $objid);
        } elseif ($sysobjid == 936) {
            $obj = m15doc::find($objid);
            $obj->name = "М-15 №" . $obj->docnum;
            $returl = route('m15docs.edit', $objid);
        } elseif ($sysobjid == 951) {
            $model = 'App\event';
            $blades = 'events';
            $obj = $model::find($objid);
            $obj->name = $obj->Info;
            $returl = route($blades . '.edit', $objid);
        }
        //dd($sysobjid, $objid, $obj, $returl);

        if ($obj)
            return view('objfiles.load', compact(['sysobjid', 'objid', 'obj', 'returl']));
        else
            return back();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        $rules = [
            'sysobjid' => 'required',
            'objid' => 'required',
            'photos' => 'required',
            //'photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            'photos.*' => 'max:10240'
        ];

        $messages = [
            'sysobjid.required' => 'Не указан тип родительского объекта',
            'objid.required' => 'Не указан id родительской записи',
            'photos.required' => 'Выберите файл для загрузки',
            'photos.*.max' => 'Размер файла превышает лимит - 10МБ',
        ];

        $request->validate($rules, $messages);


        if ($request->hasfile('photos')) {

            $userid = Auth::user()->id;

            $load_anysize_right = usrsysright::isUserHasRightByCode_cached($userid, 'objfiles.load_anysize');

            $maxFileSize = 10 * 1024 * 1024;    //10MB

            $sysobjid = $request->sysobjid;
            $objid = $request->objid;
            $folder = 'files/' . $sysobjid . '/' . $objid . '/';


//            $refitem = machine::find($refitmid);
//            $product_slug = Str::slug($refitem->name);


            foreach ($request->photos as $file) {

                $filesize = $file->getSize();
                if ($load_anysize_right or $filesize < $maxFileSize) {

                    // Make a image name based on user name and current timestamp
                    //$name = str_slug($request->input('name')).'_'.time();

                    //имя файла без расширения
                    // - либо по оригинальному имени файла
                    $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                    // - либо имя файла делаем производным от названия продукта
                    //$name = $product_slug;

                    // - либо комбинируем
                    //$name = $product_slug . '_' . str_slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                    $extension = $file->getClientOriginalExtension();
                    $fullname = $name . '.' . $extension;

                    $mimetypeid = mimetype::where('extension', $extension)->select('id')->first()->id ?? null;

                    if (isset($mimetypeid)) {
                        // Make a file path where image will be stored [ folder path + file name + file extension]
                        $filePath = $folder . $fullname;

                        $fileuri = Storage::disk('public')->getAdapter()
                            ->applyPathPrefix($filePath);

                        if (file_exists($fileuri)) {
                            $name = $name . '_' . time();
                            $fullname = $name . '.' . $extension;
                            $filePath = $folder . $fullname;
                        }

                        // Загружаем файл на сервер
                        $this->uploadOne($file, $folder, 'public', $fullname);

                        $fileuri = Storage::disk('public')->getAdapter()
                            ->applyPathPrefix($filePath);

                        if (file_exists($fileuri)) {
                            // Save to table
                            $rec = new objfile();
                            $rec->sysobjid = $sysobjid;
                            $rec->objid = $objid;
                            $rec->sysfiletype_id = 4;   //todo: заплатка. Нужно разобраться почему этот параметр обязателен
                            $rec->mimetypeid = $mimetypeid;
                            $rec->publicfilename = $fullname;
                            $rec->systemfilename = $filePath;
                            $rec->filesize = $filesize;
                            $rec->notes = '';
                            $rec->doctypeid = 221;  //221-Прочие файлы
                            //$rec->ordr = ++$ordr;
                            $rec->save();
                        }
                    } else {
                        //неизвестный тип файла
                    }
                } else {
                    //превыщен допустимый размер файла
                }
            }
            //echo "Upload Successfully";
        }

        return back()->with('success', 'Файлы успешно загружены!');
    }


    public function create(Request $request, $sysobjid, $objid, $retroute = null)
    {
        return $this->edit($request, -1, $sysobjid, $objid, $retroute = null);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\objfile $objfile
     * @return \Illuminate\Http\Response
     */
    //public function edit(objfile $objfile)
    public function edit(Request $request, $id, $sysobjid = null, $objid = null, $retroute = null)
    {

        $userid = \Auth::user()->id;
        if ($id == -1) {
            //Значения "по - умолчанию" для новой записи
            $ordr = objfile::where(['sysobjid' => $sysobjid, 'objid' => $objid])->max('ordr') ?? 0;
            $ordr += 10;

            $rec = new objfile([
                'id' => -1,
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'active' => 1,
                'ordr' => $ordr,
                'created_by' => \Auth::user()->id,
            ]);
        } else {
            $rec = objfile::find($id);

            objlog::log_info($this->sysobjid, $rec->id, 'Открыта запись о файле', 5);
        }

//        if (!isset($rec))
//            return redirect(route($this->objcode . '.index'));

        $sysobj = sysobj::find($rec->sysobjid);
        $rec->_sysobj_name = $sysobj->name;

        if (isset($sysobj->model_class)) {
            $model = "App\\{$sysobj->model_class}";
            $obj = $model::find($rec->objid);
            //dd($model, $rec->objid, $obj);
            if (isset($obj))
                $rec->_obj_info = $obj->Info;
        }

        //dd($model,$rec->_obj_info);
        //ограничение на тип загружаемых файлов: ---------------
        // todo - ввести поле sysobjs.file_accept
        if ($rec->sysobjid == 915)
            //$rec->accept = '.pdf,.xls,.xlsx,.doc,.docx';
            $rec->accept = '.pdf,.xls,.xlsx';
        else
            $rec->accept = '';  //без ограничений
        //------------------------------------------------------

        $retroute = $request->returl;
        if (!isset($retroute)) {

            if (isset($sysobj->code)) {
                $retroute = route(strtolower($sysobj->code) . '.edit', $rec->objid);

            } else {

                if ($rec->sysobjid == 951) {
                    $model = 'App\event';
                    $blades = 'events';
                    $obj = $model::find($objid);
                    $rec->_obj_info = $obj->Info;
                    $retroute = route($blades . ' . edit', $rec->objid);
                }
            }
        }

        $rec->doctypes = doctype::lstFor([
            //'is_root' => 1,
            'active_or_current' => $rec->doctypeid,
        ]);

//        $rec->docsubtypes = doctype::lstFor([
//            'parent_id' => $rec->doctypeid ?? 0,
//            'active_or_current' => $rec->docsubtypeid,
//        ]);
        $rec->docsubtypes = [];

        //Временно - определим значение по-умолчанию для файла связанного со Счетом(invoices.doctypeid=1) или с УПД(2)
        //todo: Сделать спр-к sysobj_doctypes - как список допустимых типов файлов для системного объекта
        if (!isset($rec->doctypeid)) {
            if ($rec->sysobjid == 915) {
                $rec->doctypeid = (invoice::find($objid)->doctypeid == 2) ? 218 : 217;
            }
        }


        $usrrights = $this->setInterfaceRight($id, $rec->sysobjid);

        //право на создание записи в архиве документов (1701) по файлу. С перепривязкой файла к созданной записи
        $usrrights['make_doc_by_file'] = false;
        if ($rec->sysobjid == 1701) {
            //если к записи привязано более 1 файла - то можно...
            $usrrights['make_doc_by_file']
                = (objfile::where(['sysobjid' => $rec->sysobjid, 'objid' => $rec->objid])->count() > 1);
        }

//        $ObjFlags = objflag::getFlags4Obj(107, $id);

        return view('objfiles.edit', compact('rec', 'retroute', "usrrights"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\objfile $objfile
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {
        //

        $sysobjid = $request->get('sysobjid');
        $objid = $request->get('objid');

        if ($id == -1) {
            $rules = [
                "id" => "required",
                'doc.*' => 'required',
                //'doc.*' => 'mimes:pdf,doc,docx,zip',
                //'doctypeid .*' => 'required',
            ];

        } else {
            $rules = [
                "id" => "required",
                'doctypeid.*' => 'required',
            ];
        }

        $messages = [
            'id.required' => 'Не указан id записи',
            'doc.*.required' => 'Не выбран файл с документом',
            'doctypeid.*.required' => 'Не указан тип документа',
        ];
        if ($sysobjid == 915) {
            $rules['doc.*'] .= '|mimes:pdf,doc,docx,xls,xlsx';
            $messages += ['doc.*.mimes' => 'Допустимы только файлы типа: pdf, doc, docx, xls, xlsx'];
        }

        $request->validate($rules, $messages);
        //dd($sysobjid, $rules, $messages);

        $userid = \Auth::user()->id;


        $disk = 'local';
        //$disk = 'ftp';

        $docids = $request->docid;
        $docs = $request->doc;
        $doctypeids = $request->doctypeid;
        $docsubtypeids = $request->docsubtypeid;


        $returl = $request->get('returl');
        if (!isset($returl)) {

            //определим по родительскому объекту
            $sysobjcode = sysobj::find($sysobjid)->code;
            if (isset($sysobjcode)) {
                $returl = route($sysobjcode . '.edit', $objid);

            } else
                $returl = route('home');
        }


        $mess = '';
        $bUpdated = false;
        if ($request->hasfile('doc')) {

            //добавление новых файлов - создание новых записей

            $userid = Auth::user()->id;

            $load_anysize_right = usrsysright::isUserHasRightByCode_cached($userid, 'objfiles.load_anysize');

            $maxFileSize = 10 * 1024 * 1024;    //10MB
            //$folder = 'files/' . $sysobjid . '/' . $objid . '/';
            $folder = "files/{$sysobjid}/{$objid}/";

            $ordr = $request->get('ordr')
                ?? (objfile::where(['sysobjid' => $sysobjid, 'objid' => $objid])->max('ordr') ?? 0) + 10;

            foreach ($docs as $key => $doc) {


                if (isset($doc)) {
                    //$doc = $docs[$key] ?? null;
                    //dd($id, $doc, $doctypeid);

                    if ($id == -1) {
                        $rec = new objfile([
                            'sysobjid' => $sysobjid,
                            'objid' => $objid,
                            'active' => 1,
                            'created_by' => \Auth::user()->id,
                        ]);
                    } else {
                        $rec = objfile::find($id);
                    }

                    if (isset($rec)) {

                        $file = $doc;

                        $bSave = true;  //признак возможности сохранения

                        $filesize = $file->getSize();
                        if ($load_anysize_right or $filesize < $maxFileSize) {

                            //имя файла без расширения
                            // - либо по оригинальному имени файла
                            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                            $name = str_replace(' ', '_', $name);

                            $extension = $file->getClientOriginalExtension();
                            $fullname = $name . '.' . $extension;

                            $mimetypeid = mimetype::where('extension', $extension)->select('id')->first()->id ?? null;
                            if (isset($mimetypeid)) {
                                // Make a file path where image will be stored [ folder path + file name + file extension]
                                $filePath = $folder . $fullname;

                                //$fileuri = Storage::disk('public')->getAdapter()->applyPathPrefix($filePath);
                                //$fileuri = Storage::disk('local')->getAdapter()->applyPathPrefix($filePath);
                                $fileuri = Storage::disk($disk)->getAdapter()->applyPathPrefix($filePath);

                                //if ($disk == 'local' and file_exists($fileuri)) {
                                if (Storage::disk($rec->disk)->exists($fileuri)) {
                                    $name = $name . '_' . time();
                                    $fullname = $name . '.' . $extension;
                                    $filePath = $folder . $fullname;
                                }

                                // Загружаем файл на сервер
                                //$this->uploadOne($file, $folder, 'public', $fullname);
                                //$this->uploadOne($file, $folder, 'local', $fullname);
                                $this->uploadOne($file, $folder, $disk, $fullname);

                                //$fileuri = Storage::disk('public')->getAdapter()->applyPathPrefix($filePath);
                                //$fileuri = Storage::disk('local')->getAdapter()->applyPathPrefix($filePath);
                                $fileuri = Storage::disk($disk)->getAdapter()->applyPathPrefix($filePath);

                                dd($fullname,$disk,$fileuri, Storage::disk($disk)->exists($fileuri)
                                , Storage::disk('local')->exists($fileuri)
                                , Storage::exists($fileuri)
                                    ,file_exists($fileuri), //this returns true
                                File::exists($fileuri) //this returns true
                                );

                                if (Storage::disk($disk)->exists($fileuri)) {
                                    // Save to table
                                    $rec->sysfiletype_id = 4;   //todo: заплатка. Нужно разобраться почему этот параметр обязателен
                                    $rec->mimetypeid = $mimetypeid;
                                    $rec->publicfilename = $fullname;
                                    $rec->systemfilename = $filePath;
                                    $rec->filesize = $filesize;
                                    //$rec->ordr = ++$ordr;
                                    //$rec->save();
                                }
                            } else {
                                //неизвестный тип файла
                                $bSave = false;
                            }
                        } else {
                            //превышен допустимый размер файла
                            $bSave = false;
                            return redirect($returl)->with('error', 'Файл не загружен! Размер файла превышает 10МБ.');

                        }

                        if ($bSave) {

                            $rec->disk = $disk;
                            $rec->doctypeid = $doctypeids[$key] ?? 221; //если не задано - считаем "Прочий документ"
                            $rec->docsubtypeid = $docsubtypeids[$key];
                            $rec->notes = mb_substr($request->get('notes'), 0, 256);
                            $rec->ordr = $ordr;
                            $rec->updated_by = $userid;
                            $rec->updated_at = now();
                            //dd($rec);
                            $rec->save();
                            //dd($rec);

                            $mess = "Запись о файле создана";
                            objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

                            $bUpdated = true;

                            $ordr += 10;

                        }

                    }

                }
                //end of cycle by files
            }

        } else {
            //изменение текущей записи
            $rec = objfile::find($id);

            if (isset($rec)) {

                $ordr = $request->get('ordr');
                if (!isset($ordr)) {
                    $ordr = objfile::where(['sysobjid' => $sysobjid, 'objid' => $objid])->max('ordr') ?? 0;
                    $ordr += 10;
                }


                $rec->notes = mb_substr($request->get('notes'), 0, 256);
                $rec->doctypeid = $request->get('doctypeid')[0] ?? 221; //если не задано - считаем "Прочий документ"
                $rec->docsubtypeid = $request->get('docsubtypeid')[0];
                $rec->ordr = $ordr;
                $rec->updated_by = $userid;
                $rec->updated_at = now();
                $rec->save();

                $mess = "Запись о файле обновлена";
                objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
            }

        }

        //dd($sysobjid, $objid, $docids, $docs, $doctypeids);


        if ($bUpdated) {
            //зачистим кэш для списка типов документов с образами, для данного $sysobjid
            Cache::forget('doctypes_lstUsedForSysObj_' . $sysobjid);

            //todo: сделать уведомление участников / читателей об изменении информации
            // в зависимости от объекта привязки
            $subj = "Уведомление о документе (" . $rec->publicfilename . ")";

            if ($rec->sysobjid == 856) {
                //Meeting - участники - в таблице meeting_staffs
                $recipients = meeting_staff::from('meeting_staffs as ms')
                    ->join('orgstaff as os', 'os . id', 'ms . staffid')
                    ->join('users as u', 'u . id', 'os . userid')
                    ->where('ms . protid', $rec->id)
                    ->wherenotnull('os . userid')
                    ->select('u .*')
                    ->get();
            }

            if (isset($recipients)) {
                //dd($recipients);
                $ref_url = route('objfiles . edit', $rec->id);
                $lstrcpts = '';

                foreach ($recipients as $recipient) {

                    if (isset($recipient)) {
                        $email = $recipient->email;
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                            //для журнала сформируем список получателей
                            $lstrcpts .= ' ' . $recipient->lname
                                . ' ' . mb_substr($recipient->fname, 0, 1) . ' . '
                                . mb_substr($recipient->mname, 0, 1) . ' . (' . $email . ');';

                            //$email = 'shevchenko . s@basko . su';
                            //$email = 'snsusa02@gmail . com';

                            $msg = "Здравствуйте, " . $recipient->fname . " " . $recipient->mname . "!"
                                . "<br>"
                                . "<br>Вам необходимо ознакомиться с документом: <b>" . $rec->publicfilename . "</b>"
                                . "<br><hr>"
                                . " <a href='" . $ref_url . "'>Перейти к документу</a>";
                            //dd($subj, $msg);
                            dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        }

//                        if (isset($recipient->userid)) {
//                            $subj = "Новый документ";
//                            $msg = $obj->info;
//                            user_notice::addOrUpdate($eventtypeid, $ref_url, $rcpt->userid, $subj, $msg, now(), null, $userid);
//                        }

                    }
                }
            }

        }

//        $returl = $request->get('returl');
//        if (!isset($returl)) {
//
//            //определим по родительскому объекту
//            $sysobjcode = sysobj::find($sysobjid)->code;
//            if (isset($sysobjcode)) {
//                $returl = route($sysobjcode . '.edit', $objid);
//
//            } else
//                $returl = route('home');
//        }

        return redirect($returl)->with('success', $mess);
    }


    public function destroyfile(Request $req, $id)
    {//удаление файла с фото перенесено в модель
        $returl = $req->returl ?? null;

        if (!isset($returl)) {
            $objfile = objfile::find($id);
            $sysobj = sysobj::find($objfile->sysobjid);
            $returl = route($sysobj->code . '.edit', $objfile->objid);
        }
        //dd($id,$returl);

        $delcnt = objfile::destroy($id);
        if ($delcnt) {
            //todo: сделать вменяемый код на возврат в исходную форму

            if (isset($returl))
                return redirect($returl)->with('success', 'Файл удален. ');
            else
                return back()->with('success', 'Файл удален. ');
        }
        if (isset($returl))
            return redirect($returl)->with('warning', 'Ошибка удаления файла!');
        else
            return back()->with('warning', 'Ошибка удаления файла!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\objfile $objfile
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $req)
    {

        $req->validate([
            "retroute" => "required",
            "id" => "required"
        ]);
        $id = $req->id;
        $retroute = $req->retroute;
        $sd = array();
        try {

            $res = objfile::destroy($id);
            $objid = null;
            if (array_key_exists('objid', $res->obj)) {
                $objid = $res->obj['objid'];
            }
            if ($res->err == 1) {
                $route = route('objfiles . edit', ['id' => $id, 'retroute' => $retroute]);
                $sd["error"] = $res->msg;
            } else {
                $route = route($req->retroute, $objid);
                $sd['success'] = 'Файл экспорта удален';
            }
        } catch (\Exception $e) {
            $route = route('objfiles . edit', ['id' => $id, 'retroute' => $retroute]);
            $sd["error"] = "Ошибка удаления файла экспорта";
        }
        return redirect($route)->with($sd);
    }

    public function getFile($id)
    {
        $rec = objfile::select("publicfilename", 'systemfilename'
            , 'ft . storage', 'ft . catalog', 'mt . extension', 'mt . mimetype')
            ->from('objfiles as fi')
            ->join('sysfiletypes as ft', 'ft . id', ' = ', 'fi . sysfiletype_id')
            ->join('mimetypes as mt', 'mt . id', ' = ', 'fi . mimetypeid')
            ->where('fi . id', $id)->first();
        if (!isset($rec)) return redirect()->back()->with('warning', 'Файл с данным ID не найден');
        $filename = $rec->catalog
            . " / "
            . $rec->systemfilename;
        $mime = $rec->mimetype;
        $fileuri = Storage::disk($rec->storage)->getAdapter()->applyPathPrefix($filename);
        if (!\file_exists($fileuri)) return redirect()->back()->with('warning', 'Файл с данными не найден на сервере');
        return response()->download(
            $fileuri,
            $rec->publicfilename,
            [
                'Content - Type' => $mime,
                'Content - Description' => 'File Transfer',
                'attachment;filename = ' . $rec->publicfilename,
            ]
        );
    }

    protected function get($sysobjid, $objid, $filename)
    {
        $userid = \Auth::user()->id;

        //проверим полномочия, в зависимости от типа объекта $sysobjid
        //$access = false;
        $access = true;
        if ($sysobjid == 151) {
            //договоры -------------
            $access = usrsysright::isUserHasRightByCode_cached($userid, 'contracts . read');
            if (!$access) {
                $access = obj_reader::isUserInList($sysobjid, $objid, $userid);
            }

        }

        if ($access) {
            objlog::log_info($sysobjid, $objid, "открыт файл {$filename}");

            $fileuri = Storage::disk('local')->getAdapter()->applyPathPrefix("files/{$sysobjid}/{$objid}/{$filename}");

            if (!file_exists($fileuri)) return redirect(route('home'))->with('warning', 'Файл не доступен!');

            objlog::log_info($sysobjid, $objid, "открыт файл {$filename}");

            $mimetype = File::mimeType($fileuri);

            ob_end_clean(); //!!! ВАЖНО - очистка буфера ответа от BOM

//        return response()->file(
//            $fileuri
//            , ['Content - Type' => $mimetype]
//        );

            $file = File::get($fileuri);
            $type = File::mimeType($fileuri);
            $response = Response::stream(function () use ($file, $type) {
                echo $file;
            }, 200, ["Content-Type" => $type]);
            return $response;
        } else {
            objlog::log_info($sysobjid, $objid, "несанкционированная попытка открытия файла {$filename}");
            return redirect(route('home'))->with('warning', 'Файл не доступен!');
        }
    }

    protected function getbyid($id)
    {
        $userid = \Auth::user()->id;

        $rec = objfile::find($id);
        if (!isset($rec))
            return null;

        $sysobjid = $rec->sysobjid;
        $objid = $rec->objid;
        $disk = $rec->disk;
        $systemfilename = $rec->systemfilename;
        $filename = $rec->publicfilename;

        //проверим полномочия, в зависимости от типа объекта $sysobjid
        //$access = false;
        $access = true;
        if ($sysobjid == 151) {
            //договоры -------------
            $access = usrsysright::isUserHasRightByCode_cached($userid, 'contracts.read');
            if (!$access) {
                $access = obj_reader::isUserInList($sysobjid, $objid, $userid);
            }
        }

        if ($access) {
            //проверим - есть ли в родительской записи ограничение по категории информации (ACSID)
            $sysobj = sysobj::find($sysobjid);
            if (isset($sysobj)) {
                $parent_model = $sysobj->model_class;
                if (isset($parent_model)) {
                    $parent_model = 'App\\' . $parent_model;
                    $parent_rec = $parent_model::find($objid);
                    if (isset($parent_rec->acsid)) {
                        $access = ac::userHasAcs($userid, $parent_rec->acsid);
                    }
                }
            }
        }

        if ($access) {
            objlog::log_info($sysobjid, $objid, "открыт файл {$systemfilename}");
            objlog::log_info($this->sysobjid, $id, "открыт файл {$systemfilename}");

            $fileuri = Storage::disk($disk)->getAdapter()->applyPathPrefix($systemfilename);
//            dd($disk, $fileuri
//                , Storage::disk('public')->exists($fileuri)
//                , $systemfilename
//                , Storage::disk($disk)->exists($systemfilename)
//            );

            //$file_exist = Storage::disk($disk)->exists($fileuri);
            $file_exist = Storage::disk($disk)->exists($systemfilename);
            if (!$file_exist and $disk == 'local') {
                //попробуем найти на диске public
                $disk = 'public';
                $file_exist = Storage::disk($disk)->exists($systemfilename);
                if ($file_exist) {
                    //приведем в порядок запись о диске
                    objfile::where('id', $id)->update(['disk' => 'public']);
                }
            }

            if (!$file_exist)
                return redirect(route('home'))->with('warning', 'Файл не доступен!');

            objlog::log_info($sysobjid, $objid, "открыт файл {$filename}");

            $type = $rec->mimetype->mimetype;

            ob_end_clean(); //!!! ВАЖНО - очистка буфера ответа от BOM

            if (1 == 0) {
                //загрузка файла
                return Storage::disk($disk)->download(
                    $systemfilename,
                    $rec->publicfilename,
                    [
                        "Content-Type" => $type,
                        "Content-Description" => 'File Transfer',
                        "attachment;filename=" . $rec->publicfilename,
                    ]
                );
            } else {
                //открытие в браузере
                return Storage::disk($disk)->download($systemfilename, $rec->publicfilename
                    , [
                        "Content-Type" => $type,
                        "Content-Disposition" => "inline;filename=" . $rec->publicfilename
                    ]);
            }

        } else {
            objlog::log_info($sysobjid, $objid, "несанкционированная попытка открытия файла {$filename}");
            return redirect(route('home'))->with('warning', 'Файл не доступен!');
        }
    }


    public function make_document($objfile_id)
    {
        //Преобразование файла, связанного с документом в документ Архива документов, связанный с исходным документом
        if (!isset($objfile_id))
            return redirect(route('documents.index'))->with(['error' => 'не задан файл!']);

        $objfile = objfile::find($objfile_id);
        if (!isset($objfile))
            return redirect(route('documents.index'))->with(['error' => 'не найден файл!']);

        //нас интересует только файл, связанный с документом (sysobjid = 1701)
        if ($objfile->sysobjid <> 1701)
            return redirect(route('documents.index'))->with(['error' => 'файл не связан с Архивом документов!']);

        $src_doc = document::find($objfile->objid);
        if (!isset($src_doc))
            return redirect(route('documents.index'))->with(['error' => 'не найден исходный документ!']);

        // как определить, что файл еще не является основным в документе?
        // ? может считать, что если к документу привязан единственный файл (этот), то уже не требуется создавать новый документ?
        $cnt = objfile::where(['sysobjid' => 1701, 'objid' => $objfile->objid])->count();
        if ($cnt == 1)
            return redirect(route('documents.index'))->with(['error' => 'файл - единственный в документе. Не требуется создавать новый документ!']);
        //dd($cnt, $objfile);


        //=> файл не единственный, можно выделять в документ ------------

        $userid = \Auth::user()->id;

        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, 'documents.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, 'documents.create'),
            'save' => usrsysright::isUserHasRightByCode_cached($userid, 'documents.create'),
            //право изменения категории доступа
            'acs.edit' => (!isset($rec->acsid) or User::user_has_acs_cached($userid, $rec->acsid)),
        );

        if ($usrrights['create']) {

            //Значения "по-умолчанию" для новой записи

            //создадим шаблон на основе исходного документа/ Сместим sysobjid,
            // чтобы не пересекаться с обычным шаблоном для Архива док-тов
            document::make_template($src_doc->id, 1701 * 1);

            $newData = [];

            $tmplt = user_template::getTemplate($userid, 1701 * 1);
            if (isset($tmplt->document)) {
                $newData = (array)$tmplt->document; //конвертируем в массив
            } else {
                $newData['ownorgid'] = \Auth::user()->curorgid;
            }

            //Добавим свои значения
            $newData['id'] = -1;
            $newData['active'] = 1;
            $newData['created_by'] = \Auth::user()->id;
            $newData['doctypeid'] = $objfile->doctypeid;
            $newData['name'] = $objfile->doctype->name;

            $rec = new document($newData);

            $rec->tags_lst = $newData['tags'] ?? '';

        }

        //преобразуем для нормальной работы <INPUT TYPE="DATE"...
        //if (isset($rec->plnbegdt))
        //    //$rec->plnbegdt = strftime('%Y-%m-%dT%H:%M:%S', strtotime($rec->plnbegdt));
        //    $rec->plnbegdt = strftime('%Y-%m-%dT%H:%M', strtotime($rec->plnbegdt));
        if (isset($rec->docdate))
            $rec->docdate = strftime('%Y-%m-%d', strtotime($rec->docdate));
        if (isset($rec->docbegdate))
            $rec->docbegdate = strftime('%Y-%m-%d', strtotime($rec->docbegdate));
        if (isset($rec->docenddate))
            $rec->docenddate = strftime('%Y-%m-%d', strtotime($rec->docenddate));
        if (isset($rec->ownorg_regdate))
            $rec->ownorg_regdate = strftime('%Y-%m-%d', strtotime($rec->ownorg_regdate));
        if (isset($rec->org_regdate))
            $rec->org_regdate = strftime('%Y-%m-%d', strtotime($rec->org_regdate));
        if (isset($rec->ref_regdate))
            $rec->ref_regdate = strftime('%Y-%m-%d', strtotime($rec->ref_regdate));


        $rec->linked_parent_docid = $src_doc->id;
        $rec->linked_parent_doc = null;
        if (isset($rec->linked_parent_docid)) {
            $rec->linked_parent_doc = document::find($rec->linked_parent_docid);

            $rec->objrolename = 'приложение';
            $rec->lnkobjrolename = 'основной документ';

            //имитируем
            $rec->files = objfile::where(['id' => $objfile_id])->get();
        }
        //dd($rec->linked_parent_doc->name);

        $rec->parent_objfile_id = $objfile_id;

        $rec->dirtypes = document::dirtypes();

        $rec->acs = ac::lstFor(['active_or_current' => $rec->acsid]);

        $rec->statuses = document::statuses();

        $rec->ownorgs = org::lstFor(['flagtypeid' => 12]);

        $rec->contracts = contract::lstFor(['between_orgs' => [$rec->src_orgid, $rec->tgt_orgid]]);

        $rec->ocl_items = ocl_item::lstFor_cached([
            'orgid' => $rec->ownorgid
        ]);

        $rec->buildobjs = buildobj::lstFor([
            'active_or_current' => $rec->buildobjid,
        ]);
        $rec->buildopertypes = buildopertype::lstFor([
            'buildobjid' => $rec->buildobjid,
        ]);

        return view('documents.edit', compact('rec', "usrrights"));

    }


}
