<?php

use App\budget;
use App\objfile;
use App\user_notice;
use Illuminate\Support\Facades\Route;

//use Goutte\Client;
//use Webklex\IMAP\Client;
use Illuminate\Support\Facades\Storage;
use Webklex\PHPIMAP\Client;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Spatie\Sitemap\SitemapGenerator;
use Illuminate\Support\Facades\Http;
use Webklex\PHPIMAP\ClientManager;

Route::get('sitemap', function () {
    //SitemapGenerator::create('http://basco.test')->writeToFile("mysitemap");
    Spatie\Sitemap\SitemapGenerator\SitemapGenerator::create('http://daloil.test')->writeToFile("mysitemap");
});


Route::get('/tstd4', function () {
    //\App\orgplnpay::removeExpiredItems();
    \App\org::impfrm_egrul(213);

});

Route::get('/test1/', 'testController@test1')->name('test.1');

Route::get('/tst5/', 'orgController@tst_panther')->name('orgs.tst_panther');


Route::get('/tst_ftp', function () {

    $filePath = '321.txt';
    $url = Storage::disk('ftp')->url($filePath);
    $fileuri = Storage::disk('ftp')->getAdapter()->applyPathPrefix($filePath);
    dd($fileuri, file_exists($fileuri));

    dd($url);

});

Route::get('/tstd3', function () {
    $categories = \App\itmtype::getCategories();
    //dd($categories);

    return view('d3.test1', compact('categories'));
//        ->withCategories($categories);

});

Route::get('/tstday', function () {
    $categories = \App\itmtype::getCategories();
    //dd($categories);

    return view('test.day', compact('categories'));
//        ->withCategories($categories);

});

Route::get('/tst125', function () {


    //Performs a regex-texthighlight
//    function textHighlight($text, $search, $highlightColor = '#0000FF', $casesensitive = false)
//    {
//        $modifier = ($casesensitive) ? 'i' : '';
//        //quote search-string, cause preg_replace wouldn't work correctly if chars like $?. were in search-string
//        $quotedSearch = preg_quote($search, '/');
//        //generate regex-search-pattern
//        $checkPattern = '/' . $quotedSearch . '/' . $modifier;
//        //generate regex-replace-pattern
//        $strReplacement = '$0';
//        $strReplacement = '<span style="color:' . $highlightColor . ';">$0</span>';
//        return preg_replace($checkPattern, $strReplacement, $text);
//    }

    $text = 'Подсветка искомого текста по регулярному выражению. Would you be so kind to highlight css-tricks.com in this string css-tricks.com?';
    //$search = 'css-tricks.com';
    $search = 'выраж';

    echo textHighlight($text, $search);
    return;

    function detect_city($ip)
    {

        $default = 'UNKNOWN';

        if (!is_string($ip) || strlen($ip) < 1 || $ip == '127.0.0.1' || $ip == 'localhost')
            $ip = '8.8.8.8';

        $curlopt_useragent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6 (.NET CLR 3.5.30729)';

        //$url = 'http://ipinfodb.com/ip_locator.php?ip=' . urlencode($ip);
        $url = 'http://api.ipinfodb.com/v3/ip-city/?key=YOUR_API_KEY&ip=' . urlencode($ip);
        $ch = curl_init();

        $curl_opt = array(
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT => $curlopt_useragent,
            CURLOPT_URL => $url,
            CURLOPT_TIMEOUT => 1,
            CURLOPT_REFERER => 'http://' . $_SERVER['HTTP_HOST'],
        );

        curl_setopt_array($ch, $curl_opt);

        $content = curl_exec($ch);
        dd($content);

        if (!is_null($content)) {
            $curl_info = curl_getinfo($ch);
        }

        curl_close($ch);

        if (preg_match('{<li>City : ([^<]*)</li>}i', $content, $regs)) {
            $city = $regs[1];
        }
        if (preg_match('{<li>State/Province : ([^<]*)</li>}i', $content, $regs)) {
            $state = $regs[1];
        }

        if ($city != '' && $state != '') {
            $location = $city . ', ' . $state;
            return $location;
        } else {
            return $default;
        }

    }

    function clean($value)
    {

        // If magic quotes not turned on add slashes.
        if (!get_magic_quotes_gpc()) // Adds the slashes.
        {
            $value = addslashes($value);
        }

        // Strip any tags from the value.
        $value = strip_tags($value);

        // Return the value out of the function.
        return $value;
    }

    function user_ip()
    {
        $ip = null;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    echo detect_city('86.102.124.106');


});
Route::get('/tst124', function () {
    return view('test.tst121');
});

Route::get('/tst119', function () {
    $categories = \App\itmtype::getCategories();
    //dd($categories);

    return view('itmtypes.tree', compact('categories'));
//        ->withCategories($categories);

});

Route::get('/tst120', function () {
    var_dump(\App\usrsysright::isUserHasRightByCode(12, 'equiprqsts.process'));
    var_dump(\App\usrsysright::isUserHasRightByCode(12, 'equiprqsts.process', 111, 1));
    var_dump(\App\usrsysright::isUserHasRightByCode(12, 'equiprqsts.process', 111, 6));
    //var_dump(\App\usrsysright::isUserHasRightByCode_cached(12, 'equiprqsts.process'));

});

Route::get('/tst121', function () {
    \App\proj_mailbox::getNewMail();
});


Route::get('/tst123', function () {

    //PHP_EOL
    //$msg = "\r\n(!) Внимание!\r\n{today()} необходимо сделать следущее: \r\n 1 - asdasdasdas \r\n 2 - http://192.168.33.112/\r\n - ...";
    //$msg = "\r\n(!) Проверка " . now();
    $ref_url = "http://sk-basko.su/";
    $msg = "Новый счет № " . 333
        . "\r\nПоставщик: " . "Рога и Копыта"
        . "\r\nПолучатель: " . "СК БАСКО"
        . "\r\nПримечание: " . "это тест"
        . "\r\n\r\nПерейти к документу: {$ref_url}";

    $msg = "\r\nНеобходимо перевыставить счет №" . 5678
        . "\r\n    Поставщик: " . "Рога и Копыта"
        . "\r\n   Получатель: " . "СК БАСКО"
        . "\r\n   Примечание: " . "это тест"
        . "\r\n\r\n   Перейти к документу: {$ref_url}";

    $msg = now() . " сообщение из Информационной системы ГК БАСКО {$ref_url}";
    //var_dump($msg);
    \App\myChat::sendMsg(915, 1, $msg, 12);

}); //->middleware('auth');

Route::get('/tst222', function () {
    //dd(Request::fullUrl(), Route::current(), Route::currentRouteName(), Route::currentRouteAction());

    $date = '2020-10-19';
    $date = null;
    $date = ($date) ?? now()->format('Y-m-d');
    $date = date_create($date)->format('Y-m-d');
    dd($date);


    $equiprqst = \App\equiprqst::find(16);
    //dd($equiprqst->id, $equiprqst->orgid, $equiprqst->contractid);
    if (isset($equiprqst)) {

        //найдем собственный бюджет подрядчика по заявке на этот вид работ
        $bdgt = budget::from('budgets as b')
            ->join('budget_items as bi', 'bi.budgetid', 'b.id')
            ->where([
                'b.orgid' => $equiprqst->orgid, //подрядчик
                'bi.buildopertypeid' => $equiprqst->buildopertypeid,
                'b.par_contractid' => $equiprqst->contractid,
            ])
            ->select('b.id', 'b.parid', 'b.orgid')
            ->first();
        //dd($equiprqst->id, $equiprqst->orgid, $equiprqst->contractid, $bdgt);

        $org_childs = [];  //будет содержать id организации дочернего бюджета для текущей организации
        if (isset($bdgt)) {

            $preorgid = $bdgt->orgid;
            while (isset($bdgt)) {

                $org_childs[$bdgt->orgid] = $preorgid;

                $preorgid = $bdgt->orgid;

                //предыдущий бюджет
                $bdgt = budget::select('id', 'parid', 'orgid')->find($bdgt->parid);
            }
        }
        //var_dump($org_childs);

        //сформируем список уникальных ЦФО (владельцев бюджета) для данной заявки
        $bdgtorgs = \App\equiprqst_item::from('equiprqst_items as eri')
            ->join('budget_itmsums as bis', 'bis.id', 'eri.bdgtitmsumid')
            ->join('budgets as b', 'b.id', 'bis.budgetid')
            ->where('eri.rqstid', $equiprqst->id)
            ->wherenotNull('eri.bdgtitmsumid')
            ->select('b.orgid as bdgtorgid')
            ->distinct()
            ->get();
        //dd($bdgtorgs);

        foreach ($bdgtorgs as $bdgtorg) {
            //dd($bdgtorg);
            $m15srcorgid = $bdgtorg->bdgtorgid; //всегда источником материалов для м-15 является владелец бюджета

            if ($bdgtorg->bdgtorgid == $equiprqst->orgid) {

                $m15tgtorgid = $bdgtorg->bdgtorgid;

            } else {
                //нужно найти связь между указанным бюджетом и подрядчиком (*: НВС->СКБ->СУ7 )
                // и ближайшую "дочку" (получателя материалов) в направлении от владельца бюджета к подрядчику
                // (*: Если использован бюджет НВС, а подрядчик СУ7, то передача будет НВС->СКБ

                //аосспользуемся ранее сформированным массивом зависимостей
                $m15tgtorgid = $org_childs[$bdgtorg->bdgtorgid] ?? -1;
            }
            print('<br> m15srcorgid= ' . $m15srcorgid . ', m15tgtorgid= ' . $m15tgtorgid);

            if (isset($m15srcorgid) and isset($m15tgtorgid)) {
                \App\equiprqst_item::where(['rqstid' => $equiprqst->id, 'bdgtorgid' => $m15srcorgid])
                    ->update(['m15srcorgid' => $bdgtorg->bdgtorgid, 'm15tgtorgid' => $m15tgtorgid]);
            }
        }

    }


});

//Route::get('/tst222', function () {
//    $enddt = new DateTime('first day of this month');
//    $enddt = new DateTime('tomorrow');
//    dd($enddt);
//});

Route::get('/pie', function () {
//    return redirect("/pie.html");
    return view('charts.pie');
});

Route::get('/gantt', function () {
    return view('gantt');
});

Route::get('/img', function () {
    //intervention
    $img = Image::make('images/demo.jpg')->resize(300, 200);

    return $img->response('jpg');
});

//Route::get('/', function () {
//    return view('welcome');
//});
Route::get('/', 'HomeController@welcome')->name('welcome');


Auth::routes();


// Email related routes
Route::get('mail/send', 'MailController@send');
Route::get('sendbasicemail','MailController@basic_email');
Route::get('sendhtmlemail','MailController@html_email');
Route::get('sendattachmentemail','MailController@attachment_email');
Route::get('send_checkrqst/{id}','MailController@email_checkrqst')->name('checkrqsts.send');

//Route::get('/api/calendar', 'MyCalendarController@index');

Route::get('/home', 'HomeController@index')->name('home');

//для CKEditor (4)
//Route::post('ckeditor/image_upload', 'CKEditorController@upload')->name('upload');

//Сообщения
Route::post('/msg/add', 'ObjMsgController@store')->name('obj_msgs.add');


Route::get('/rqsts', function () {
    return view('rqsts.index');
})->middleware('auth')->name('rqsts');

Route::get('/office', function () {
    return view('office.index');
})->middleware('auth')->name('office');

Route::get('/planning', function () {
    return view('planning.index');
})->middleware('auth')->name('planning');

Route::get('/finmon', function () {
    return view('finance.index');
})->middleware('auth')->name('finance');

Route::get('/admin', 'AdminController@index')->name('admin');


Route::get('/rprts', function () {
    //todo: не выходит вернуться в список отчетов
    //return view('reports.index');
    return redirect('/admin#nsi-rep');
})->middleware('auth')->name('reports');

//Пользователи (управление)
Route::match(array('GET', 'POST'), '/users', "UserManage@index")->name('users.index');
Route::get('users/{id}', 'UserManage@edit')->name('users.edit');
Route::get('usrsysrights/{id}/{limsysobjid}/{limobjid}/edt', 'UserManage@edtSysRights')->name('users.sysrights');
Route::match(array('GET', 'POST'), 'usermanage/updatesysrights/{id}/{limsysobjid}/{limobjid}', "UserManage@updUsrSysRights")->name("users.updatesysrights");
Route::match(array('POST', 'PUT'), 'usermanage/{id}', "UserManage@update")->name('users.update');
Route::put('users/{id}/delete', "UserManage@destroy")->name("users.delete");
Route::put('/setDefaultPassword/{userid}', 'UserManage@setDefaultPasswordForUser')->name('users.resetpassword');
Route::match(array('POST', 'GET'), '/user_clone_rights/{id}', "UserManage@clone_rights")->name('users.clone_rights');
Route::match(array('GET', 'POST'), '/user_save_cloned_rights/{id}/{limsysobjid}/{limobjid}', "UserManage@save_cloned_rights")->name("users.save_cloned_rights");

//Категории информации, доступные пользователю
Route::get('user_acs/create/{userid}/', "UserAcController@create")->name('user_acs.create');
Route::get('user_acs/{id}/', 'UserAcController@edit')->name('user_acs.edit');
Route::match(array('POST', 'PUT'), 'user_acs/{id}', "UserAcController@update")->name('user_acs.update');
Route::put('user_acs/{id}/delete', "UserAcController@destroy")->name("user_acs.delete");

Route::get('/profile', 'ProfileController@index')->name('profile');
Route::post('/profile/update', 'ProfileController@updateProfile')->name('profile.update');

Route::get('/changePassword', 'ProfileController@showChangePasswordForm');
Route::post('/changePassword', 'ProfileController@changePassword')->name('changePassword');

//ограничения права на объекты компаний
//Route::match(array('GET', 'POST'), '/usrsysright_orgs/{usrsysrightid}', "UsrsysrightOrgController@index")->name("usrsysright_orgs.index");
//Route::get('usrsysright_orgs/{id}/edit', 'UsrsysrightOrgController@edit')->name('usrsysright_orgs.edit');
//Route::get('usrsysright_orgs/create/{usrsysrightid}', 'UsrsysrightOrgController@create')
//    ->name('usrsysright_orgs.create');
//Route::match(array('POST', 'PUT'), 'usrsysright_orgs/{id}', "UsrsysrightOrgController@update")
//    ->name('usrsysright_orgs.update');
//Route::put('usrsysright_orgs/{id}/delete', "UsrsysrightOrgController@destroy")->name("usrsysright_orgs.delete");


// отчеты по пользователям
Route::match(array('POST', 'GET'), '/users/rep/5', "UserReportController@rep05")->name('reports.rep5');
Route::match(array('POST', 'GET'), '/users/rep/10', "UserReportController@rep10")->name('reports.rep10');
Route::match(array('POST', 'GET'), '/users/rep/11', "UserReportController@rep11")->name('reports.rep11');


//Смена текущей представляемой пользователем организации
Route::get('/setuserorg/{orgid}', "UserManage@setCurOrgID")->name('usersetcurorg');

Route::get('userorgs/{id}', 'UserorgController@edit')->name('userorgs.edit');
Route::get('userorgs/create/{userid}', 'UserorgController@create')->name('userorgs.create');
Route::match(array('POST', 'PUT'), 'userorgs/{id}', "UserorgController@update")
    ->name('userorgs.update');
Route::put('userorgs/{id}/delete', "UserorgController@destroy")->name("userorgs.delete");


//Сортировка списка
Route::get('/sort/{field}/{retroute}', 'SortController@index_sort')->name('set_sort');

//Просмотр журнала событий объекта
Route::get('/evntlog/{sysobjid}/{objid}/{route}', "ObjlogController@evntlog")->name('objevntlog');

//Внешние системы
Route::match(array('GET', 'POST'), '/extsystems', "ExtsystemController@index")->name("extsystems.index");
Route::get('extsystems/create', "ExtsystemController@create")->name('extsystems.create');
Route::get('extsystems/{id}/edit', 'ExtsystemController@edit')->name('extsystems.edit');
Route::match(array('POST', 'PUT'), 'extsystems/{id}', "ExtsystemController@update")->name('extsystems.update');
Route::put('extsystems/{id}/delete', "ExtsystemController@destroy")->name("extsystems.delete");


//Ареал использования Внешних систем
Route::get('/extsys_sysobjs/create/{extsysid}', "ExtsysSysobjController@create")->name('extsys_sysobjs.create');
Route::get('/extsys_sysobjs/edit/{id}', 'ExtsysSysobjController@edit')->name('extsys_sysobjs.edit');
Route::match(array('POST', 'PUT'), '/extsys_sysobjs/update/{id}/{extsysid}', "ExtsysSysobjController@update")->name('extsys_sysobjs.update');
Route::put('/extsys_sysobjs/delete/{id}', "ExtsysSysobjController@destroy")->name("extsys_sysobjs.delete");

//Связи с объектами внешних систем
Route::get('/objextids/create/{sysobjid}/{objid}', "ObjextidController@create")->name('objextids.create');
Route::get('/objextids/edit/{id}', 'ObjextidController@edit')->name('objextids.edit');
Route::match(array('POST', 'PUT'), '/objextids/update/{id}', "ObjextidController@update")->name('objextids.update');
Route::put('/objextids/delete/{id}', "ObjextidController@destroy")->name("objextids.delete");


//читатели записи
Route::get('/readers/create/{sysobjid}/{objid}', "ObjReaderController@create")->name('obj_readers.create');
Route::get('/readers/edit/{id}', 'ObjReaderController@edit')->name('obj_readers.edit');
Route::match(array('POST', 'PUT'), '/readers/update/{id}', "ObjReaderController@update")->name('obj_readers.update');
Route::put('/readers/delete/{id}', "ObjReaderController@destroy")->name("obj_readers.delete");

//персонал, связанный с информационным объектом
Route::get('/obj_staffs/create/{sysobjid}/{objid}', "ObjStaffController@create")->name('obj_staffs.create');
Route::get('/obj_staffs/{id}/edit', "ObjStaffController@edit")->name('obj_staffs.edit');
Route::match(array('POST', 'PUT'), 'obj_staffs/{id}', "ObjStaffController@update")->name('obj_staffs.update');
Route::put('/obj_staffs/{id}/delete', "ObjStaffController@destroy")->name("obj_staffs.delete");

//организации, связанные с информационным объектом
Route::get('/obj_orgs/create/{sysobjid}/{objid}', "ObjOrgController@create")->name('obj_orgs.create');
Route::get('/obj_orgs/{id}/edit', "ObjOrgController@edit")->name('obj_orgs.edit');
Route::match(array('POST', 'PUT'), 'obj_orgs/{id}', "ObjOrgController@update")->name('obj_orgs.update');
Route::put('/obj_orgs/{id}/delete', "ObjOrgController@destroy")->name("obj_orgs.delete");

Route::get('/contract_orgs/create/{contractid}', "ContractOrgController@create")->name('contract_orgs.create');
Route::get('/contract_orgs/{id}/edit', "ContractOrgController@edit")->name('contract_orgs.edit');
Route::match(array('POST', 'PUT'), 'contract_orgs/{id}', "ContractOrgController@update")->name('contract_orgs.update');
Route::put('/contract_orgs/{id}/delete', "ContractOrgController@destroy")->name("contract_orgs.delete");

//альтернативные названия для объекта
Route::get('/altnames/create/{sysobjid}/{objid}', "ObjNameController@create")->name('obj_names.create');
Route::get('/altnames/edit/{id}', 'ObjNameController@edit')->name('obj_names.edit');
Route::match(array('POST', 'PUT'), '/altnames/update/{id}', "ObjNameController@update")->name('obj_names.update');
Route::put('/altnames/delete/{id}', "ObjNameController@destroy")->name("obj_names.delete");

//признаки/особенности объектов ИС
Route::get('/objflags/create/{sysobjid}/{objid}', "ObjflagController@create")->name('objflags.create');
Route::get('/objflags/edit/{id}', 'ObjflagController@edit')->name('objflags.edit');
Route::match(array('POST', 'PUT'), '/objflags/update/{id}', "ObjflagController@update")->name('objflags.update');
Route::put('/objflags/delete/{id}', "ObjflagController@destroy")->name("objflags.delete");

//контактные данные объектов ИС
Route::get('/obj_contacts/create/{sysobjid}/{objid}', "ObjContactController@create")->name('obj_contacts.create');
Route::get('/obj_contacts/edit/{id}', 'ObjContactController@edit')->name('obj_contacts.edit');
Route::match(array('POST', 'PUT'), '/obj_contacts/update/{id}', "ObjContactController@update")->name('obj_contacts.update');
Route::put('/obj_contacts/delete/{id}', "ObjContactController@destroy")->name("obj_contacts.delete");

//адреса объектов ИС
Route::get('/obj_addresses/create/{sysobjid}/{objid}', "ObjAddressController@create")->name('obj_addresses.create');
Route::get('/obj_addresses/edit/{id}', 'ObjAddressController@edit')->name('obj_addresses.edit');
Route::match(array('POST', 'PUT'), '/obj_addresses/update/{id}', "ObjAddressController@update")->name('obj_addresses.update');
Route::put('/obj_addresses/delete/{id}', "ObjAddressController@destroy")->name("obj_addresses.delete");
Route::get('/obj_addresses/print_envelope/{id}', 'ObjAddressController@print_envelope')->name('obj_addresses.print_envelope');

Route::match(array('GET', 'POST'), '/envelopes/prep', "EnvelopeController@prep")->name("envelopes.prep");
Route::match(array('POST', 'PUT'), 'envelopes/prep_save', "EnvelopeController@prep_save")->name('envelopes.prep_save');
Route::get('/envelopes/edit/{id}', 'EnvelopeController@edit')->name('envelopes.edit');
Route::get('/envelopes/print_envelopes', 'EnvelopeController@print_envelopes')->name('envelopes.print_envelopes');


//Список для выбора через отдельное окно
Route::match(array('GET', 'POST'), '/orgs/list', "orgController@list")->name("orgs.list");
//Route::match(array('GET', 'POST','PUT'), '/orgs/list', "orgController@list")->name("orgs.list");

Route::match(array('POST'), 'orgs/getshortinfo', 'orgController@getshortinfo')->name('orgs.getshortinfo');

//Автодополнение названий организаций
Route::get('orgs/autocomplete/search', 'AutoCompleteController@OrgsAutocompleteSearch');

//Автодополнение фИО сотрудников организаций
Route::get('orgstaff/autocomplete/search', 'AutoCompleteController@OrgstaffAutocompleteSearch');
Route::get('users/autocomplete/search', 'AutoCompleteController@UsersAutocompleteSearch');

//Автодополнение позиций заказа
Route::get('/refitems/autocomplete/search', 'AutoCompleteController@RefItemsAutocompleteSearch');
Route::get('/refitems/ac/wrhdoclst', 'AutoCompleteController@refitemsAC_wrhdoclst');


//Контрагенты
Route::match(array('GET', 'POST'), 'orgs/search', "orgController@search")->name("orgs.search");
Route::match(array('GET', 'POST'), 'orgs', "orgController@index")->name("orgs.index");

Route::get('/orgs/{org}/show', 'orgController@show')->middleware('auth');
Route::get('orgs/{id}/edit', "orgController@edit")->name('orgs.edit');
Route::match(array('POST', 'PUT'), 'orgs/{id}', "orgController@update")->name('orgs.update');
Route::get('orgs/create/{owngrp}', "orgController@create")->name('orgs.create');
Route::put('org/{id}/delete', "orgController@destroy")->name("org.delete");
Route::get('orgs/{orgid}/updfrm_zachestnyibiznes', "orgController@updfrm_zachestnyibiznes")->name("orgs.updfrm_zachestnyibiznes");
Route::get('orgs/{orgid}/impfrm_egrul', "orgController@impfrm_egrul")->name("orgs.impfrm_egrul");

//загрузка новых записей об организациях из файла в формате XLS
Route::get('orgs/load/xls', "orgController@load")->name('orgs.load');
Route::put('orgs/import/xls', "orgController@import")->name('orgs.import');

Route::get('/orgs/projects/params/', 'orgController@listprojects');


Route::get('orgs/org_users/{orgid}', "orgController@org_users")->name('org_users.index');
Route::get('orgs/org_staff/{orgid}', "orgController@org_staff")->name('org_staff.index');
Route::get('orgs/org_curators/{orgid}', "orgController@org_curators")->name('org_curators.index');
Route::get('orgs/org_supoffers/{orgid}', "orgController@org_supoffers")->name('org_supoffers.index');
Route::get('orgs/org_extids/{orgid}', "orgController@org_extids")->name('org_extids.index');
Route::get('orgs/org_ri_prices/{orgid}', "orgController@org_ri_prices")->name('org_ri_prices.index');

//Кураторы клиента (org_curators)
//Если нет контроллера не надо писать маршрут
Route::get('org_curator/{orgid}/create', "OrgCuratorController@create")->name('org_curator.create');
Route::get('org_curator/{id}/edit', "OrgCuratorController@edit")->name('org_curator.edit');
Route::match(array('POST', 'PUT'), 'org_curator/{id}', "OrgCuratorController@update")->name('org_curator.update');
Route::put('org_curator/{id}/delete', "OrgCuratorController@destroy")->name("org_curator.delete");


Route::get('orgs/org_groups/{orgid}', "orgController@org_groups_edit")->name('org_groups.edit');
Route::match(array('POST', 'PUT'), 'orgs/org_groups//{orgid}', "orgController@org_groups_update")
    ->name('org_groups.update');

Route::get('/org_saldos/create/{orgid}/{ownorgid}', "OrgSaldoController@create")->name('org_saldos.create');
Route::get('/org_saldos/edit/{id}', 'OrgSaldoController@edit')->name('org_saldos.edit');
Route::match(array('POST', 'PUT'), '/org_saldos/update/{id}', "OrgSaldoController@update")->name('org_saldos.update');
Route::put('/org_saldos/delete/{id}', "OrgSaldoController@destroy")->name("org_saldos.delete");

//расчетные счета организации
//Route::get('org_acnts/{id}/edit', "OrgAcntController@edit")->name('org_acnts.edit');
//Route::get('org_acnts/{orgid}/create', "OrgAcntController@create")->name('org_acnts.create');
//Route::match(array('POST', 'PUT'), 'org_acnts/{id}', "OrgAcntController@update")->name('org_acnts.update');
//Route::put('org_acnts/{id}/delete', "OrgAcntController@destroy")->name("org_acnts.delete");
//
//Route::get('/org/acnts/params/', 'OrgAcntController@listActive');

//названия организации
Route::get('org_names/{id}/edit', "OrgNameController@edit")->name('org_names.edit');
Route::get('org_names/{orgid}/create', "OrgNameController@create")->name('org_names.create');
Route::match(array('POST', 'PUT'), 'org_names/{id}', "OrgNameController@update")->name('org_names.update');
Route::put('org_names/{id}/delete', "OrgNameController@destroy")->name("org_names.delete");


//подразделения организации
//Route::get('orgdeps/{id}/edit', "OrgdepController@edit")->name('orgdeps.edit');
//Route::get('orgdeps/{orgid}/create', "OrgdepController@create")->name('orgdeps.create');
//Route::match(array('POST', 'PUT'), 'orgdeps/{id}', "OrgdepController@update")->name('orgdeps.update');
//Route::put('orgdeps/{id}/delete', "OrgdepController@destroy")->name("orgdeps.delete");

//должности организации / штатное расписание
Route::get('orgposts/{orgid}/create', "OrgpostController@create")->name('orgposts.create');
Route::get('orgposts/{id}/edit', "OrgpostController@edit")->name('orgposts.edit');
Route::match(array('POST', 'PUT'), 'orgposts/{id}', "OrgpostController@update")->name('orgposts.update');
Route::put('orgposts/{id}/delete', "OrgpostController@destroy")->name("orgposts.delete");


//Места(локации), связанные с организациями
Route::get('org_places/{id}/edit', "OrgPlaceController@edit")->name('org_places.edit');
Route::get('org_places/{orgid}/create', "OrgPlaceController@create")->name('org_places.create');
Route::match(array('POST', 'PUT'), 'org_places/{id}', "OrgPlaceController@update")->name('org_places.update');
Route::put('org_places/{id}/delete', "OrgPlaceController@destroy")->name("org_places.delete");
Route::get('/api/org_places/for_ac/', 'OrgPlaceController@list_for_ac');
Route::get('/api/org_places/for_/', 'OrgPlaceController@list_for');

//Просто места в городе/регионе
Route::get('/api/places/for_ac/', 'PlaceController@list_for_ac');


//Персонал
//загрузка новых записей о сотрудниках из файла в формате XLS
Route::get('orgstaff/load/xls', "orgstaffController@load")->name('orgstaff.load');
Route::put('orgstaff/import/xls', "orgstaffController@import")->name('orgstaff.import');

Route::match(array('GET', 'POST'), 'orgstaff', "orgstaffController@index")->name("orgstaff.index");
Route::get('orgstaff/{id}/show', "orgstaffController@show")->name('orgstaff.show');
Route::get('orgstaff/{id}/edit', "orgstaffController@edit")->name('orgstaff.edit');
//Route::get('orgstaff/create',"orgstaffController@create")->name('orgstaff.create');
Route::get('orgstaff/{orgid}/create', "orgstaffController@create")->name('orgstaff.create');
Route::match(array('POST', 'PUT'), 'orgstaff/{id}', "orgstaffController@update")->name('orgstaff.update');
Route::put('orgstaff/{id}/delete', "orgstaffController@destroy")->name("orgstaff.del");
Route::match(array('GET', 'POST'), 'orgstaff/search', "orgstaffController@search")->name("orgstaff.search");

//Список для выбора через отдельное окно
Route::match(array('GET', 'POST'), '/orgstaffs/list', "orgstaffController@list")->name("orgstaffs.list");
Route::match(array('get', 'POST'), 'orgstaffs/getshortinfo', 'orgstaffController@getshortinfo')->name('orgstaffs.getshortinfo');

//Приказы по персоналу
//Route::get('stforders/create/{staffid}/', "StforderController@create")->name('stforders.create');
//Route::get('stforders/{id}/', 'StforderController@edit')->name('stforders.edit');
//Route::match(array('POST', 'PUT'), 'stforders/{id}', "StforderController@update")->name('stforders.update');
//Route::put('stforders/{id}/delete', "StforderController@destroy")->name("stforders.delete");
//Route::get('stforders/{id}/print', 'StforderController@print')->name('stforders.print');

//История должностей сотрудника
//Route::get('staff_posts/create/{staffid}/', "StaffPostController@create")->name('staff_posts.create');
//Route::get('staff_posts/{id}/', 'StaffPostController@edit')->name('staff_posts.edit');
//Route::match(array('POST', 'PUT'), 'staff_posts/{id}', "StaffPostController@update")->name('staff_posts.update');
//Route::put('staff_posts/{id}/delete', "StaffPostController@destroy")->name("staff_posts.delete");


//Архив документов
//Route::match(array('GET', 'POST'), 'documents', "DocumentController@index")->name("documents.index");
//Route::get('documents/create/{owngrp}', "DocumentController@create")->name('documents.create');
//Route::get('documents/{id}/edit', "DocumentController@edit")->name('documents.edit');
//Route::match(array('POST', 'PUT'), 'documents/{id}', "DocumentController@update")->name('documents.update');
//Route::put('documents/{id}/delete', "DocumentController@destroy")->name("documents.delete");
//Route::get('documents/{id}/make_template', "DocumentController@make_template")->name('documents.make_template');
//
//Route::match(array('GET', 'POST'), 'documents/{id}/link', "DocumentController@link")->name("documents.link");
//Route::match(array('POST', 'PUT'), 'documents/{id}/link_save', "DocumentController@link_save")->name('documents.link_save');
//
//Route::match(array('POST', 'GET'), '/reports/rep/37', "DocumentReportController@rep37")->name('reports.rep37');
//Route::match(array('POST', 'GET'), '/reports/rep/44', "DocumentReportController@rep44")->name('reports.rep44');

//Договоры
Route::match(array('GET', 'POST'), 'contracts', "ContractController@index")->name("contracts.index");

Route::get('contracts/{id}/edit', "ContractController@edit")->name('contracts.edit');
Route::match(array('POST', 'PUT'), 'contracts/{id}', "ContractController@update")->name('contracts.update');
Route::get('contracts/create/{owngrp}', "ContractController@create")->name('contracts.create');
Route::put('contracts/{id}/delete', "ContractController@destroy")->name("contracts.delete");

Route::get('/contracts/fill_regnums/{ownorgid}', "ContractController@fill_regnums")->name("contracts.fill_regnums");
Route::get('/contracts/fill_regnum/params/', 'ContractController@fill_regnum');

Route::get('contracts/{id}/make_template', "ContractController@make_template")->name('contracts.make_template');
//
////уведомление о необходимости прочитать опубликованный документ
//Route::get('contracts/{id}/notify/1', 'ContractController@notify_mustreaders')->name('contracts.notify_mustreaders');
////Автозаполнение
//Route::get('/api/contracts/autocomplete/', 'AutoCompleteController@ContractsAutocompleteSearch');

//Route::match(array('POST', 'GET'), '/contracts/rep/23', "ContractController@rep23")->name('reports.rep23');

//Контрактные цены
Route::get('contract_prices/{id}/edit', "ContractPriceController@edit")->name('contract_prices.edit');
Route::match(array('POST', 'PUT'), 'contract_prices/{id}', "ContractPriceController@update")->name('contract_prices.update');
Route::get('contract_prices/create/{sysobjid}', "ContractPriceController@create")->name('contract_prices.create');
Route::put('contract_prices/{id}/delete', "ContractPriceController@destroy")->name("contract_prices.delete");
//
////Контракт - планы работ
//Route::get('contract_workplans/create/{contractid}', "ContractWorkplanController@create")->name('contract_workplans.create');
//Route::get('contract_workplans/{id}/edit', "ContractWorkplanController@edit")->name('contract_workplans.edit');
//Route::match(array('POST', 'PUT'), 'contract_workplans/{id}', "ContractWorkplanController@update")->name('contract_workplans.update');
//Route::put('contract_workplans/{id}/delete', "ContractWorkplanController@destroy")->name("contract_workplans.delete");
//
////Контракт - исполнение
//Route::get('contract_exes/create/{contractid}/{buildopertypeid}', "ContractExeController@create")->name('contract_exes.create');
//Route::get('contract_exes/{id}/edit', "ContractExeController@edit")->name('contract_exes.edit');
//Route::match(array('POST', 'PUT'), 'contract_exes/{id}', "ContractExeController@update")->name('contract_exes.update');
//Route::put('contract_exes/{id}/delete', "ContractExeController@destroy")->name("contract_exes.delete");
//
////печатные формы
//Route::get('contract_exes/{id}/print/1', 'ContractExeController@print_1')->name('contract_exes.print_1');
//Route::match(array('POST', 'GET'), '/contract_exes/rep/22', "ContractExeController@rep22")->name('reports.rep22');
//Route::match(array('POST', 'GET'), '/contract_exes/rep/27', "ContractExeController@rep27")->name('reports.rep27');
//
//Контракт - согласование
Route::get('contract_reviews/create/{contractid}', "ContractReviewController@create")->name('contract_reviews.create');
Route::get('contract_reviews/{id}/edit', "ContractReviewController@edit")->name('contract_reviews.edit');
Route::match(array('POST', 'PUT'), 'contract_reviews/{id}', "ContractReviewController@update")->name('contract_reviews.update');
Route::put('contract_reviews/{id}/delete', "ContractReviewController@destroy")->name("contract_reviews.delete");
Route::get('contract_reviews/{id}/print/1', 'ContractReviewController@print_1')->name('contract_reviews.print_1');
//
////Контракт - согласование - участник
//Route::get('contrrev_users/create/{contrrevid}', "ContrrevUserController@create")->name('contrrev_users.create');
//Route::get('contrrev_users/{id}/edit', "ContrrevUserController@edit")->name('contrrev_users.edit');
//Route::match(array('POST', 'PUT'), 'contrrev_users/{id}', "ContrrevUserController@update")->name('contrrev_users.update');
//Route::put('contrrev_users/{id}/delete', "ContrrevUserController@destroy")->name("contrrev_users.delete");
//
////Проекты
//Route::match(array('GET', 'POST'), 'projects/search', "ProjectController@search")->name("projects.search");
//Route::match(array('GET', 'POST'), '/projects', "ProjectController@index")->name('projects.index');
//Route::get('/projects/sort/{field}', 'ProjectController@index_sort')->name('projects.sort');
//Route::match(array('GET', 'POST'), '/orders/search/org/{orgid}', "ProjectController@searchfororg")
//    ->name("orders.for_org");
//
//Route::get('projects/create', "ProjectController@create")->name('projects.create');
//Route::get('projects/{id}', 'ProjectController@edit')->name('projects.edit');
//Route::match(array('POST', 'PUT'), 'projects/{id}', "ProjectController@update")->name('projects.update');
//Route::put('projects/{id}/delete', "ProjectController@destroy")->name("projects.delete");
//Route::put('projects/{id}/admindelete', "ProjectController@admindelete")->name("projects.admindelete");
//
//Route::get('projects/proj_risks/{projid}', "ProjectController@proj_risks")->name('proj_risks.index');
//Route::get('projects/proj_milestones/{projid}', "ProjectController@proj_milestones")->name('proj_milestones.index');
//Route::get('projects/proj_estdocs/{projid}', "ProjectController@proj_estdocs")->name('proj_estdocs.index');
//Route::get('projects/proj_budgetitems/{projid}', "ProjectController@proj_budgetitems")->name('proj_budgetitems.index');
//Route::get('projects/{projid}/load', 'testLoadController@load')->name('projects.estdoc_load');
//

// КУРСИА - Учет рабочего времени спецтехники --------------------------------------------------------------------------
//Route::match(array('GET', 'POST'), '/cursias/', "CursiaController@index")->name('cursias.index');
//Route::get('cursias/create', "CursiaController@create")->name('cursias.create');
//Route::get('cursias/{id}', 'CursiaController@edit')->name('cursias.edit');
//Route::match(array('POST', 'PUT'), 'cursias/{id}', "CursiaController@update")->name('cursias.update');
//Route::put('cursias/{id}/delete', "CursiaController@destroy")->name("cursias.delete");
//Route::get('cursias/{id}/make_template', "CursiaController@make_template")->name('cursias.make_template');

// driver_works - Учет рабочего времени водителей----------------------------------------------------------------------
Route::match(array('GET', 'POST'), '/driver_works/', "DriverWorkController@index")->name('driver_works.index');
Route::get('driver_works/create', "DriverWorkController@create")->name('driver_works.create');
Route::get('driver_works/{id}', 'DriverWorkController@edit')->name('driver_works.edit');
Route::match(array('POST', 'PUT'), 'driver_works/{id}', "DriverWorkController@update")->name('driver_works.update');
Route::get('driver_works/{id}/delete', "DriverWorkController@destroy")->name("driver_works.delete");
Route::get('driver_works/{id}/make_template', "DriverWorkController@make_template")->name('driver_works.make_template');

// mchn_raids - Учет рейсов спецтехники --------------------------------------------------------------------------
Route::get('mchn_raids/rfr_all_mchnraids', "MchnRaidController@rfr_all_mchnraids");
Route::match(array('GET', 'POST'), '/mchn_raids/', "MchnRaidController@index")->name('mchn_raids.index');
Route::get('mchn_raids/create/{dw_id}', "MchnRaidController@create")->name('mchn_raids.create');
Route::get('mchn_raids/{id}', 'MchnRaidController@edit')->name('mchn_raids.edit');
Route::match(array('POST', 'PUT'), 'mchn_raids/{id}', "MchnRaidController@update")->name('mchn_raids.update');
Route::put('mchn_raids/{id}/delete', "MchnRaidController@destroy")->name("mchn_raids.delete");
Route::put('mchn_raids/{id}/admindelete', "MchnRaidController@admindelete")->name("mchn_raids.admindelete");
Route::get('mchn_raids/{id}/make_template', "MchnRaidController@make_template")->name('mchn_raids.make_template');
Route::get('mchn_raids/{id}/clone', "MchnRaidController@clone")->name('mchn_raids.clone');

//Операции по mchn_raids
Route::get('mr_opers/create/{mr_id}/', "MrOperController@create")->name('mr_opers.create');
Route::get('mr_opers/{id}/', 'MrOperController@edit')->name('mr_opers.edit');
Route::match(array('POST', 'PUT'), 'mr_opers/{id}', "MrOperController@update")->name('mr_opers.update');
Route::get('mr_opers/{id}/delete', "MrOperController@destroy")->name("mr_opers.delete");


// dw_breaks - Простои в работе водителя -------------------------------------------------------------------------
Route::get('dw_breaks/create/{dw_id}', "DwBreakController@create")->name('dw_breaks.create');
Route::get('dw_breaks/{id}', 'DwBreakController@edit')->name('dw_breaks.edit');
Route::match(array('POST', 'PUT'), 'dw_breaks/{id}', "DwBreakController@update")->name('dw_breaks.update');
Route::get('dw_breaks/{id}/delete', "DwBreakController@destroy")->name("dw_breaks.delete");

//// Отчет о работе ------------------------------------------------------------------------------------------------------
//Route::match(array('GET', 'POST'), '/wrkreps/', "WrkrepController@index")->name('wrkreps.index');
//Route::get('wrkreps/create', "WrkrepController@create")->name('wrkreps.create');
//Route::get('wrkreps/{id}', 'WrkrepController@edit')->name('wrkreps.edit');
//Route::match(array('POST', 'PUT'), 'wrkreps/{id}', "WrkrepController@update")->name('wrkreps.update');
//Route::put('wrkreps/{id}/delete', "WrkrepController@destroy")->name("wrkreps.delete");
//
//// ... механизмов
//Route::get('wrkrep_machines/create/{docid}/', "WrkrepMachineController@create")->name('wrkrep_machines.create');
//Route::get('wrkrep_machines/{id}/', 'WrkrepMachineController@edit')->name('wrkrep_machines.edit');
//Route::match(array('POST', 'PUT'), 'wrkrep_machines/{id}', "WrkrepMachineController@update")->name('wrkrep_machines.update');
//Route::put('wrkrep_machines/{id}/delete', "WrkrepMachineController@destroy")->name("wrkrep_machines.delete");
////----------------------------------------------------------------------------------------------------------------------


//Типы спецтехники
Route::match(array('GET', 'POST'), 'mchntypes/search', "MchntypeController@search")->name("mchntypes.search");
Route::match(array('GET', 'POST'), '/mchntypes', "MchntypeController@index")->name('mchntypes.index');
Route::get('/mchntypes/sort/{field}', 'MchntypeController@index_sort')->name('mchntypes.sort');

Route::get('mchntypes/create', "MchntypeController@create")->name('mchntypes.create');
Route::get('mchntypes/{id}', 'MchntypeController@edit')->name('mchntypes.edit');
Route::match(array('POST', 'PUT'), 'mchntypes/{id}', "MchntypeController@update")->name('mchntypes.update');
Route::put('mchntypes/{id}/delete', "MchntypeController@destroy")->name("mchntypes.delete");

//Спец-техника
//Route::match(array('GET', 'POST'), 'machines/search', "MachineController@search")->name("machines.search");
Route::match(array('GET', 'POST'), '/machines', "MachineController@index")->name('machines.index');
Route::get('/machines/sort/{field}', 'MachineController@index_sort')->name('machines.sort');


//Спецтехника ---------------------------------------------------------------------------------------------------------
//загрузка новых записей о технике из файла в формате XLS
Route::get('machines/load/xls', "MachineController@load")->name('machines.load');
Route::put('machines/import/xls', "MachineController@import")->name('machines.import');

Route::get('machines/create', "MachineController@create")->name('machines.create');
Route::get('machines/{id}', 'MachineController@edit')->name('machines.edit');
Route::match(array('POST', 'PUT'), 'machines/{id}', "MachineController@update")->name('machines.update');
Route::put('machines/{id}/delete', "MachineController@destroy")->name("machines.delete");
Route::put('machines/{id}/admindelete', "MachineController@admindelete")->name("machines.admindelete");

Route::get('machines/mchn_rqsts/{machineid}', "MachineController@mchn_rqsts")->name('machine_rqsts.index');

//Типы операций / режимы эксплуатации спецтехники ----------------------------------------------------------------------
Route::get('mchn_opertypes/create/{machineid}/', "MchnOpertypeController@create")->name('mchn_opertypes.create');
Route::get('mchn_opertypes/{id}/', 'MchnOpertypeController@edit')->name('mchn_opertypes.edit');
Route::match(array('POST', 'PUT'), 'mchn_opertypes/{id}', "MchnOpertypeController@update")->name('mchn_opertypes.update');
Route::put('mchn_opertypes/{id}/delete', "MchnOpertypeController@destroy")->name("mchn_opertypes.delete");
//----------------------------------------------------------------------------------------------------------------------

// Расценки на режим эксплуатации техники ------------------------------------------------------------------------------
//Route::get('mot_prices/{id}/edit', "MotPriceController@edit")->name('mot_prices.edit');
//Route::match(array('POST', 'PUT'), 'mot_prices/{id}', "MotPriceController@update")->name('mot_prices.update');
//Route::get('mot_prices/create/{mot_id}', "MotPriceController@create")->name('mot_prices.create');
//Route::put('mot_prices/{id}/delete', "MotPriceController@destroy")->name("mot_prices.delete");
//----------------------------------------------------------------------------------------------------------------------


// отчеты по спец-технике
Route::match(array('POST', 'GET'), '/mchnrqsts/rep/1', "MchnReportController@rep01")->name('reports.rep1');
Route::match(array('POST', 'GET'), '/mchnrqsts/rep/02', "MchnReportController@rep02_esm3")->name('mchnrqsts.rep02_esm3');
Route::match(array('POST', 'GET'), '/mchnrqsts/rep/2', "MchnReportController@rep02_esm3")->name('reports.rep2');
Route::match(array('POST', 'GET'), '/mchnrqsts/rep/3', "MchnReportController@rep03")->name('reports.rep3');
Route::match(array('POST', 'GET'), '/mchnrqsts/rep/6', "MchnReportController@rep06")->name('reports.rep6');
Route::match(array('POST', 'GET'), '/mchnrqsts/rep/7', "MchnReportController@rep07")->name('reports.rep7');
Route::match(array('POST', 'GET'), '/mchnrqsts/rep/8', "MchnReportController@rep08")->name('reports.rep8');
Route::match(array('POST', 'GET'), '/mchnrqsts/rep/9', "MchnReportController@rep09")->name('reports.rep9');
Route::match(array('POST', 'GET'), '/mchnrqsts/rep/26', "MchnReportController@rep26")->name('reports.rep26');


////организации, управляющие техникой
//Route::get('mchncontrorgs/create', "MchncontrorgController@create")->name('mchncontrorgs.create');
//Route::get('mchncontrorgs/edit/{id}', 'MchncontrorgController@edit')->name('mchncontrorgs.edit');
//Route::match(array('POST', 'PUT'), 'mchncontrorgs/{id}', "MchncontrorgController@update")->name('mchncontrorgs.update');
//Route::put('mchncontrorgs/{id}/delete', "MchncontrorgController@destroy")->name("mchncontrorgs.delete");


//Фото для продуктов
Route::get('ri_img/{refitmid}', 'RiImageController@create')->name('ri_images.load');
Route::post('ri_img', 'RiImageController@store')->name('ri_image.upload');
Route::get('ri_img/delete/{id}', 'RiImageController@destroy')->name('ri_img.destroy');

//Файлы для объектов
Route::get('objfiles/{sysobjid}/{objid}/load', 'ObjfileController@load')->name('objfiles.load');
Route::post('objfiles/store', 'ObjfileController@store')->name('objfiles.upload');

Route::get('/objfiles/{sysobjid}/{objid}/create', "ObjfileController@create")->name('objfiles.create');
Route::get('objfiles/{id}', 'ObjfileController@edit')->name('objfiles.edit');
Route::match(array('POST', 'PUT'), 'objfiles/{id}', "ObjfileController@update")->name('objfiles.update');
Route::put('objfiles/delete/{id}', 'ObjfileController@destroy')->name('objfiles.delete');
Route::get('objfiles/destroy/{id}', 'ObjfileController@destroyfile')->name('objfiles.destroy');
Route::get('objfiles/make_document/{id}', 'ObjfileController@make_document')->name('objfiles.make_document');

Route::get('/storage/files/{id}/getbyid', 'ObjfileController@getbyid')->name('objfiles.getbyid');
Route::get('/storage/files/{sysobjid}/{objid}/{fn}', 'ObjfileController@get')->name('objfiles.get');
Route::get('/storage/files/{sysobjid}/{objid}/{fn}/get', 'ObjfileController@get')->name('objfiles.get');


//itmtypes - Справочник типов(категорий) номенклатуры
Route::get('/itmtypes/create/{parid}', "ItmTypeController@create")->name('itmtypes.create');
Route::match(array('GET', 'POST'), '/itmtypes', "ItmTypeController@index")->name("itmtypes.index");
Route::get('itmtypes/{id}', 'ItmTypeController@edit')->name('itmtypes.edit');
Route::match(array('POST', 'PUT'), 'itmtypes/{id}', "ItmTypeController@update")
    ->name('itmtypes.update');
Route::put('itmtypes/{id}/delete', "ItmTypeController@destroy")->name("itmtypes.delete");
Route::put('itmtypes/{id}/admindelete', "ItmTypeController@admindelete")->name("itmtypes.admindelete");

//Справочник Единиц измерения
Route::match(array('GET', 'POST'), '/unittypes', 'UnittypeController@index')->name('unittypes.index');
Route::get('/unittypes/create', 'UnittypeController@create')->name('unittypes.create');
Route::get('/unittypes/{id}/edit', "UnittypeController@edit")->name('unittypes.edit');
Route::match(array('POST', 'PUT'), 'unittypes/update/{id}', "UnittypeController@update")->name('unittypes.update');
Route::put('/unittypes/{id}/delete', "UnittypeController@destroy")->name("unittypes.delete");
Route::get('/unittypes/info/params/', 'UnittypeController@info_params');

// ------------------------------------------
//Справочник RefItems - Прайслист для внутреннего использования
Route::match(array('GET', 'POST'), 'refitems', 'refItemController@index')->name('refitems.index');
Route::get('refitems/create', 'refItemController@create')->name('refitems.create');
Route::match(array('GET', 'POST'), '/refitems/search', "refItemController@search")
    ->name("refitems.search");
Route::match(array('GET', 'POST'), '/refitems/list', "refItemController@list")
    ->name("refitems.list");
Route::match(array('GET', 'POST'), '/refitems/listgoods', "refItemController@listgoods")
    ->name("refitems.listgoods");
Route::match(array('POST'), 'refitems/getshortinfo', 'refItemController@getshortinfo')
    ->name('refitems.getshortinfo');

Route::get('refitems/{id}/edit', 'refItemController@edit')->name('refitems.edit');
Route::match(array('POST', 'PUT'), 'refitems/{id}', "refItemController@update")
    ->name('refitems.update');
Route::put('refitems/{id}/delete', "refItemController@destroy")->name("refitems.del");
Route::put('refitems/{id}/admindelete', "refItemController@admindelete")->name("refitems.admindelete");

Route::get('refitems/{orditemid}/assemble', 'refItemController@assembleItem')->name('refitems.assembleitem');

Route::get('refitems/fill_ordr', 'refItemController@fillOrdr')->name('refitems.fillordr');

Route::get('refitems/{srcid}/{tgtid}/join', 'refItemController@join')->name('refitems.join');

//Цены поставщиков товаров (идем от поставщика)
Route::match(array('GET', 'POST'), '/ri_sup_prices', "RiSupPriceController@index")->name("ri_sup_prices.index");
Route::get('ri_sup_prices/{id}/edit', "RiSupPriceController@edit")->name('ri_sup_prices.edit');
Route::get('ri_sup_prices/{orgid}/create', "RiSupPriceController@create")->name('ri_sup_prices.create');
Route::match(array('POST', 'PUT'), 'ri_sup_prices/{id}', "RiSupPriceController@update")->name('ri_sup_prices.update');
Route::get('ri_sup_prices/{id}/delete', "RiSupPriceController@destroy")->name("ri_sup_prices.delete");

//Составы комплектующих для производства (Рецептуры)
Route::match(array('GET', 'POST'), '/ri_compounds', "RiCompoundController@index")->name("ri_compounds.index");
Route::get('ri_compounds/{id}/edit', "RiCompoundController@edit")->name('ri_compounds.edit');
Route::get('ri_compounds/create', "RiCompoundController@create")->name('ri_compounds.create');
Route::match(array('POST', 'PUT'), 'ri_compounds/{id}', "RiCompoundController@update")->name('ri_compounds.update');
Route::put('ri_compounds/{id}/delete', "RiCompoundController@destroy")->name("ri_compounds.delete");
Route::put('ri_compounds/{id}/sign', "RiCompoundController@sign")->name("ri_compounds.sign");
Route::put('ri_compounds/{id}/unsign', "RiCompoundController@unsign")->name("ri_compounds.unsign");
Route::put('ri_compounds/{id}/set_active', "RiCompoundController@set_active")->name("ri_compounds.set_active");
Route::put('ri_compounds/{id}/trg_active', "RiCompoundController@trg_active")->name("ri_compounds.trg_active");
Route::get('/api/ri_compounds/for_ac/', 'RiCompoundController@list_for_ac');

//Позиции документа состава изготавливаемого изделия
Route::get('ri_cmpnd_items/{docid}/create', 'RiCmpndItemController@create')->name('ri_cmpnd_items.create');
Route::get('ri_cmpnd_items/{id}/edit', "RiCmpndItemController@edit")->name('ri_cmpnd_items.edit');
Route::match(array('POST', 'PUT'), 'ri_cmpnd_items/{id}', "RiCmpndItemController@update")->name('ri_cmpnd_items.update');
Route::put('ri_cmpnd_items/{id}/delete', "RiCmpndItemController@destroy")->name("ri_cmpnd_items.delete");


//Единицы измерения для позиции справочника номенклатуры
Route::get('ri_units/create/{refitmid}', "RiUnitController@create")->name('ri_units.create');
Route::get('ri_units/edit/{id}', 'RiUnitController@edit')->name('ri_units.edit');
Route::match(array('POST', 'PUT'), 'ri_units.edit/{id}', "RiUnitController@update")->name('ri_units.update');
Route::put('ri_units.edit/{id}/delete', "RiUnitController@destroy")->name("ri_units.delete");

//Отчеты
Route::get('reports/create', "ReportController@create")->name('reports.create');
Route::match(array('GET', 'POST'), '/admin/reports', "ReportController@index")->name("reports.index");
Route::get('reports/edit/{id}', 'ReportController@edit')->name('reports.edit');
Route::match(array('POST', 'PUT'), 'reports.edit/{id}', "ReportController@update")->name('reports.update');
Route::put('reports/del/{id}/delete', "ReportController@destroy")->name("reports.delete");

Route::match(array('GET', 'POST'), '/reports', "ReportController@pub_index")->name("reports.pub_index");

Route::match(array('GET', 'POST'), '/salary', "ReportController@pub_index")->name("salary.tab_index");

//payrolltypes - Схемы расчета заработной платы (ЗП)
Route::match(array('GET', 'POST'), '/payrolltypes', "PayrolltypeController@index")->name("payrolltypes.index");
Route::get('/payrolltypes/create/{parid}', "PayrolltypeController@create")->name('payrolltypes.create');
Route::get('payrolltypes/{id}', 'PayrolltypeController@edit')->name('payrolltypes.edit');
Route::match(array('POST', 'PUT'), 'payrolltypes/{id}', "PayrolltypeController@update")
    ->name('payrolltypes.update');
Route::put('payrolltypes/{id}/delete', "PayrolltypeController@destroy")->name("payrolltypes.delete");

//salary_rate_sets - Группы ставок для схем расчета заработной платы (ЗП)
//Route::match(array('GET', 'POST'), '/salary_rate_sets', "SalaryRateSetController@index")->name("salary_rate_sets.index");
Route::get('/salary_rate_sets/create/{payrolltypeid}', "SalaryRateSetController@create")->name('salary_rate_sets.create');
Route::get('salary_rate_sets/{id}', 'SalaryRateSetController@edit')->name('salary_rate_sets.edit');
Route::match(array('POST', 'PUT'), 'salary_rate_sets/{id}', "SalaryRateSetController@update")
    ->name('salary_rate_sets.update');
Route::put('salary_rate_sets/{id}/delete', "SalaryRateSetController@destroy")->name("salary_rate_sets.delete");

//srs_hr_items - По-часовые ставки схемы расчета заработной платы
Route::get('/srs_hr_items/create/{srs_id}', "SrsHrItemController@create")->name('srs_hr_items.create');
Route::get('srs_hr_items/{id}', 'SrsHrItemController@edit')->name('srs_hr_items.edit');
Route::match(array('POST', 'PUT'), 'srs_hr_items/{id}', "SrsHrItemController@update")
    ->name('srs_hr_items.update');
Route::put('srs_hr_items/{id}/delete', "SrsHrItemController@destroy")->name("srs_hr_items.delete");

//chargetypes - Справочник типов начислений/удержаний
Route::match(array('GET', 'POST'), '/chargetypes', "ChargetypeController@index")->name("chargetypes.index");
Route::get('/chargetypes/create/{parid}', "ChargetypeController@create")->name('chargetypes.create');
Route::get('chargetypes/{id}', 'ChargetypeController@edit')->name('chargetypes.edit');
Route::match(array('POST', 'PUT'), 'chargetypes/{id}', "ChargetypeController@update")
    ->name('chargetypes.update');
Route::put('chargetypes/{id}/delete', "ChargetypeController@destroy")->name("chargetypes.delete");
//Route::put('chargetypes/{id}/admindelete', "ChargetypeController@admindelete")->name("chargetypes.admindelete");

//org_charges - ставки Начислений/удержаний организаций
Route::match(array('GET', 'POST'), '/org_charges', "OrgChargeController@index")->name("org_charges.index");
Route::get('/org_charges/create/{parid}', "OrgChargeController@create")->name('org_charges.create');
Route::get('org_charges/{id}', 'OrgChargeController@edit')->name('org_charges.edit');
Route::match(array('POST', 'PUT'), 'chargetypes/{id}', "OrgChargeController@update")
    ->name('org_charges.update');
Route::put('org_charges/{id}/delete', "OrgChargeController@destroy")->name("org_charges.delete");
//Route::put('org_charges/{id}/admindelete', "OrgChargeController@admindelete")->name("org_charges.admindelete");
Route::get('/api/org_charges/for_ac/', 'OrgChargeController@list_for_ac');


//doctypes - Справочник типов документов, загружаемых в систему
Route::get('/doctypes/create/{parent_id}', "DoctypeController@create")->name('doctypes.create');
Route::match(array('GET', 'POST'), '/doctypes', "DoctypeController@index")->name("doctypes.index");
Route::get('doctypes/{id}', 'DoctypeController@edit')->name('doctypes.edit');
Route::match(array('POST', 'PUT'), 'doctypes/{id}', "DoctypeController@update")
    ->name('doctypes.update');
Route::put('doctypes/{id}/delete', "DoctypeController@destroy")->name("doctypes.delete");
Route::put('doctypes/{id}/admindelete', "DoctypeController@admindelete")->name("doctypes.admindelete");


//Телефонный и почтовый справочник
Route::match(array('GET', 'POST'), 'contacts', 'orgContactController@index')->name('orgcontacts.index');

Route::get('pref_catqtyfmt', "ObjprefController@editGlblPref25")->name('pref_catqtyfmt');
Route::put('pref_catqtyfmt/set', "ObjprefController@setGlblPref25")->name('pref_catqtyfmt.set');

Route::get('/api/sysfuncs/for_/', 'sysfuncController@list_for');
Route::get('/api/sysfuncs/for_ac/', 'sysfuncController@list_for_ac');
Route::get('/api/orgs/m15_tgt/', 'orgController@listorgs_m15tgt');
Route::get('/api/orgs/for_/', 'orgController@list_for');
Route::get('/api/orgs/for_ac/', 'orgController@list_for_ac');
Route::get('/api/orgposts/for_/', 'OrgpostController@list_for');
Route::get('/api/orgposts/stdlimunits', 'OrgpostController@stdlimunits');
Route::get('/api/orgposts/dep_posts', 'OrgpostController@dep_posts');
Route::get('/api/refitems/for_ac/', 'refItemController@list_for_ac');
Route::get('/api/machines/for_/', 'MachineController@list_for');
Route::get('/api/machines/for_ac/', 'MachineController@list_for_ac');
Route::get('/api/mchn_opertypes/for_/', 'MchnOpertypeController@list_for');
Route::get('/api/mchn_raids/data_for_driver_works/', 'MchnRaidController@data_for_driver_works');
Route::get('/orgstaff/staff/params/', 'orgstaffController@listorgstaff');
Route::get('/orgstaff/fio_name/params/', 'orgstaffController@liststafffio');
Route::get('/orgs/info/params/', 'orgController@info_params');
Route::get('/orgs/addrs/params/', 'OrgPlaceController@addrs_params');
Route::get('/api/doctypes/ac_/', 'DoctypeController@get_for');
Route::get('/api/doctypes/params/', 'DoctypeController@list_for');
Route::get('/api/orgstaff/ac_/', 'orgstaffController@get_for');

Route::get('/api/contracts/params/', 'ContractController@listcontracts');
Route::get('/api/contracts/buildopertypeid/', 'ContractController@list_for_buildopertypeid');
Route::get('/api/contracts/for_/', 'ContractController@list_for');
Route::get('/api/contractroles/typeid/', 'ContractroleController@list_for_contracttypeid');
Route::get('/api/regnum_srcs/', 'RegnumSrcController@list_for');

Route::get('/api/wrhs/for_/', 'WrhController@list_for');
Route::get('/api/wrh_boxes/for_/', 'WrhBoxController@list_for');
Route::get('/stock/wrhdoctypes/params', 'WrhdoctypeController@params');


//Счета на оплату
Route::match(array('GET', 'POST'), 'invoices', "InvoiceController@index")->name("invoices.index");
Route::get('invoices/create/{pardocid}', "InvoiceController@create")->name('invoices.create');
//загрузка счета из файла в формате XLS
//Route::get('invoices/load/', "InvoiceController@load")->name('invoices.load');
//Route::put('invoices/import/', "InvoiceController@import")->name('invoices.import');
Route::get('invoices/{id}/edit', "InvoiceController@edit")->name('invoices.edit');
Route::match(array('POST', 'PUT'), 'invoices/{id}', "InvoiceController@update")->name('invoices.update');
Route::put('invoices/{id}/delete', "InvoiceController@destroy")->name("invoices.delete");
Route::get('invoices/{id}/send2pay', "InvoiceController@send2pay")->name('invoices.send2pay');


//План платежей организации
Route::match(array('GET', 'POST'), 'orgplnpays', "OrgplnpayController@index")->name("orgplnpays.index");
Route::get('orgplnpays/create', "OrgplnpayController@create")->name('orgplnpays.create');
Route::get('orgplnpays/{id}/edit', "OrgplnpayController@edit")->name('orgplnpays.edit');
Route::match(array('POST', 'PUT'), 'orgplnpays/{id}', "OrgplnpayController@update")->name('orgplnpays.update');
//Route::put('orgplnpays/{id}/delete', "OrgplnpayController@destroy")->name("orgplnpays.delete");
Route::match(array('GET', 'PUT'), 'orgplnpays/{id}/delete', "OrgplnpayController@destroy")->name("orgplnpays.delete");

// сформировать уведомления по плановым платежам
Route::get('orgplnpays/notify', "OrgplnpayController@notify")->name('orgplnpays.notify');

// перенести неоплаченные счета на текущий план
Route::get('orgplnpays/fillprev/{id}', "OrgplnpayController@fillItemsFromPrev")->name('orgplnpays.fillitemsfromprev');
//Удалить просроченные заявки
Route::get('orgplnpays/rmvexp/{id}', "OrgplnpayController@removeExpired")->name('orgplnpays.remove_expired');

Route::match(array('POST', 'GET'), '/orgplnpays/rep/13', "OrgplnpayReportController@rep13")->name('reports.rep13');
Route::match(array('POST', 'GET'), '/orgplnpays/rep/14', "OrgplnpayReportController@rep14")->name('reports.rep14');
Route::match(array('POST', 'GET'), '/orgplnpays/rep/16', "OrgplnpayReportController@rep16")->name('reports.rep16');
Route::match(array('POST', 'GET'), '/orgplnpays/rep/17', "OrgplnpayReportController@rep17")->name('reports.rep17');
Route::match(array('POST', 'GET'), '/orgplnpays/rep/31', "OrgplnpayReportController@rep31")->name('reports.rep31');
Route::match(array('POST', 'GET'), '/orgplnpays/rep/43', "OrgplnpayReportController@rep43")->name('reports.rep43');
Route::match(array('POST', 'GET'), '/orgplnpays/rep/43_xls', "OrgplnpayReportController@rep43_excel")->name('reports.rep43_excel');
Route::match(array('POST', 'GET'), '/reports/rep/46', "MchnRaidReportController@rep46")->name('reports.rep46');
Route::match(array('POST', 'GET'), '/reports/rep/47', "PayDocReportController@rep47")->name('reports.rep47');
Route::match(array('POST', 'GET'), '/reports/rep/48/{ownorgid}/{orgid}', "PayDocReportController@rep48")->name('reports.rep48');
Route::match(array('POST', 'GET'), '/reports/rep/51', "MchnRaidReportController@rep51")->name('reports.rep51');
Route::match(array('POST', 'GET'), '/reports/rep/52', "MchnRaidReportController@rep52")->name('reports.rep52');
Route::match(array('POST', 'GET'), '/reports/rep/53/{ownorgid}/{orgid}', "PayDocReportController@rep53")->name('reports.rep53');
Route::match(array('POST', 'GET'), '/reports/rep/54/{date}', "PayDocReportController@rep54")->name('reports.rep54');
Route::match(array('POST', 'GET'), '/reports/rep/55/{date}', "WrhDocReportController@rep55")->name('reports.rep55');
Route::match(array('POST', 'GET'), '/reports/rep/56', "OrgChargeController@rep56")->name('reports.rep56');
Route::match(array('POST', 'GET'), '/reports/rep/57', "WrhDocReportController@rep57")->name('reports.rep57');
Route::match(array('POST', 'GET'), '/reports/rep/58', "DriverWorkReportController@rep58")->name('reports.rep58');
Route::match(array('POST', 'GET'), '/reports/rep/59', "DriverWorkReportController@rep59")->name('reports.rep59');
Route::match(array('POST', 'GET'), '/reports/rep/60', "WrhDocReportController@rep60")->name('reports.rep60');
Route::match(array('POST', 'GET'), '/reports/rep/61', "MchnRaidReportController@rep61")->name('reports.rep61');

//Состав плана платежей
Route::get('orgplnpay_items/create/{docid}/', "OrgplnpayItemController@create")->name('orgplnpay_items.create');
Route::get('orgplnpay_items/{id}/', 'OrgplnpayItemController@edit')->name('orgplnpay_items.edit');
Route::match(array('POST', 'PUT'), 'orgplnpay_items/{id}', "OrgplnpayItemController@update")->name('orgplnpay_items.update');
Route::put('orgplnpay_items/{id}/delete', "OrgplnpayItemController@destroy")->name("orgplnpay_items.delete");

Route::match(array('POST', 'PUT'), 'orgplnpay_items/agr1/{id}', "OrgplnpayItemController@update_agr1")->name('orgplnpay_items.agr1');
Route::get('orgplnpay_items/cancel_agr1/{id}/', "OrgplnpayItemController@cancel_agr1")->name('orgplnpay_items.cancel_agr1');

Route::match(array('POST', 'PUT'), 'orgplnpay_items/agr2/{id}', "OrgplnpayItemController@update_agr2")->name('orgplnpay_items.agr2');
Route::get('orgplnpay_items/cancel_agr2/{id}/', "OrgplnpayItemController@cancel_agr2")->name('orgplnpay_items.cancel_agr2');

Route::get('orgplnpay_items/regpay/{id}/', "OrgplnpayItemController@regpay")->name('orgplnpay_items.regpay');
Route::match(array('GET', 'PUT'), 'orgplnpay_items/regpay_upd/{id}', "OrgplnpayItemController@update_regpay")
    ->name('orgplnpay_items.update_regpay');

Route::match(array('POST', 'PUT'), 'orgplnpay_items/tgl_funding/{id}', "OrgplnpayItemController@toggle_funding")->name('orgplnpay_items.toggle_funding');
Route::match(array('POST', 'PUT'), 'orgplnpay_items/upd_alreadypay/{id}', "OrgplnpayItemController@upd_alreadypay")->name('orgplnpay_items.upd_alreadypay');

//Платежи
Route::match(array('GET', 'POST'), 'paydocs', "PaydocController@index")->name("paydocs.index");
Route::get('paydocs/create', "PaydocController@create")->name('paydocs.create');
Route::get('paydocs/{id}/edit', "PaydocController@edit")->name('paydocs.edit');
Route::match(array('POST', 'PUT'), 'paydocs/{id}', "PaydocController@update")->name('paydocs.update');
Route::match(array('GET', 'PUT'), 'paydocs/{id}/delete', "PaydocController@destroy")->name("paydocs.delete");
Route::get('paydocs/{id}/make_template', "PaydocController@make_template")->name('paydocs.make_template');

//Фин. транзакции
Route::get('obj_finopers/{sysobjid}/{objid}/refresh', "ObjFinoperController@refresh_for_obj")->name('obj_finopers.refresh_for_obj');
Route::get('mchn_raids/rfr_all_finopers', "MchnRaidController@rfr_all_finopers")->name('mchn_raids.rfr_all_finopers');
Route::get('paydocs/rfr_all_finopers', "PaydocController@rfr_all_finopers")->name('paydocs.rfr_all_finopers');


//уведомления пользователей
Route::get('user_notices/{id}/delete/{route}', "UserNoticeController@destroy")->name("user_notices.delete");


Route::get('/tst3', function () {

    //для отображения текста запроса

    $rcpts = \App\User::from('users as u')
        ->join('usrsysrights as usr', 'usr.userid', 'u.id')
        ->join('userorgs as uo', 'uo.userid', 'u.id')
        ->join('obj_approvals as oa', 'oa.dcsn_rightid', 'usr.sysfuncid')
        ->where('usr.active', 1)
        ->whereRaw('now() between usr.begdt and ifnull(usr.enddt,now())')
        ->where('oa.sysobjid', 870)
        ->where('oa.objid', 2)
        ->where('oa.stageid', 4)
        ->whereColumn('uo.orgid', 'oa.dcsn_orgid')
        ->select('u.lname', 'u.fname', 'u.mname', 'u.email')
        //  ->tosql();
        ->get();
    //dd($rcpts);
    //return $rcpts;

    return view('test.tst2');
});

Route::get('/home_refresh', 'HomeController@refresh')->name('home.refresh');

//Аналитические отчеты
Route::match(array('POST', 'PUT'), '/reports/rep/32/set', "AnaliticsController@rep32setparams")->name('reports.rep32set');
Route::match(array('POST', 'GET'), '/reports/rep/32', "AnaliticsController@rep32")->name('reports.rep32');

Route::match(array('POST', 'PUT'), '/reports/rep/45/set', "AnaliticsController@rep45setparams")->name('reports.rep45set');
Route::match(array('POST', 'GET'), '/reports/rep/45', "AnaliticsController@rep45")->name('reports.rep45');

//ACS - категории доступа к информации
Route::match(array('GET', 'POST'), '/acs', "AcController@index")->name('acs.index');
Route::get('acs/create', "AcController@create")->name('acs.create');
Route::get('acs/{id}/edit', "AcController@edit")->name('acs.edit');
Route::match(array('POST', 'PUT'), 'acs/{id}', "AcController@update")->name('acs.update');
Route::put('acs/{id}/delete', "AcController@destroy")->name("acs.delete");

Route::get('/acslst/{sysobjid}', "AcslstController@index")->name('acslst.index');
Route::match(array('GET', 'POST'), '/acslst/{sysobjid}', "AcslstController@index")
    ->name("acslst.index");
Route::get('acslst/{sysobjid}/{usrid}/new', "AcslstController@create")->name('acslst.create');
Route::get('acslst/{sysobjid}/{usrid}/edit', "AcslstController@edit")->name('acslst.edit');
Route::match(array('POST', 'PUT'), 'acslst/update', "AcslstController@update")->name('acslst.update');


//Pay_categories - категории платежей
Route::match(array('GET', 'POST'), '/pay_categories', "PayCategoryController@index")->name('pay_categories.index');
Route::get('pay_categories/create', "PayCategoryController@create")->name('pay_categories.create');
Route::get('pay_categories/{id}/edit', "PayCategoryController@edit")->name('pay_categories.edit');
Route::match(array('POST', 'PUT'), 'pay_categories/{id}', "PayCategoryController@update")->name('pay_categories.update');
Route::put('pay_categories/{id}/delete', "PayCategoryController@destroy")->name("pay_categories.delete");

//Шаблоны для новых записей user_templates
Route::match(array('GET', 'POST'), '/user_templates', "UserTemplateController@index")->name('user_templates.index');
//Route::get('user_templates/create', "UserTemplateController@create")->name('user_templates.create');
Route::get('user_templates/{id}/edit', "UserTemplateController@edit")->name('user_templates.edit');
Route::match(array('POST', 'PUT'), 'user_templates/{id}', "UserTemplateController@update")->name('user_templates.update');
Route::get('user_templates/{id}/delete', "UserTemplateController@destroy")->name("user_templates.delete");

// stf_payrolltypes - Способы расчета ЗП сотрудников (по периодам)
Route::get('stf_payrolltypes/{staffid}/create', "StfPayrolltypeController@create")->name('stf_payrolltypes.create');
Route::get('stf_payrolltypes/{id}/edit', "StfPayrolltypeController@edit")->name('stf_payrolltypes.edit');
Route::match(array('POST', 'PUT'), 'stf_payrolltypes/{id}', "StfPayrolltypeController@update")->name('stf_payrolltypes.update');
Route::get('stf_payrolltypes/{id}/delete', "StfPayrolltypeController@destroy")->name("stf_payrolltypes.delete");

// stf_charges - в каких начислениях/удержания участвует сотрудник
Route::get('stf_charges/{staffid}/create', "StfChargeController@create")->name('stf_charges.create');
Route::get('stf_charges/{id}/edit', "StfChargeController@edit")->name('stf_charges.edit');
Route::match(array('POST', 'PUT'), 'stf_charges/{id}', "StfChargeController@update")->name('stf_charges.update');
Route::get('stf_charges/{id}/delete', "StfChargeController@destroy")->name("stf_charges.delete");

// stf_chrg_calcs - фактические начисления/удержания сотрудника
Route::match(array('GET', 'POST'), '/stf_chrg_calcs', "StfChrgCalcController@index")->name('stf_chrg_calcs.index');
Route::get('stf_chrg_calcs/{staffid}/create', "StfChrgCalcController@create")->name('stf_chrg_calcs.create');
Route::get('stf_chrg_calcs/{id}/edit', "StfChrgCalcController@edit")->name('stf_chrg_calcs.edit');
Route::match(array('POST', 'PUT'), 'stf_chrg_calcs/{id}', "StfChrgCalcController@update")->name('stf_chrg_calcs.update');
Route::get('stf_chrg_calcs/{id}/delete', "StfChrgCalcController@destroy")->name("stf_chrg_calcs.delete");

//stf_salaries - ЗП сотрудников
Route::get('stf_salaries/{staffid}/create', "StfSalaryController@create")->name('stf_salaries.create');
Route::get('stf_salaries/{id}/edit', "StfSalaryController@edit")->name('stf_salaries.edit');
Route::match(array('POST', 'PUT'), 'stf_salaries/{id}', "StfSalaryController@update")->name('stf_salaries.update');
//Route::put('stf_salaries/{id}/delete', "StfSalaryController@destroy")->name("stf_salaries.delete");
Route::get('stf_salaries/{id}/delete', "StfSalaryController@destroy")->name("stf_salaries.delete");


// Дата блокировки данных ----------------------------------------------------------------------------------------------
Route::get('_lockdates/{sysobjid}/edit', "SysobjLockdateController@edit")->name('sysobj_lockdates.edit');
Route::match(array('POST', 'PUT'), '_lockdates/{sysobjid}', "SysobjLockdateController@update")->name('sysobj_lockdates.update');
//----------------------------------------------------------------------------------------------------------------------

// Задачи --------------------------------------------------------------------------------------------------------------
Route::match(array('GET', 'POST'), '/tasks', "myTaskController@index")->name('tasks.index');
Route::get('/tasks/create', 'myTaskController@create')->name('tasks.create');
Route::get('/tasks/edit/{id}', 'myTaskController@edit')->name('tasks.edit');
Route::match(array('POST', 'PUT'), 'tasks/{id}', "myTaskController@update")->name('tasks.update');
Route::put('/tasks/delete/{id}', 'myTaskController@destroy')->name('tasks.delete');

Route::match(array('POST', 'PUT'), 'tasks/take/{id}', "myTaskController@take")->name('tasks.take');
Route::match(array('POST', 'PUT'), 'tasks/break/{id}', "myTaskController@breakwork")->name('tasks.break');
Route::match(array('POST', 'PUT'), 'tasks/complete/{id}', "myTaskController@complete")->name('tasks.complete');
//----------------------------------------------------------------------------------------------------------------------

//работники по задаче
Route::get('/task_users/create/{taskid}', "TaskUserController@create")->name('task_users.create');
Route::get('/task_users/edit/{id}', 'TaskUserController@edit')->name('task_users.edit');
Route::match(array('POST', 'PUT'), '/task_users/update/{id}', "TaskUserController@update")->name('task_users.update');
Route::put('/task_users/delete/{id}', "TaskUserController@destroy")->name("task_users.delete");

//отчеты по задаче
Route::get('/task_reports/create/{taskid}', "TaskReportController@create")->name('task_reports.create');
Route::get('/task_reports/edit/{id}', 'TaskReportController@edit')->name('task_reports.edit');
Route::match(array('POST', 'PUT'), '/task_reports/update/{id}', "TaskReportController@update")->name('task_reports.update');
Route::put('/task_reports/delete/{id}', "TaskReportController@destroy")->name("task_reports.delete");

Route::match(array('GET', 'POST', 'PUT'), '/taskcalendar', 'TaskCalendarController@index')->name('tasks.calendar');
Route::get('/taskcalendar/get', 'TaskCalendarController@get');
Route::post('/taskcalendar/create', 'TaskCalendarController@create');
Route::post('/taskcalendar/update', 'TaskCalendarController@update');
Route::post('/taskcalendar/move', 'TaskCalendarController@move');
Route::post('/taskcalendar/delete', 'TaskCalendarController@destroy');



Route::match(array('GET', 'POST'), 'events_index', "EventController@index")->name("events.index");

Route::get('events/{id}/edit', "EventController@edit")->name('events.edit');
Route::match(array('POST', 'PUT'), 'events/{id}', "EventController@update")->name('events.update');
Route::get('events/create/{sysobjid}/{objid}', "EventController@create")->name('events.create');
Route::put('events/{id}/delete', "EventController@destroy")->name("events.delete");

// сформировать уведомления по событиям
Route::get('events/notify', "EventController@notify")->name('events.notify');

//Route::get('/fullcalendar', 'FullCalendarEventMasterController@index');
Route::match(array('GET', 'POST', 'PUT'), '/events', 'FullCalendarEventMasterController@index')->name('events.calendar');
Route::get('/fullcalendar/get', 'FullCalendarEventMasterController@get');
Route::post('/fullcalendar/create', 'FullCalendarEventMasterController@create');
Route::post('/fullcalendar/update', 'FullCalendarEventMasterController@update');
Route::post('/fullcalendar/move', 'FullCalendarEventMasterController@move');
Route::post('/fullcalendar/delete', 'FullCalendarEventMasterController@destroy');


Route::get('/tasks', 'myTaskController@index')->name('tasks.index');
Route::get('/tasks/create', 'myTaskController@create')->name('tasks.create');
Route::get('/tasks/edit/{id}', 'myTaskController@edit')->name('tasks.edit');
Route::match(array('POST', 'PUT'), 'tasks/{id}', "myTaskController@update")->name('tasks.update');
Route::put('/tasks/delete/{id}', 'myTaskController@destroy')->name('tasks.delete');


//Справочник Wrhs - склады предприятия
Route::get('wrhs', 'WrhController@index')->name('wrhs.index');
Route::match(array('GET', 'POST'), 'wrhs/search', "WrhController@search")
    ->name("wrhs.search");
Route::match(array('GET', 'POST'), 'wrhs/list', "WrhController@list")
    ->name("wrhs.list");
Route::get('wrhs/create', "WrhController@create")->name('wrhs.create');
Route::get('wrhs/{id}/edit', 'WrhController@edit')->name('wrhs.edit');
Route::match(array('POST'), 'wrhs/getshortinfo', 'WrhController@getshortinfo')
    ->name('wrhs.getshortinfo');
Route::match(array('POST', 'PUT'), 'wrhs/{id}', "WrhController@update")->name('wrhs.update');
Route::put('wrhs/{id}/delete', "WrhController@destroy")->name("wrhs.delete");

Route::get('wrh_boxes', 'WrhBoxController@index')->name('wrh_boxes.index');
Route::get('wrh_boxes/{wrhid}/create', "WrhBoxController@create")->name('wrh_boxes.create');
Route::get('wrh_boxes/{id}/edit', 'WrhBoxController@edit')->name('wrh_boxes.edit');
Route::match(array('POST', 'PUT'), 'wrh_boxes/{id}', "WrhBoxController@update")->name('wrh_boxes.update');
Route::put('wrh_boxes/{id}/delete', "WrhBoxController@destroy")->name("wrh_boxes.delete");


//    Route::get('wrhdocs', 'WrhdocController@index')->name('wrhdocs.index');
Route::match(array('GET', 'POST'), 'wrhdocs', 'WrhdocController@index')->name('wrhdocs.index');
Route::match(array('GET', 'POST'), 'wrhdocs/search', "WrhdocController@search")->name("wrhdocs.search");

Route::get('wrhdocs/create', "WrhdocController@create")->name('wrhdocs.create');
Route::get('wrhdocs/createfromord/{ordid}', "WrhdocController@createFromOrd")->name('wrhdocs.createfromord');
Route::get('wrhdocs/{id}', 'WrhdocController@edit')->name('wrhdocs.edit');
Route::match(array('POST', 'PUT'), 'Wrhdocs/{id}', "WrhdocController@update")->name('wrhdocs.update');
Route::put('wrhdocs/{id}/delete', "WrhdocController@destroy")->name("wrhdocs.delete");
Route::put('wrhdocs/{id}/admindelete', "WrhdocController@admindelete")->name("wrhdocs.admindelete");
Route::put('wrhdocs/{id}/sign', "WrhdocController@sign")->name("wrhdocs.sign");
Route::put('wrhdocs/{id}/unsign', "WrhdocController@unsign")->name("wrhdocs.unsign");
Route::get('wrhdocs/{id}/makediffdoc', "WrhdocController@make_diffdoc")->name("wrhdocs.make_diffdoc");
Route::get('wrhdocs/{id}/clone', "WrhdocController@clone")->name('wrhdocs.clone');
Route::get('wrhdocs/{id}/print', "WrhdocController@print")->name('wrhdocs.print');
Route::get('wrhdocs/{id}/make_doc5', "WrhdocController@make_doc5")->name("wrhdocs.make_doc5");

//Полный пересчет остатков на складах
Route::get('wrh_stocks/recalc', "WrhdocController@recalc_stock")->name('recalc_stock');

//Позиции документа склада
Route::get('wrhdoclst/{docid}/create', 'WrhdoclstController@create')->name('wrhdoclst.create');
Route::get('wrhdoclst/{id}/edit', "WrhdoclstController@edit")->name('wrhdoclst.edit');
Route::match(array('POST', 'PUT'), 'wrhdoclst/{id}', "WrhdoclstController@update")
    ->name('wrhdoclst.update');
Route::put('wrhdoclst/{id}/delete', "WrhdoclstController@destroy")->name("wrhdoclst.delete");
Route::get('wrhdoclst/{docid}/load', 'WrhdoclstController@load')->name('wrhdoclst.load.file');
Route::post('wrhdoclst/load/save', 'orderController@saveload');
Route::get('wrhdoclst/loadfromord/{docid}/{ordid}', "WrhdoclstController@loadFromOrder")->name('wrhdoclst.load.order');


//Склады для обслуживания организации
Route::get('org_wrhs/{orgid}', "OrgWrhController@index")->name('org_wrhs.index');
Route::get('org_wrhs/create/{orgid}', "OrgWrhController@create")->name('org_wrhs.create');
Route::get('org_wrhs/edit/{id}', 'OrgWrhController@edit')->name('org_wrhs.edit');
Route::match(array('POST', 'PUT'), 'org_wrhs/{id}', "OrgWrhController@update")->name('org_wrhs.update');
Route::put('org_wrhs/{id}/delete', "OrgWrhController@destroy")->name("org_wrhs.delete");

//Склады для обслуживания строительных объектов
//Route::get('buildobj_wrhs', "BuildobjWrhController@index")->name('buildobj_wrhs.index');
Route::get('buildobj_wrhs/create/{buildobjid}/{wrhid}', "BuildobjWrhController@create")->name('buildobj_wrhs.create');
Route::get('buildobj_wrhs/edit/{id}', 'BuildobjWrhController@edit')->name('buildobj_wrhs.edit');
Route::match(array('POST', 'PUT'), 'buildobj_wrhs/{id}', "BuildobjWrhController@update")->name('buildobj_wrhs.update');
Route::put('buildobj_wrhs/{id}/delete', "BuildobjWrhController@destroy")->name("buildobj_wrhs.delete");

//Типы складских документов
Route::get('wrhdoctypes/params/', 'WrhdoctypeController@params')->name('wrhdoctypes.params');
//Отчеты по данным склада
Route::match(array('POST', 'GET'), '/reports/rep/33', "WrhStockController@rep33")->name('reports.rep33');


//Документы регистрации получения материалов на "линии"
Route::match(array('GET', 'POST'), 'dlvrydocs', "DlvrydocController@index")->name("dlvrydocs.index");
Route::get('dlvrydocs/create', "DlvrydocController@create")->name('dlvrydocs.create');
Route::get('dlvrydocs/{id}/edit', "DlvrydocController@edit")->name('dlvrydocs.edit');
Route::match(array('POST', 'PUT'), 'dlvrydocs/{id}', "DlvrydocController@update")->name('dlvrydocs.update');
Route::get('dlvrydocs/{id}/delete', "DlvrydocController@destroy")->name("dlvrydocs.delete");
Route::match(array('POST', 'PUT'), 'dlvrydocs/{id}/add_items', "DlvrydocController@add_items")->name('dlvrydocs.add_items');
//печать в форме накладной
Route::get('dlvrydocs/{id}/print/1', 'DlvrydocController@print_nakl')->name('dlvrydocs.print_nakl');
//Формирование документа склада
Route::get('dlvrydocs/{id}/send2stock', 'DlvrydocController@send2stock')->name('dlvrydocs.send2stock');


// Информеры -----------------------------------------------------------------------------------------------------------
Route::match(array('GET', 'POST'), '/informers/', "InformerController@index")->name('informers.index');
Route::get('informers/create', "InformerController@create")->name('informers.create');
Route::get('informers/{id}', 'InformerController@edit')->name('informers.edit');
Route::match(array('POST', 'PUT'), 'informers/{id}', "InformerController@update")->name('informers.update');
Route::put('informers/{id}/delete', "InformerController@destroy")->name("informers.delete");

// Роли доступа для пользователей --------------------------------------------------------------------------------------
Route::match(array('GET', 'POST'), '/acl_roles', "AclRoleController@index")->name('acl_roles.index');
Route::get('acl_roles/create', "AclRoleController@create")->name('acl_roles.create');
Route::get('acl_roles/{id}', 'AclRoleController@edit')->name('acl_roles.edit');
Route::match(array('POST', 'PUT'), 'acl_roles/{id}', "AclRoleController@update")->name('acl_roles.update');
Route::put('acl_roles/{id}/delete', "AclRoleController@destroy")->name("acl_roles.delete");
Route::get('acl_role_rights/{id}/edt', 'AclRoleController@edtRoleRights')->name('acl_roles.edit_rights');
Route::match(array('GET', 'POST'), 'acl_role_rights/update/{id}', "AclRoleController@updRoleRights")
    ->name("acl_roles.update_rights");

//Роли доступа пользователя
Route::get('user_acl_roles/create/{userid}/', "UserAclRoleController@create")->name('user_acl_roles.create');
Route::get('user_acl_roles/{id}/', 'UserAclRoleController@edit')->name('user_acl_roles.edit');
Route::match(array('POST', 'PUT'), 'user_acl_roles/{id}', "UserAclRoleController@update")->name('user_acl_roles.update');
Route::put('user_acl_roles/{id}/delete', "UserAclRoleController@destroy")->name("user_acl_roles.delete");
