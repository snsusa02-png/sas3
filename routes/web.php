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

Route::get('/tst5/', 'orgController@tst_panther')->name('orgs.tst_panther');


Route::get('/tst_ftp', function () {

    $filePath = '321.txt';
    $url = Storage::disk('ftp')->url($filePath);
    $fileuri = Storage::disk('ftp')->getAdapter()->applyPathPrefix($filePath);
    dd($fileuri, file_exists($fileuri));

    dd($url);

});

Route::get('/test1', function () {
    //$categories = \App\itmtype::getCategories();
    //dd($categories);

    $data = new stdClass();
    $data->buildobj = \App\buildobj::find(34);
    $data->buildobj_name = "Комплекс многоквартирных жилых домов в районе ул.Снеговая, 9 г.Владивосток";
    $data->contract_num = "СКБ/2021-4";
    $data->place_info = "Встроенная подземная автостоянка №4. Секция в осях 12-15. Альбом 970-3/21(1)-13-КЖ1, лист 10.";
    $data->plndt = date_create('2021-11-10 10:00')->format('d.m.Y H:i');
    $data->fctdt = date_create('2021-11-10 10:15')->format('d.m.Y H:i');
    $data->chk_descript = "Армирование в осях 12-15 на с отм.-10.500 до отм. -7.300";
    $data->aux_docs = "Акты скрытых работ";
    $data->results = "";
    $data->orgs = [1 => '', 2 => '', 3 => 'X', 4 => 'X'];
    $data->other_orgs = 'никого';

    $data->works = [1 => '', 2 => '', 3 => 'X', 4 => 'X', 5 => '', 6 => '', 7 => '', 8 => '', 9 => '', 10 => ''
        , 11 => '', 12 => '', 13 => 'X', 14 => 'X', 15 => 'X', 16 => 'X'];
    $data->auxwork1_name = '';
    $data->auxwork2_name = '';

    $data->stf1_reason = 'Приказ №33';
    $data->stf1_post = 'Главный инженер';
    $data->stf1_fio = 'Коршиков И.В.';

    $data->stf2_reason = 'Приказ №13';
    $data->stf2_post = 'Прораб';
    $data->stf2_fio = 'Айрапетян А.С.';

    return view('test.rqst_sk', compact('data'));
//        ->withCategories($categories);

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

Route::get('/api/calendar', 'MyCalendarController@index');

Route::get('/home', 'HomeController@index')->name('home');

//для CKEditor (4)
Route::post('ckeditor/image_upload', 'CKEditorController@upload')->name('upload');

//Route::resource('post', 'PostController')->only('index', 'store', 'show');
//Route::get('/lib', 'PostController@public_index')->name('posts.public_index');
Route::match(array('GET', 'POST'), '/lib', "PostController@public_index")->name("posts.public_index");
Route::get('/blog/category/{category_id}', 'PostController@category_index')->name('posts.category_index');
Route::get('/blog/user/{user_id}', 'PostController@user_index')->name('posts.user_index');

Route::match(array('GET', 'POST'), '/posts', "PostController@index")->name("posts.index");
//Route::get('/lib', ['as' => 'home', 'uses' => 'PostController@index']);

// delete post
Route::post('/posts/delete/{id}', 'PostController@destroy')->name('posts.delete');

// display list of posts
//Route::get('user/{id}/posts', 'UserController@user_posts')->where('id', '[0-9]+');

// display single post
//Route::get('/post/{slug}', ['as' => 'post', 'uses' => 'PostController@show'])->name('posts.show')
//    ->where('slug', '[A-Za-z0-9-_]+');
Route::get('/post/{slug}', 'PostController@show')->name('posts.show')
    ->where('slug', '[A-Za-z0-9-_]+');
Route::get('/post/show/{id}', 'PostController@show_by_id')->name('posts.show_by_id');

//уведомление о необходимости прочитать опубликованный материал
Route::get('posts/{id}/notify/1', 'PostController@notify_mustreaders')->name('posts.notify_mustreaders');


// check for logged in user
Route::middleware(['auth'])->group(function () {
    // show new post form
    Route::get('new-post', 'PostController@create')->name('posts.create');
    // save new post
    Route::post('new-post', 'PostController@store');
    // edit post form
    Route::get('/post/edit/{id}', 'PostController@edit')->name('posts.edit');
    // update post
    Route::post('update', 'PostController@update');

    // display user's all posts
//    Route::get('my-all-posts', 'UserController@user_posts_all');
    // display user's drafts
//    Route::get('my-draft-posts', 'UserController@user_posts_draft');
    // add comment
    Route::post('post/comment/add', 'CommentController@store_for_post');
    Route::post('/comment/add', 'CommentController@store')->name('obj_comments.add');
    // delete comment
    Route::post('comment/delete/{id}', 'CommentController@destroy');
});

Route::get('/comments/create/{sysobjid}/{objid}', "ObjCommentController@create")->name('obj_comments.create');


//Новости
Route::match(array('GET', 'POST'), '/news', "NewsController@index")->name("news.index");
Route::get('/new-news', 'NewsController@create')->name('news.create');
Route::get('/news/{id}', 'NewsController@show')->name('news.show');
Route::get('/news/edit/{id}', 'NewsController@edit')->name('news.edit');
Route::post('/news/update/{id}', 'NewsController@update')->name('news.update');
Route::post('/news/delete/{id}', 'NewsController@destroy')->name('news.delete');

Route::match(array('GET', 'POST'), '/all-news', "NewsController@public_index")->name("news.public_index");
Route::get('/news/user/{user_id}', 'NewsController@user_index')->name('news.user_index');
Route::get('/news/band/{bandid}', 'NewsController@band_index')->name('news.band_index');
Route::get('/news_feed', 'NewsController@news_feed')->name('news.news_feed');
Route::get('/msgs_feed', 'NewsController@msgs_feed')->name('news.msgs_feed');

//Ленты новостей
Route::match(array('GET', 'POST'), '/newsbands', 'NewsbandController@index')->name('newsbands.index');
Route::get('/newsbands/create', 'NewsbandController@create')->name('newsbands.create');
Route::get('/newsbands/{id}/edit', "NewsbandController@edit")->name('newsbands.edit');
Route::match(array('POST', 'PUT'), 'newsbands/update/{id}', "NewsbandController@update")->name('newsbands.update');
Route::put('/newsbands/{id}/delete', "NewsbandController@destroy")->name("newsbands.delete");

//Сообщения
Route::post('/msg/add', 'ObjMsgController@store')->name('obj_msgs.add');


Route::get('/tst', 'testLoadController@load')->name('tstload');
//Route::get('/test/loadxml', "testController@load_1sdoc_xml");

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

//Route::get('/admin', function () {
//    return view('admin.index');
//})->middleware('auth')->name('admin');

Route::get('/admin', 'AdminController@index')->name('admin');


Route::get('/rprts', function () {
    //todo: не выходит вернуться в список отчетов
    //return view('reports.index');
    return redirect('/admin#nsi-rep');

})->middleware('auth')->name('reports');

//Пользователи (управление)
//Route::get('usermanage', 'UserManage@index')->name('usermanage.index');
//Route::match(array('GET', 'POST'), 'usermanage/search', "UserManage@search")->name("usermanage.search");
Route::match(array('GET', 'POST'), '/users', "UserManage@index")->name('users.index');
Route::get('users/{id}', 'UserManage@edit')->name('users.edit');
Route::get('usrsysrights/{id}/{limsysobjid}/{limobjid}/edt', 'UserManage@edtSysRights')->name('users.sysrights');
Route::match(array('GET', 'POST'), 'usermanage/updatesysrights/{id}/{limsysobjid}/{limobjid}', "UserManage@updUsrSysRights")->name("users.updatesysrights");
Route::match(array('POST', 'PUT'), 'usermanage/{id}', "UserManage@update")->name('users.update');
Route::put('users/{id}/delete', "UserManage@destroy")->name("users.delete");
Route::put('/setDefaultPassword/{userid}', 'UserManage@setDefaultPasswordForUser')->name('users.resetpassword');

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
Route::match(array('GET', 'POST'), '/usrsysright_orgs/{usrsysrightid}', "UsrsysrightOrgController@index")->name("usrsysright_orgs.index");
Route::get('usrsysright_orgs/{id}/edit', 'UsrsysrightOrgController@edit')->name('usrsysright_orgs.edit');
Route::get('usrsysright_orgs/create/{usrsysrightid}', 'UsrsysrightOrgController@create')
    ->name('usrsysright_orgs.create');
Route::match(array('POST', 'PUT'), 'usrsysright_orgs/{id}', "UsrsysrightOrgController@update")
    ->name('usrsysright_orgs.update');
Route::put('usrsysright_orgs/{id}/delete', "UsrsysrightOrgController@destroy")->name("usrsysright_orgs.delete");


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


//Загрузка файлов (для обработки)
Route::get('importfiles', 'ImportFileController@index')->name('importfiles.index');
Route::get('importfiles/create/{sysfiletypeid}', "ImportFileController@create")
    ->name('importfiles.create');

Route::match(array('POST', 'PUT'), 'importfiles/upload', 'ImportFileController@upload')
    ->name('importfiles.upload');
//Route::get('importfiles/{id}', 'ImportFileController@uploadedFile')->name('importfiles.uploadedfile');
Route::match(array('GET', 'POST'), '/importfiles/search', "ImportFileController@search")
    ->name("importfiles.search");

Route::get('importfiles/{id}/', 'ImportFileController@edit')->name('importfiles.edit');
Route::match(array('POST', 'PUT'), 'importfiles/{id}', "ImportFileController@update")
    ->name('importfiles.update');
Route::get('importfiles/{id}/download', 'ImportFileController@getFile')->name('importfiles.download');
Route::put('importfiles/{id}/delete', "ImportFileController@destroy")->name("importfiles.delete");


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

Route::get('/orgs/projects/params/', 'orgController@listprojects');


Route::get('orgs/org_users/{orgid}', "orgController@org_users")->name('org_users.index');
Route::get('orgs/org_staff/{orgid}', "orgController@org_staff")->name('org_staff.index');
Route::get('orgs/org_curators/{orgid}', "orgController@org_curators")->name('org_curators.index');
Route::get('orgs/org_supoffers/{orgid}', "orgController@org_supoffers")->name('org_supoffers.index');
Route::get('orgs/org_extids/{orgid}', "orgController@org_extids")->name('org_extids.index');
Route::get('orgs/org_ri_prices/{orgid}', "orgController@org_ri_prices")->name('org_ri_prices.index');

Route::get('orgs/org_groups/{orgid}', "orgController@org_groups_edit")->name('org_groups.edit');
Route::match(array('POST', 'PUT'), 'orgs/org_groups//{orgid}', "orgController@org_groups_update")
    ->name('org_groups.update');

Route::get('/org_saldos/create/{orgi
d}/{ownorgid}', "OrgSaldoController@create")->name('org_saldos.create');
Route::get('/org_saldos/edit/{id}', 'OrgSaldoController@edit')->name('org_saldos.edit');
Route::match(array('POST', 'PUT'), '/org_saldos/update/{id}', "OrgSaldoController@update")->name('org_saldos.update');
Route::put('/org_saldos/delete/{id}', "OrgSaldoController@destroy")->name("org_saldos.delete");

//расчетные счета организации
Route::get('org_acnts/{id}/edit', "OrgAcntController@edit")->name('org_acnts.edit');
Route::get('org_acnts/{orgid}/create', "OrgAcntController@create")->name('org_acnts.create');
Route::match(array('POST', 'PUT'), 'org_acnts/{id}', "OrgAcntController@update")->name('org_acnts.update');
Route::put('org_acnts/{id}/delete', "OrgAcntController@destroy")->name("org_acnts.delete");

Route::get('/org/acnts/params/', 'OrgAcntController@listActive');

//названия организации
Route::get('org_names/{id}/edit', "OrgNameController@edit")->name('org_names.edit');
Route::get('org_names/{orgid}/create', "OrgNameController@create")->name('org_names.create');
Route::match(array('POST', 'PUT'), 'org_names/{id}', "OrgNameController@update")->name('org_names.update');
Route::put('org_names/{id}/delete', "OrgNameController@destroy")->name("org_names.delete");


//подразделения организации
Route::get('orgdeps/{id}/edit', "OrgdepController@edit")->name('orgdeps.edit');
Route::get('orgdeps/{orgid}/create', "OrgdepController@create")->name('orgdeps.create');
Route::match(array('POST', 'PUT'), 'orgdeps/{id}', "OrgdepController@update")->name('orgdeps.update');
Route::put('orgdeps/{id}/delete', "OrgdepController@destroy")->name("orgdeps.delete");

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

//Просто места в городе/регионе
Route::get('/api/places/for_ac/', 'PlaceController@list_for_ac');


//Персонал
//Route::get('orgstaff', "orgstaffController@index")->name('orgstaff.index');
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
Route::get('stforders/create/{staffid}/', "StforderController@create")->name('stforders.create');
Route::get('stforders/{id}/', 'StforderController@edit')->name('stforders.edit');
Route::match(array('POST', 'PUT'), 'stforders/{id}', "StforderController@update")->name('stforders.update');
Route::put('stforders/{id}/delete', "StforderController@destroy")->name("stforders.delete");
Route::get('stforders/{id}/print', 'StforderController@print')->name('stforders.print');

//История должностей сотрудника
Route::get('staff_posts/create/{staffid}/', "StaffPostController@create")->name('staff_posts.create');
Route::get('staff_posts/{id}/', 'StaffPostController@edit')->name('staff_posts.edit');
Route::match(array('POST', 'PUT'), 'staff_posts/{id}', "StaffPostController@update")->name('staff_posts.update');
Route::put('staff_posts/{id}/delete', "StaffPostController@destroy")->name("staff_posts.delete");


//Архив документов
Route::match(array('GET', 'POST'), 'documents', "DocumentController@index")->name("documents.index");
Route::get('documents/create/{owngrp}', "DocumentController@create")->name('documents.create');
Route::get('documents/{id}/edit', "DocumentController@edit")->name('documents.edit');
Route::match(array('POST', 'PUT'), 'documents/{id}', "DocumentController@update")->name('documents.update');
Route::put('documents/{id}/delete', "DocumentController@destroy")->name("documents.delete");
Route::get('documents/{id}/make_template', "DocumentController@make_template")->name('documents.make_template');

Route::match(array('GET', 'POST'), 'documents/{id}/link', "DocumentController@link")->name("documents.link");
Route::match(array('POST', 'PUT'), 'documents/{id}/link_save', "DocumentController@link_save")->name('documents.link_save');

Route::match(array('POST', 'GET'), '/reports/rep/37', "DocumentReportController@rep37")->name('reports.rep37');
Route::match(array('POST', 'GET'), '/reports/rep/44', "DocumentReportController@rep44")->name('reports.rep44');

//Номенклатура дел организаций холдинга
Route::match(array('GET', 'POST'), 'org_caselists', "OrgCaselistController@index")->name("org_caselists.index");
Route::get('org_caselists/create', "OrgCaselistController@create")->name('org_caselists.create');
Route::get('org_caselists/{id}/edit', "OrgCaselistController@edit")->name('org_caselists.edit');
Route::match(array('POST', 'PUT'), 'org_caselists/{id}/upd', "OrgCaselistController@update")->name('org_caselists.update');
Route::get('org_caselists/{id}/delete', "OrgCaselistController@destroy")->name("org_caselists.delete");
Route::get('org_caselists/{id}/make_template', "OrgCaselistController@make_template")->name('org_caselists.make_template');

//состав/перечень Номенклатуры дел
Route::get('ocl_items/create/{ocl_id}/', "OclItemController@create")->name('ocl_items.create');
Route::get('ocl_items/{id}/', 'OclItemController@edit')->name('ocl_items.edit');
Route::match(array('POST', 'PUT'), 'ocl_items/{id}', "OclItemController@update")->name('ocl_items.update');
Route::put('ocl_items/{id}/delete', "OclItemController@destroy")->name("ocl_items.delete");


//Договоры
Route::match(array('GET', 'POST'), 'contracts', "ContractController@index")->name("contracts.index");

Route::get('contracts/{id}/edit', "ContractController@edit")->name('contracts.edit');
Route::match(array('POST', 'PUT'), 'contracts/{id}', "ContractController@update")->name('contracts.update');
Route::get('contracts/create/{owngrp}', "ContractController@create")->name('contracts.create');
Route::put('contracts/{id}/delete', "ContractController@destroy")->name("contracts.delete");

Route::get('/contracts/fill_regnums/{ownorgid}', "ContractController@fill_regnums")->name("contracts.fill_regnums");
Route::get('/contracts/fill_regnum/params/', 'ContractController@fill_regnum');

Route::get('contracts/{id}/make_template', "ContractController@make_template")->name('contracts.make_template');

//уведомление о необходимости прочитать опубликованный документ
Route::get('contracts/{id}/notify/1', 'ContractController@notify_mustreaders')->name('contracts.notify_mustreaders');
//Автозаполнение
Route::get('/api/contracts/autocomplete/', 'AutoCompleteController@ContractsAutocompleteSearch');

Route::match(array('POST', 'GET'), '/contracts/rep/23', "ContractController@rep23")->name('reports.rep23');

//Контрактные цены
Route::get('contract_prices/{id}/edit', "ContractPriceController@edit")->name('contract_prices.edit');
Route::match(array('POST', 'PUT'), 'contract_prices/{id}', "ContractPriceController@update")->name('contract_prices.update');
Route::get('contract_prices/create/{sysobjid}', "ContractPriceController@create")->name('contract_prices.create');
Route::put('contract_prices/{id}/delete', "ContractPriceController@destroy")->name("contract_prices.delete");

//Контракт - планы работ
Route::get('contract_workplans/create/{contractid}', "ContractWorkplanController@create")->name('contract_workplans.create');
Route::get('contract_workplans/{id}/edit', "ContractWorkplanController@edit")->name('contract_workplans.edit');
Route::match(array('POST', 'PUT'), 'contract_workplans/{id}', "ContractWorkplanController@update")->name('contract_workplans.update');
Route::put('contract_workplans/{id}/delete', "ContractWorkplanController@destroy")->name("contract_workplans.delete");

//Контракт - исполнение
Route::get('contract_exes/create/{contractid}/{buildopertypeid}', "ContractExeController@create")->name('contract_exes.create');
Route::get('contract_exes/{id}/edit', "ContractExeController@edit")->name('contract_exes.edit');
Route::match(array('POST', 'PUT'), 'contract_exes/{id}', "ContractExeController@update")->name('contract_exes.update');
Route::put('contract_exes/{id}/delete', "ContractExeController@destroy")->name("contract_exes.delete");

//печатные формы
Route::get('contract_exes/{id}/print/1', 'ContractExeController@print_1')->name('contract_exes.print_1');
Route::match(array('POST', 'GET'), '/contract_exes/rep/22', "ContractExeController@rep22")->name('reports.rep22');
Route::match(array('POST', 'GET'), '/contract_exes/rep/27', "ContractExeController@rep27")->name('reports.rep27');

//Контракт - согласование
Route::get('contract_reviews/create/{contractid}', "ContractReviewController@create")->name('contract_reviews.create');
Route::get('contract_reviews/{id}/edit', "ContractReviewController@edit")->name('contract_reviews.edit');
Route::match(array('POST', 'PUT'), 'contract_reviews/{id}', "ContractReviewController@update")->name('contract_reviews.update');
Route::put('contract_reviews/{id}/delete', "ContractReviewController@destroy")->name("contract_reviews.delete");
Route::get('contract_reviews/{id}/print/1', 'ContractReviewController@print_1')->name('contract_reviews.print_1');

//Контракт - согласование - участник
Route::get('contrrev_users/create/{contrrevid}', "ContrrevUserController@create")->name('contrrev_users.create');
Route::get('contrrev_users/{id}/edit', "ContrrevUserController@edit")->name('contrrev_users.edit');
Route::match(array('POST', 'PUT'), 'contrrev_users/{id}', "ContrrevUserController@update")->name('contrrev_users.update');
Route::put('contrrev_users/{id}/delete', "ContrrevUserController@destroy")->name("contrrev_users.delete");

//Проекты
Route::match(array('GET', 'POST'), 'projects/search', "ProjectController@search")->name("projects.search");
Route::match(array('GET', 'POST'), '/projects', "ProjectController@index")->name('projects.index');
Route::get('/projects/sort/{field}', 'ProjectController@index_sort')->name('projects.sort');
Route::match(array('GET', 'POST'), '/orders/search/org/{orgid}', "ProjectController@searchfororg")
    ->name("orders.for_org");

Route::get('projects/create', "ProjectController@create")->name('projects.create');
Route::get('projects/{id}', 'ProjectController@edit')->name('projects.edit');
Route::match(array('POST', 'PUT'), 'projects/{id}', "ProjectController@update")->name('projects.update');
Route::put('projects/{id}/delete', "ProjectController@destroy")->name("projects.delete");
Route::put('projects/{id}/admindelete', "ProjectController@admindelete")->name("projects.admindelete");

Route::get('projects/proj_risks/{projid}', "ProjectController@proj_risks")->name('proj_risks.index');
Route::get('projects/proj_milestones/{projid}', "ProjectController@proj_milestones")->name('proj_milestones.index');
Route::get('projects/proj_estdocs/{projid}', "ProjectController@proj_estdocs")->name('proj_estdocs.index');
Route::get('projects/proj_budgetitems/{projid}', "ProjectController@proj_budgetitems")->name('proj_budgetitems.index');
Route::get('projects/{projid}/load', 'testLoadController@load')->name('projects.estdoc_load');


//(Строительные) объекты
Route::match(array('GET', 'POST'), '/buildobjs', "BuildobjController@index")->name('buildobjs.index');
Route::get('buildobjs/create/proj/{projid}', "BuildobjController@create")->name('buildobjs.create');
Route::get('buildobjs/{id}', 'BuildobjController@edit')->name('buildobjs.edit');
Route::match(array('POST', 'PUT'), 'buildobjs/{id}', "BuildobjController@update")->name('buildobjs.update');
Route::put('buildobjs/{id}/delete', "BuildobjController@destroy")->name("buildobjs.delete");
//печатные формы
Route::get('buildobjs/{id}/print/1', 'BuildobjController@print_1')->name('buildobjs.print_1');

//Виды работ на объекте
Route::get('buildopertypes/{id}/edit', "BuildopertypeController@edit")->name('buildopertypes.edit');
Route::match(array('POST', 'PUT'), 'buildopertypes/{id}', "BuildopertypeController@update")->name('buildopertypes.update');
Route::get('buildopertypes/create/{buildobjid}', "BuildopertypeController@create")->name('buildopertypes.create');
Route::put('buildopertypes/{id}/delete', "BuildopertypeController@destroy")->name("buildopertypes.delete");

//ключевые(основные) работы для вида работ объекта
Route::get('bot_keyworks/create/{docid}/', "BotKeyworkController@create")->name('bot_keyworks.create');
Route::get('bot_keyworks/{id}/', 'BotKeyworkController@edit')->name('bot_keyworks.edit');
Route::match(array('POST', 'PUT'), 'bot_keyworks/{id}', "BotKeyworkController@update")->name('bot_keyworks.update');
Route::put('bot_keyworks/{id}/delete', "BotKeyworkController@destroy")->name("bot_keyworks.delete");

//Сотрудники объекта
Route::get('buildobj_staffs/{id}/edit', "BuildobjStaffController@edit")->name('buildobj_staffs.edit');
Route::match(array('POST', 'PUT'), 'buildobj_staffs/{id}', "BuildobjStaffController@update")->name('buildobj_staffs.update');
Route::get('buildobj_staffs/create/{buildobjid}', "BuildobjStaffController@create")->name('buildobj_staffs.create');
Route::put('buildobj_staffs/{id}/delete', "BuildobjStaffController@destroy")->name("buildobj_staffs.delete");

//Точки обзора объекта
Route::get('viewpoints/{id}/edit', "ViewpointController@edit")->name('viewpoints.edit');
Route::match(array('POST', 'PUT'), 'viewpoints/{id}', "ViewpointController@update")->name('viewpoints.update');
Route::get('viewpoints/create/{buildobjid}', "ViewpointController@create")->name('viewpoints.create');
Route::put('viewpoints/{id}/delete', "ViewpointController@destroy")->name("viewpoints.delete");

//Точки погрузки/разгрузки для объекта
Route::get('cargoload_points/{id}/edit', "CargoloadPointController@edit")->name('cargoload_points.edit');
Route::match(array('POST', 'PUT'), 'cargoload_points/{id}', "CargoloadPointController@update")->name('cargoload_points.update');
Route::get('cargoload_points/create/{buildobjid}', "CargoloadPointController@create")->name('cargoload_points.create');
Route::put('cargoload_points/{id}/delete', "CargoloadPointController@destroy")->name("cargoload_points.delete");

// Фото, сделанные из точки обзора объекта
Route::get('vp_photos/{id}/edit', "VpPhotoController@edit")->name('vp_photos.edit');
Route::match(array('POST', 'PUT'), 'vp_photos/{id}', "VpPhotoController@update")->name('vp_photos.update');
Route::get('vp_photos/create/{viewpointid}', "VpPhotoController@create")->name('vp_photos.create');
Route::get('vp_photos/{id}/delete', "VpPhotoController@destroyfile")->name("vp_photos.destroy");

Route::get('buildobjs/{id}/photos', "VpPhotoController@buildobj_photos")->name('buildobjs.photos');


//Работы, проводимые на объекте
Route::match(array('GET', 'POST'), '/buildobj_works', "BuildobjWorkController@index")->name('buildobj_works.index');
Route::get('buildobj_works/create/buildobj/{buildobjid}', "BuildobjWorkController@create")->name('buildobj_works.create');
Route::get('buildobj_works/{id}', 'BuildobjWorkController@edit')->name('buildobj_works.edit');
Route::match(array('POST', 'PUT'), 'buildobj_works/{id}', "BuildobjWorkController@update")->name('buildobj_works.update');
Route::put('buildobj_works/{id}/delete', "BuildobjWorkController@destroy")->name("buildobj_works.delete");
Route::get('buildobj_works/copy/{id}', "BuildobjWorkController@copy")->name("buildobj_works.copy");

//Ведомость ресурсов для вида работ
Route::get('bot_ri_lims/create/{buildopertypeid}/{bdgtitmsumid}', "BotRiLimController@create")->name('bot_ri_lims.create');
Route::get('bot_ri_lims/edit/{id}', "BotRiLimController@edit")->name('bot_ri_lims.edit');
Route::match(array('POST', 'PUT'), 'bot_ri_lims/update/{id}', "BotRiLimController@update")->name('bot_ri_lims.update');
Route::put('bot_ri_lims/delete/{id}', "BotRiLimController@destroy")->name("bot_ri_lims.delete");

Route::get('bot_ri_lims/load/{buildopertypeid}/{bdgtitmsumid}', "BotRiLimController@load")->name('bot_ri_lims.load');
Route::put('bot_ri_lims/import/', "BotRiLimController@import2")->name('bot_ri_lims.import');
Route::get('bot_ri_lims/import2/', "BotRiLimController@import2")->name('bot_ri_lims.import2');
//удаление дубликатов по refitmid
Route::get('bot_ri_lims/{buildopertypeid}/{bdgtitmsumid}/compress', "BotRiLimController@compress")->name('bot_ri_lims.compress');

//Автодополнение позиций для cwp_work_equips
Route::get('/bot_ri_lims/autocomplete/refitems', 'BotRiLimController@RefItemsAutocompleteSearch');

//Позиция плана работ для контракта
Route::get('cwp_works/create/{cwp_id}', "CwpWorkController@create")->name('cwp_works.create');
Route::get('cwp_works/edit/{id}', "CwpWorkController@edit")->name('cwp_works.edit');
Route::match(array('POST', 'PUT'), 'cwp_works/update/{id}', "CwpWorkController@update")->name('cwp_works.update');
Route::put('cwp_works/delete/{id}', "CwpWorkController@destroy")->name("cwp_works.delete");

//Ресурсы для работы
Route::get('cwp_work_equips/create/{workid}', "CwpWorkEquipController@create")->name('cwp_work_equips.create');
Route::get('cwp_work_equips/edit/{id}', "CwpWorkEquipController@edit")->name('cwp_work_equips.edit');
Route::match(array('POST', 'PUT'), 'cwp_work_equips/update/{id}', "CwpWorkEquipController@update")->name('cwp_work_equips.update');
Route::put('cwp_work_equips/delete/{id}', "CwpWorkEquipController@destroy")->name("cwp_work_equips.delete");

Route::get('cwp_work_equips/create_list/{workid}', "CwpWorkEquipController@edit_equips")->name('cwp_work_equips.create_from');
Route::put('/cwp_work_equips/update_list/save', 'CwpWorkEquipController@save_equips')->name('cwp_work_equips.update_list');

//Факт исполнения показателей работы
Route::get('cwp_facts/create/{workid}', "CwpFactController@create")->name('cwp_facts.create');
Route::get('cwp_facts/edit/{id}', "CwpFactController@edit")->name('cwp_facts.edit');
Route::match(array('POST', 'PUT'), 'cwp_facts/update/{id}', "CwpFactController@update")->name('cwp_facts.update');
Route::put('cwp_facts/delete/{id}', "CwpFactController@destroy")->name("cwp_facts.delete");


//Документы смет
Route::match(array('GET', 'POST'), '/estdocs/', "estdocController@index")->name('estdocs.index');
Route::get('/estdocs/create', "estdocController@create")->name('estdocs.create');
Route::get('/estdocs/load', "estdocController@load")->name('estdocs.load');
Route::get('/estdocs/{id}', 'estdocController@edit')->name('estdocs.edit');
Route::match(array('POST', 'PUT'), '/estdocs/{id}', "estdocController@update")->name('estdocs.update');
Route::put('estdocs/{id}/delete', "estdocController@destroy")->name("estdocs.delete");


Route::get('estdocitems/{id}', 'estdocItemController@edit')->name('estdocitems.edit');
Route::match(array('POST', 'PUT'), 'estdocitems/{id}', "estdocItemController@update")->name('estdocitems.update');

// Разделы состава сметы
Route::get('/estdoc_sections/{id}', 'estdocSectionController@edit')->name('estdoc_sections.edit');
Route::match(array('POST', 'PUT'), '/estdoc_sections/{id}', "estdocSectionController@update")->name('estdoc_sections.update');


//Операции по статьям бюджета
Route::get('projbudgetitems.edit/create', "ProjbudgetitemController@create")->name('projbudgetitems.create');
Route::get('projbudgetitems.edit/{id}', 'ProjbudgetitemController@edit')->name('projbudgetitems.edit');
Route::match(array('POST', 'PUT'), 'projbudgetitems.edit/{id}', "ProjbudgetitemController@update")->name('projbudgetitems.update');
Route::put('projbudgetitems.edit/{id}/delete', "ProjbudgetitemController@destroy")->name("projbudgetitems.delete");


//Планы производства работ
Route::match(array('GET', 'POST'), '/prodplans/', "ProdplanController@index")->name('prodplans.index');
Route::get('prodplans/create', "ProdplanController@create")->name('prodplans.create');
Route::get('prodplans/{id}', 'ProdplanController@edit')->name('prodplans.edit');
Route::match(array('POST', 'PUT'), 'prodplans/{id}', "ProdplanController@update")->name('prodplans.update');
Route::put('prodplans/{id}/delete', "ProdplanController@destroy")->name("prodplans.delete");

//Позиции планов производства работ
Route::get('/pp_tree/{docid}/{parid}', 'ProdplanItemController@index')->name('prodplan_items.index');
//Route::match(array('GET', 'POST'), '/catalog/search/{itmtypeid}', "IssaCatalogController@index")->name("prodplan_items.search");
//Route::get('/catalog/show/{id}', 'IssaCatalogController@show')->middleware('auth')->name('prodplan_items.show');
Route::get('/catalog/show/{id}', 'ProdplanItemController@show')->middleware('auth')->name('prodplan_items.show');

Route::get('prodplan_items/create/{docid}/{parid}', "ProdplanItemController@create")->name('prodplan_items.create');

Route::get('prodplan_items/edit/{id}', 'ProdplanItemController@edit')->name('prodplan_items.edit');
Route::get('prodplan_items/recalc_est/{id}', 'ProdplanItemController@recalc_est')->name('prodplan_items.recalc_est');

Route::match(array('POST', 'PUT'), 'prodplan_items.edit/{id}', "ProdplanItemController@update")->name('prodplan_items.update');
Route::put('prodplan_items.edit/{id}/delete', "ProdplanItemController@destroy")->name("prodplan_items.delete");

Route::put('prodplan_items/{id}/calcdonepcnt', "ProdplanItemController@calc_tree_donepcnt")
    ->name("prodplan_items.calc_tree_donepcnt");


//факт выполненных работ
Route::get('prodplan_facts/list/{planid}', "ProdplanFactController@index")->name('prodplan_facts.index');
Route::get('prodplan_facts/create/{planid}', "ProdplanFactController@create")->name('prodplan_facts.create');
Route::get('prodplan_facts/{id}', 'ProdplanFactController@edit')->name('prodplan_facts.edit');
Route::match(array('POST', 'PUT'), 'prodplan_facts.edit/{id}', "ProdplanFactController@update")->name('prodplan_facts.update');
Route::put('prodplan_facts.edit/{id}/delete', "ProdplanFactController@destroy")->name("prodplan_facts.delete");

// отчеты по фактам производства работ
Route::match(array('POST', 'GET'), '/prodplan_facts/rep/4', "ProdplanReportController@rep04")->name('reports.rep4');


//Совещания
Route::match(array('GET', 'POST'), '/meetings/', "MeetingController@index")->name('meetings.index');
Route::get('meetings/create', "MeetingController@create")->name('meetings.create');
Route::get('meetings/{id}', 'MeetingController@edit')->name('meetings.edit');
Route::match(array('POST', 'PUT'), 'meetings/{id}', "MeetingController@update")->name('meetings.update');
Route::put('meetings/{id}/delete', "MeetingController@destroy")->name("meetings.delete");

//перевести совещание на заданный этап
Route::put('meetings/{id}/setstage/{stageid}', 'MeetingController@Move2Stage')->name('meetings.move2stage');

//уведомление о намерении провести совещание - приглашение согласовать время и повестку
Route::get('meetings/{id}/notify/1', 'MeetingController@notify1')->name('meetings.notify');

//печать в форме анонса для согласованного совещания
//Route::get('meetings/{id}/print/1', 'MeetingController@print_anons')->name('meetings.print_anons');
Route::get('meetings/{id}/print/1', 'MeetingController@print_anons')->name('meetings.print_anons');

//печать в форме протокола - по завершенному совещанию
Route::get('meetings/{id}/print/2', 'MeetingController@print_protocol')->name('meetings.print_protocol');

//test - создание события календаря для заданного совещания
Route::get('meetings/{id}/makeevent', 'MeetingController@make_event')->name('meetings.make_event');
Route::get('meetings/{id}/disableevent', 'MeetingController@disable_event')->name('meetings.disable_event');
Route::get('meetings/{id}/removeevent', 'MeetingController@remove_event')->name('meetings.remove_event');

//Участники совещания
Route::get('meeting_staffs/{id}/edit', "MeetingStaffController@edit")->name('meeting_staffs.edit');
Route::match(array('POST', 'PUT'), 'meeting_staffs/{id}', "MeetingStaffController@update")->name('meeting_staffs.update');
Route::get('meeting_staffs/create/{protid}', "MeetingStaffController@create")->name('meeting_staffs.create');
Route::put('meeting_staffs/{id}/delete', "MeetingStaffController@destroy")->name("meeting_staffs.delete");
Route::match(array('POST', 'PUT'), 'meeting_staffs/{id}/accept', "MeetingStaffController@accept")->name('meeting_staffs.accept');

//Вопросы/задачи по протоколу совещания
Route::get('meeting_items/create/{docid}/', "MeetingItemController@create")->name('meeting_items.create');
Route::get('meeting_items/{id}/', 'MeetingItemController@edit')->name('meeting_items.edit');
Route::match(array('POST', 'PUT'), 'meeting_items/{id}', "MeetingItemController@update")->name('meeting_items.update');
Route::put('meeting_items/{id}/delete', "MeetingItemController@destroy")->name("meeting_items.delete");

Route::match(array('POST', 'PUT'), 'meeting_items/accept/{id}', "MeetingItemController@accept")->name('meeting_items.accept');
Route::match(array('POST', 'PUT'), 'meeting_items/decline/{id}', "MeetingItemController@decline")->name('meeting_items.decline');
Route::match(array('POST', 'PUT'), 'meeting_items/cancel_dcsn/{id}', "MeetingItemController@cancel_dcsn")->name('meeting_items.cancel_dcsn');

Route::match(array('POST', 'PUT'), 'meeting_items/{id}/reg_dcsn', "MeetingItemController@reg_dcsn")->name('meeting_items.reg_dcsn');
Route::match(array('POST', 'PUT'), 'meeting_items/{id}/reg_exe', "MeetingItemController@reg_exe")->name('meeting_items.reg_exe');

//закладка "СтройКонтроль"
Route::get('/qcheck', function () {
    return view('qcheck_tab.index');
})->middleware('auth')->name('qcheck');

//Проверки стройконтроля
Route::match(array('GET', 'POST'), '/qchecks/', "QcheckController@index")->name('qchecks.index');
Route::get('qchecks/create', "QcheckController@create")->name('qchecks.create');
Route::get('qchecks/{id}', 'QcheckController@edit')->name('qchecks.edit');
Route::match(array('POST', 'PUT'), 'qchecks/{id}', "QcheckController@update")->name('qchecks.update');
Route::put('qchecks/{id}/delete', "QcheckController@destroy")->name("qchecks.delete");
//печать в форме протокола
Route::get('qchecks/{id}/print/1', 'QcheckController@printform1')->name('qchecks.print1');
Route::get('qchecks/{id}/notify/1', 'QcheckController@notify1')->name('qchecks.notify');

//элементы проверки стройконтроля
Route::get('qcheck_items/create/{docid}/', "QcheckItemController@create")->name('qcheck_items.create');
Route::get('qcheck_items/{id}/', 'QcheckItemController@edit')->name('qcheck_items.edit');
Route::match(array('POST', 'PUT'), 'qcheck_items/{id}', "QcheckItemController@update")->name('qcheck_items.update');
Route::put('qcheck_items/{id}/delete', "QcheckItemController@destroy")->name("qcheck_items.delete");

Route::match(array('GET', 'POST'), '/qchecks/images/gallery', "QcheckController@imagegallery")->name('qchecks.gallery');

//уведомление о необходимости прочитать опубликованный материал
Route::get('qcheck_items/{id}/notify/1', 'QcheckItemController@notify_mustreaders')->name('qcheck_items.notify_mustreaders');

// Учет рабочего времени -----------------------------------------------------------------------------------------------
Route::match(array('GET', 'POST'), '/jts/', "JobtimesheetController@index")->name('jobtimesheets.index');
Route::get('jts/create', "JobtimesheetController@create")->name('jobtimesheets.create');
Route::get('jts/{id}', 'JobtimesheetController@edit')->name('jobtimesheets.edit');
Route::match(array('POST', 'PUT'), 'jts/{id}', "JobtimesheetController@update")->name('jobtimesheets.update');
Route::put('jts/{id}/delete', "JobtimesheetController@destroy")->name("jobtimesheets.delete");

//загрузка счета из файла в формате XLS
Route::get('jts/xls/load/', "JobtimesheetController@load")->name('jobtimesheets.load_xls');
Route::put('jts/xls/import/', "JobtimesheetController@import")->name('jobtimesheets.import_xls');

Route::match(array('POST', 'GET'), '/reports/rep/29', "jtsReportController@rep29")->name('reports.rep29');
Route::match(array('POST', 'GET'), '/reports/rep/30', "jtsReportController@rep30")->name('reports.rep30');

// ... рабочих
Route::get('jts_items/create/{docid}/', "JtsItemController@create")->name('jts_items.create');
Route::get('jts_items/{id}/', 'JtsItemController@edit')->name('jts_items.edit');
Route::match(array('POST', 'PUT'), 'jts_items/{id}', "JtsItemController@update")->name('jts_items.update');
Route::put('jts_items/{id}/delete', "JtsItemController@destroy")->name("jts_items.delete");

// ... механизмов
Route::get('jts_machines/create/{docid}/', "JtsMachineController@create")->name('jts_machines.create');
Route::get('jts_machines/{id}/', 'JtsMachineController@edit')->name('jts_machines.edit');
Route::match(array('POST', 'PUT'), 'jts_machines/{id}', "JtsMachineController@update")->name('jts_machines.update');
Route::put('jts_machines/{id}/delete', "JtsMachineController@destroy")->name("jts_machines.delete");
// ... нарушения
Route::get('jts_violations/create/{docid}/', "JtsViolationController@create")->name('jts_violations.create');
Route::get('jts_violations/{id}/', 'JtsViolationController@edit')->name('jts_violations.edit');
Route::match(array('POST', 'PUT'), 'jts_violations/{id}', "JtsViolationController@update")->name('jts_violations.update');
Route::put('jts_violations/{id}/delete', "JtsViolationController@destroy")->name("jts_violations.delete");
// ... заезды авто
Route::get('jts_mchn_visits/create/{docid}/', "JtsMchnVisitController@create")->name('jts_mchn_visits.create');
Route::get('jts_mchn_visits/{id}/', 'JtsMchnVisitController@edit')->name('jts_mchn_visits.edit');
Route::match(array('POST', 'PUT'), 'jts_mchn_visits/{id}', "JtsMchnVisitController@update")->name('jts_mchn_visits.update');
Route::put('jts_mchn_visits/{id}/delete', "JtsMchnVisitController@destroy")->name("jts_mchn_visits.delete");
//----------------------------------------------------------------------------------------------------------------------

// КУРСИА - Учет рабочего времени спецтехники --------------------------------------------------------------------------
Route::match(array('GET', 'POST'), '/cursias/', "CursiaController@index")->name('cursias.index');
Route::get('cursias/create', "CursiaController@create")->name('cursias.create');
Route::get('cursias/{id}', 'CursiaController@edit')->name('cursias.edit');
Route::match(array('POST', 'PUT'), 'cursias/{id}', "CursiaController@update")->name('cursias.update');
Route::put('cursias/{id}/delete', "CursiaController@destroy")->name("cursias.delete");
Route::get('cursias/{id}/make_template', "CursiaController@make_template")->name('cursias.make_template');

// driver_works - Учет рабочего времени водителей----------------------------------------------------------------------
Route::match(array('GET', 'POST'), '/driver_works/', "DriverWorkController@index")->name('driver_works.index');
Route::get('driver_works/create', "DriverWorkController@create")->name('driver_works.create');
Route::get('driver_works/{id}', 'DriverWorkController@edit')->name('driver_works.edit');
Route::match(array('POST', 'PUT'), 'driver_works/{id}', "DriverWorkController@update")->name('driver_works.update');
Route::get('driver_works/{id}/delete', "DriverWorkController@destroy")->name("driver_works.delete");
Route::get('driver_works/{id}/make_template', "DriverWorkController@make_template")->name('driver_works.make_template');

// mchn_raids - Учет рейсов спецтехники --------------------------------------------------------------------------
Route::match(array('GET', 'POST'), '/mchn_raids/', "MchnRaidController@index")->name('mchn_raids.index');
Route::get('mchn_raids/create/{dw_id}', "MchnRaidController@create")->name('mchn_raids.create');
Route::get('mchn_raids/{id}', 'MchnRaidController@edit')->name('mchn_raids.edit');
Route::match(array('POST', 'PUT'), 'mchn_raids/{id}', "MchnRaidController@update")->name('mchn_raids.update');
Route::put('mchn_raids/{id}/delete', "MchnRaidController@destroy")->name("mchn_raids.delete");
Route::get('mchn_raids/{id}/make_template', "MchnRaidController@make_template")->name('mchn_raids.make_template');

// dw_breaks - Простои в работе водителя -------------------------------------------------------------------------
Route::get('dw_breaks/create/{dw_id}', "DwBreakController@create")->name('dw_breaks.create');
Route::get('dw_breaks/{id}', 'DwBreakController@edit')->name('dw_breaks.edit');
Route::match(array('POST', 'PUT'), 'dw_breaks/{id}', "DwBreakController@update")->name('dw_breaks.update');
Route::get('dw_breaks/{id}/delete', "DwBreakController@destroy")->name("dw_breaks.delete");


// checkrqsts - Запросы на проведение инспецкций СК -------------------------------------------------------------------
Route::match(array('GET', 'POST'), '/checkrqsts/', "CheckrqstController@index")->name('checkrqsts.index');
Route::get('checkrqsts/create', "CheckrqstController@create")->name('checkrqsts.create');
Route::get('checkrqsts/{id}', 'CheckrqstController@edit')->name('checkrqsts.edit');
Route::match(array('POST', 'PUT'), 'checkrqsts/{id}', "CheckrqstController@update")->name('checkrqsts.update');
Route::put('checkrqsts/{id}/delete', "CheckrqstController@destroy")->name("checkrqsts.delete");
Route::get('checkrqsts/{id}/make_template', "CheckrqstController@make_template")->name('checkrqsts.make_template');
//печать в форме заявки
Route::get('checkrqsts/{id}/print/1', 'CheckrqstController@print_rqst')->name('checkrqsts.print_rqst');

// Отчет о работе ------------------------------------------------------------------------------------------------------
Route::match(array('GET', 'POST'), '/wrkreps/', "WrkrepController@index")->name('wrkreps.index');
Route::get('wrkreps/create', "WrkrepController@create")->name('wrkreps.create');
Route::get('wrkreps/{id}', 'WrkrepController@edit')->name('wrkreps.edit');
Route::match(array('POST', 'PUT'), 'wrkreps/{id}', "WrkrepController@update")->name('wrkreps.update');
Route::put('wrkreps/{id}/delete', "WrkrepController@destroy")->name("wrkreps.delete");

// ... механизмов
Route::get('wrkrep_machines/create/{docid}/', "WrkrepMachineController@create")->name('wrkrep_machines.create');
Route::get('wrkrep_machines/{id}/', 'WrkrepMachineController@edit')->name('wrkrep_machines.edit');
Route::match(array('POST', 'PUT'), 'wrkrep_machines/{id}', "WrkrepMachineController@update")->name('wrkrep_machines.update');
Route::put('wrkrep_machines/{id}/delete', "WrkrepMachineController@destroy")->name("wrkrep_machines.delete");
//----------------------------------------------------------------------------------------------------------------------


//Планы работ
Route::match(array('GET', 'POST'), '/wrkplans/', "WrkplanController@index")->name('wrkplans.index');
Route::get('wrkplans/create', "WrkplanController@create")->name('wrkplans.create');
Route::get('wrkplans/{id}', 'WrkplanController@edit')->name('wrkplans.edit');
Route::match(array('POST', 'PUT'), 'wrkplans/{id}', "WrkplanController@update")->name('wrkplans.update');
Route::put('wrkplans/{id}/delete', "WrkplanController@destroy")->name("wrkplans.delete");

Route::get('wrkplans/unreg_plan/{id}', 'WrkplanController@unreg_plan')->name('wrkplans.unreg_plan');

Route::match(array('POST', 'PUT'), 'wrkplans/save_report/{id}', "WrkplanController@save_report")->name('wrkplans.save_report');
Route::match(array('POST', 'PUT'), 'wrkplans/reg_report/{id}', "WrkplanController@reg_report")->name('wrkplans.reg_report');

Route::match(array('POST', 'PUT'), 'wrkplans/save_reason/{id}', "WrkplanController@save_reason")->name('wrkplans.save_reason');
Route::match(array('POST', 'PUT'), 'wrkplans/reg_reason/{id}', "WrkplanController@reg_reason")->name('wrkplans.reg_reason');

//Route::put('wrkplans/reg/{id}', "WrkplanController@registrate")->name("wrkplans.registrate");


//Заявки на материалы и оборудование
Route::match(array('GET', 'POST'), '/equiprqsts/', "EquiprqstsController@index")->name('equiprqsts.index');
Route::get('equiprqsts/create', "EquiprqstsController@create")->name('equiprqsts.create');
Route::get('equiprqsts/{id}', 'EquiprqstsController@edit')->name('equiprqsts.edit');
Route::match(array('POST', 'PUT'), 'equiprqsts/{id}', "EquiprqstsController@update")->name('equiprqsts.update');
//Route::put('equiprqsts/{id}/delete', "EquiprqstsController@destroy")->name("equiprqsts.delete");
Route::match(array('GET', 'PUT'), 'equiprqsts/{id}/delete', "EquiprqstsController@destroy")->name("equiprqsts.delete");

Route::match(array('GET', 'POST'), '/plnsupplies/', "EquiprqstsController@plnsupplies")->name('equiprqsts.plnsupplies');

Route::get('/equiprqsts/orgplnpay_items/params/', 'EquiprqstsController@listrqsts');


//перевести заявку на заданный этап
Route::put('equiprqsts/{id}/setstage/{stageid}', 'EquiprqstsController@Move2Stage')->name('equiprqsts.move2stage');

Route::match(array('POST', 'PUT'), 'equiprqsts/approve/{id}', "EquiprqstsController@approve")->name('equiprqsts.approve');
Route::match(array('POST', 'PUT'), 'equiprqsts/decline/{id}', "EquiprqstsController@decline")->name('equiprqsts.decline');
//Route::match(array('POST', 'PUT'), 'equiprqsts/cancel_dcsn/{id}', "EquiprqstsController@cancel_dcsn")->name('equiprqsts.cancel_dcsn');

Route::match(array('POST', 'PUT'), 'equiprqsts/setfinlim/{id}', "EquiprqstsController@setfinlim")->name('equiprqsts.setfinlim');
Route::match(array('POST', 'PUT'), 'equiprqsts/setplnorder/{id}', "EquiprqstsController@setplnorder")->name('equiprqsts.setplnorder');
Route::match(array('POST', 'PUT'), 'equiprqsts/setplnpay/{id}', "EquiprqstsController@setplnpay")->name('equiprqsts.setplnpay');

Route::match(array('POST', 'PUT'), 'equiprqsts/take/{id}', "EquiprqstsController@take")->name('equiprqsts.take');
Route::match(array('POST', 'PUT'), 'equiprqsts/break/{id}', "EquiprqstsController@breakwork")->name('equiprqsts.break');

//печать в форме заявки
Route::get('equiprqsts/{id}/print/1', 'EquiprqstsController@print_rqst')->name('equiprqsts.print_rqst');
Route::get('equiprqsts/{id}/print/2', 'EquiprqstsController@print_orddata')->name('equiprqsts.print_orddata');
Route::get('equiprqsts/{id}/print/3', 'EquiprqstsController@print_resved')->name('equiprqsts.print_resved');
Route::get('equiprqsts/{id}/print/4', 'EquiprqstsController@print_4')->name('equiprqsts.print_4');

Route::get('equiprqsts/{id}/budget_regest', 'EquiprqstsController@budget_reg_est')->name('equiprqsts.budget_regest');
Route::get('equiprqsts/{id}/budget_reguse', 'EquiprqstsController@budget_reg_use')->name('equiprqsts.budget_reguse');

Route::get('equiprqsts/{rqstid}/invoice2pay/{invoiceid}', 'EquiprqstsController@send_invoice2pay')->name('equiprqsts.send_invoice2pay');
Route::get('equiprqsts/{id}/invoices2pay', 'EquiprqstsController@send_invoices2pay')->name('equiprqsts.send_invoices2pay');

Route::match(array('POST', 'GET'), '/equiprqsts/rep/12', "EquiprqstReportController@rep12")->name('reports.rep12'); //Кандидаты для М-15
Route::match(array('POST', 'GET'), '/equiprqsts/rep/15', "EquiprqstReportController@rep15")->name('reports.rep15');
Route::match(array('POST', 'GET'), '/equiprqsts/rep/18', "EquiprqstReportController@rep18")->name('reports.rep18');
Route::match(array('POST', 'GET'), '/equiprqsts/rep/19', "EquiprqstReportController@rep19")->name('reports.rep19');
Route::match(array('POST', 'GET'), '/equiprqsts/rep/20', "EquiprqstReportController@rep20")->name('reports.rep20');
Route::match(array('POST', 'GET'), '/equiprqsts/rep/21', "EquiprqstReportController@rep21")->name('reports.rep21');
Route::match(array('POST', 'GET'), '/equiprqsts/rep/28', "EquiprqstReportController@rep28")->name('reports.rep28');
Route::match(array('POST', 'GET'), '/equiprqsts/rep/35', "EquiprqstReportController@rep35")->name('reports.rep35');
Route::match(array('POST', 'GET'), '/equiprqsts/rep/39', "EquiprqstReportController@rep39")->name('reports.rep39');
Route::match(array('POST', 'GET'), '/budgets/rep/40', "BudgetReportController@rep40")->name('reports.rep40');
Route::match(array('POST', 'GET'), '/cursias/rep/41', "CursiaReportController@rep41")->name('reports.rep41');
Route::match(array('POST', 'GET'), '/equiprqsts/rep/42', "EquiprqstReportController@rep42")->name('reports.rep42');
Route::match(array('POST', 'GET'), '/equiprqsts/rep/42_1/{refitmid}', "EquiprqstReportController@rep42_1")->name('reports.rep42_1');


//Состав заявки на материалы и оборудование
Route::get('equiprqst_items/create/{docid}/', "EquiprqstItemsController@create")->name('equiprqst_items.create');
Route::get('equiprqst_items/create_list/{docid}/', "EquiprqstItemsController@create_list")->name('equiprqst_items.create_list');
Route::get('equiprqst_items/{id}/', 'EquiprqstItemsController@edit')->name('equiprqst_items.edit');
Route::match(array('POST', 'PUT'), 'equiprqst_items/{id}', "EquiprqstItemsController@update")->name('equiprqst_items.update');
//Route::put('equiprqst_items/{id}/delete', "EquiprqstItemsController@destroy")->name("equiprqst_items.delete");
Route::match(array('GET', 'PUT'), 'equiprqst_items/{id}/delete', "EquiprqstItemsController@destroy")->name("equiprqst_items.delete");

Route::get('/equiprqst_items/edit_list/{docid}', "EquiprqstItemsController@edit_list")->name('equiprqst_items.edit_list');
Route::put('/equiprqst_items/update_list/save', 'EquiprqstItemsController@update_list')->name('equiprqst_items.update_list');


Route::get('/equiprqst_items/{rqstid}/setestprices', 'EquiprqstsController@set_estprices')->name('equiprqst_items.setestprices');
Route::post('/equiprqst_items/setestprices/save', 'EquiprqstsController@save_estprices');
Route::put('/equiprqst_items/setestprices/update', 'EquiprqstsController@upd_estprices')->name('equiprqsts.upd_estprices');

Route::get('/equiprqst_items/setall/estprices', 'EquiprqstsController@set_allestprices')->name('equiprqst_items.set_allestprices');
Route::put('/equiprqst_items/setall/estprices/update', 'EquiprqstsController@upd_allestprices')->name('equiprqsts.upd_allestprices');

//Route::get('/equiprqsts/check/prices', 'EquiprqstsController@check_prices')->name('equiprqsts.check_prices');
Route::match(array('GET', 'post'), '/equiprqsts/check/prices', "EquiprqstsController@check_prices")->name('equiprqsts.check_prices');
Route::get('/equiprqsts/check/prices/edit/{id}', 'EquiprqstsController@check_prices_edit')->name('equiprqsts.check_prices_edit');
Route::match(array('POST', 'PUT'), 'equiprqsts/check/prices/set/{id}', "EquiprqstsController@check_prices_set")->name('equiprqsts.check_prices_set');
Route::match(array('POST', 'PUT'), 'equiprqsts/check/prices/skip/{id}', "EquiprqstsController@check_prices_skip")->name('equiprqsts.check_prices_skip');


Route::get('/equiprqst_items/{rqstid}/setbdgtacnts', 'EquiprqstsController@setbudgetacnts')->name('equiprqst_items.setbudgetacnts');
Route::put('/equiprqst_items/setbdgtacnts/save', 'EquiprqstsController@upd_budgetacnts')->name('equiprqsts.upd_budgetacnts');

Route::get('/equiprqst_items/{rqstid}/orddata', 'EquiprqstsController@edit_orddata')->name('equiprqst_items.edit_orddata');
Route::put('/equiprqst_items/orddata/save', 'EquiprqstsController@save_orddata')->name('equiprqst_items.save_orddata');

Route::get('/equiprqst_items/{rqstid}/orddatafororg/{orgid}', 'EquiprqstsController@edit_orddata_org')->name('equiprqst_items.edit_orddata_org');
//Route::put('/equiprqst_items/orddatafororg/save', 'EquiprqstsController@save_orddata_org')->name('equiprqst_items.save_orddata_org');

//устарело, данные УПД вносим в УПД
Route::get('/equiprqst_items/{rqstid}/upddata', 'EquiprqstsController@edit_upddata')->name('equiprqst_items.edit_upddata');
Route::put('/equiprqst_items/upddata/save', 'EquiprqstsController@save_upddata')->name('equiprqst_items.save_upddata');

Route::get('equiprqst_items/link2lim/{refitmid}/{buildopertypeid}', 'EquiprqstItemsController@link2lim_edit')->name('equiprqst_items.link2lim');
Route::match(array('POST', 'PUT'), 'equiprqst_items/link2lim/{refitmid}/{buildopertypeid}', "EquiprqstItemsController@link2lim_update")->name('equiprqst_items.link2lim_update');
//добавление материала из состава заявки
Route::get('equiprqst_items/{id}/add2refitems', 'EquiprqstItemsController@add2refitems')->name('equiprqst_items.add2refitems');
//конверитрование ЕИ позиции заявки в ЕИ заданной номенклатуры
Route::get('equiprqst_items/{id}/{refitmid}/convert', 'EquiprqstItemsController@convert2refitm')->name('equiprqst_items.convert2refitm');

//Route::get('/equiprqst_items/{rqstid}/edtqty', 'EquiprqstsController@edtOrdItemsQty')->name('equiprqst_items.edtqty');
//Route::post('/equiprqst_items/edtqty/save', 'EquiprqstsController@saveOrdItemsQty');

Route::match(array('POST', 'PUT'), 'equiprqst_items/setorder/{id}', "EquiprqstItemsController@setorder")->name('equiprqst_items.setorder');
Route::get('/equiprqst_items_fill_m15srcorgid', 'EquiprqstItemsController@fill_m15srcorgid');


//Предложения поставщиков по строке/позиции заявки
Route::get('eritm_offers/create/{eritmid}/', "EritmOfferController@create")->name('eritm_offers.create');
Route::get('eritm_offers/{id}/', 'EritmOfferController@edit')->name('eritm_offers.edit');
Route::match(array('POST', 'PUT'), 'eritm_offers/{id}', "EritmOfferController@update")->name('eritm_offers.update');
Route::put('eritm_offers/{id}/delete', "EritmOfferController@destroy")->name("eritm_offers.delete");

//Поставки поставщиков по заказу(offer)
Route::get('eritm_supplies/create/{offerid}/', "EritmSupplyController@create")->name('eritm_supplies.create');
Route::get('eritm_supplies/eritm_supplies/{id}/', 'EritmSupplyController@edit')->name('eritm_supplies.edit');
Route::match(array('POST', 'PUT'), 'eritm_supplies/{id}', "EritmSupplyController@update")->name('eritm_supplies.update');
Route::put('eritm_supplies/{id}/delete', "EritmSupplyController@destroy")->name("eritm_supplies.delete");

//Учет передачи материалов подрядчику по формам М15
Route::get('ersup_m15s/create/{eritmid}/', "ErsupM15Controller@create")->name('ersup_m15s.create');
Route::get('ersup_m15s/{id}/', 'ErsupM15Controller@edit')->name('ersup_m15s.edit');
Route::match(array('POST', 'PUT'), 'ersup_m15s/{id}', "ErsupM15Controller@update")->name('ersup_m15s.update');
Route::put('ersup_m15s/{id}/delete', "ErsupM15Controller@destroy")->name("ersup_m15s.delete");


//Дополнительные затраты по заявке на материалы и оборудование
Route::get('equiprqst_expenses/create/{docid}/', "EquiprqstExpenseController@create")->name('equiprqst_expenses.create');
Route::get('equiprqst_expenses/{id}/', 'EquiprqstExpenseController@edit')->name('equiprqst_expenses.edit');
Route::match(array('POST', 'PUT'), 'equiprqst_expenses/{id}', "EquiprqstExpenseController@update")->name('equiprqst_expenses.update');
Route::put('equiprqst_expenses/{id}/delete', "EquiprqstExpenseController@destroy")->name("equiprqst_expenses.delete");

Route::get('equiprqst_expenses/create4upd/{upd_id}/', "EquiprqstExpenseController@create4upd")->name('equiprqst_expenses.create4upd');
Route::get('equiprqst_expenses/4upd/{id}/', 'EquiprqstExpenseController@edit4upd')->name('equiprqst_expenses.edit4upd');
Route::match(array('POST', 'PUT'), 'equiprqst_expenses/4upd/{id}', "EquiprqstExpenseController@update4upd")->name('equiprqst_expenses.update4upd');
//Route::put('equiprqst_expenses/{id}/delete', "EquiprqstExpenseController@destroy")->name("equiprqst_expenses.delete");

Route::get('equiprqsts/calcoutprice/{docid}/', "EquiprqstsController@calcOutPrice")->name('equiprqsts.calcoutprice');

Route::get('equiprqst_expenses/{id}/make_contract_exe', "EquiprqstExpenseController@make_contract_exe")->name('equiprqst_expenses.make_contract_exe');
Route::get('equiprqst_expenses/make_contract_exe/all', "EquiprqstExpenseController@make_all_contract_exes")->name('equiprqst_expenses.make_all_contract_exes');


//Показатели
Route::match(array('GET', 'POST'), 'indicators/search', "ViewparamController@search")->name("indicators.search");
Route::match(array('GET', 'POST'), '/indicators', "ViewparamController@index")->name('indicators.index');
//Route::get('/indicators/sort/{field}', 'ViewparamController@index_sort')->name('indicators.sort');
Route::get('indicators/create', "ViewparamController@create")->name('indicators.create');
Route::get('indicators/{id}/', 'ViewparamController@edit')->name('indicators.edit');
Route::match(array('POST', 'PUT'), 'indicators/{id}/upd', "ViewparamController@update")->name('indicators.update');
Route::put('indicators/{id}/delete', "ViewparamController@destroy")->name("indicators.delete");
//Route::put('indicators/{id}/admindelete', "ViewparamController@admindelete")->name("indicators.admindelete");
Route::get('indicators/0/recalcfacts', "ViewparamController@recalcfacts")->name('indicators.recalcfacts');

//Фактические значения показателей
Route::get('indi_facts/create/0/{paramid}/', "VpFactController@create")->name('indi_facts.create');
Route::get('indi_facts/{id}/', 'VpFactController@edit')->name('indi_facts.edit');
Route::match(array('POST', 'PUT'), 'indicators/{id}', "VpFactController@update")->name('indi_facts.update');
Route::put('indi_facts/{id}/delete', "VpFactController@destroy")->name("indi_facts.delete");

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
Route::get('mot_prices/{id}/edit', "MotPriceController@edit")->name('mot_prices.edit');
Route::match(array('POST', 'PUT'), 'mot_prices/{id}', "MotPriceController@update")->name('mot_prices.update');
Route::get('mot_prices/create/{mot_id}', "MotPriceController@create")->name('mot_prices.create');
Route::put('mot_prices/{id}/delete', "MotPriceController@destroy")->name("mot_prices.delete");
//----------------------------------------------------------------------------------------------------------------------


//Заявки на технику
Route::match(array('GET', 'POST'), 'mchnrqsts/search', "MchnrqstController@search")->name("mchnrqsts.search");
Route::match(array('GET', 'POST'), '/mchnrqsts', "MchnrqstController@index")->name('mchnrqsts.index');
Route::get('/mchnrqsts/sort/{field}', 'MchnrqstController@index_sort')->name('mchnrqsts.sort');

Route::get('mchnrqsts/create', "MchnrqstController@create")->name('mchnrqsts.create');
Route::get('mchnrqsts/edit/{id}', 'MchnrqstController@edit')->name('mchnrqsts.edit');
Route::match(array('POST', 'PUT'), 'mchnrqsts/{id}', "MchnrqstController@update")->name('mchnrqsts.update');
Route::put('mchnrqsts/{id}/delete', "MchnrqstController@destroy")->name("mchnrqsts.delete");
Route::put('mchnrqsts/{id}/admindelete', "MchnrqstController@admindelete")->name("mchnrqsts.admindelete");

Route::put('mchnrqsts/reg/{id}', "MchnrqstController@registrate")->name("mchnrqsts.registrate");
Route::get('mchnrqsts/unreg/{id}', 'MchnrqstController@unregistrate')->name('mchnrqsts.unregistrate');

Route::get('mchnrqsts/copy/{id}', "MchnrqstController@copy")->name("mchnrqsts.copy");

Route::match(array('POST', 'PUT'), 'mchnrqsts/approve/{id}', "MchnrqstController@approve")->name('mchnrqsts.approve');
Route::match(array('POST', 'PUT'), 'mchnrqsts/decline/{id}', "MchnrqstController@decline")->name('mchnrqsts.decline');
Route::match(array('POST', 'PUT'), 'mchnrqsts/cancel_dcsn/{id}', "MchnrqstController@cancel_dcsn")->name('mchnrqsts.cancel_dcsn');

Route::match(array('POST', 'PUT'), 'mchnrqsts/setfact/{id}', "MchnrqstController@setfact")->name('mchnrqsts.setfact');

//печать в форме заявки
Route::get('mchnrqsts/print1/{id}', 'MchnrqstController@printform1')->name('mchnrqsts.print1');
//печать в форме ЭСМ-7
//Route::get('mchnrqsts/print_esm_7/{id}', 'MchnrqstController@print_esm_7')->name('mchnrqsts.print_esm_7');
Route::match(array('POST', 'GET'), 'mchnrqsts/print_esm_7/{id}', 'MchnrqstController@print_esm_7')->name('mchnrqsts.print_esm_7');

//печать в форме Транспортной накладной
// между ГК Баско и УМТС
//Route::get('mchnrqsts/print_transp_nakl/{id}', 'MchnrqstController@print_transp_nakl')->name('mchnrqsts.print_transp_nakl');
Route::match(array('POST', 'GET'), '/mchnrqsts/print_transp_nakl/{id}', "MchnrqstController@print_transp_nakl")
    ->name('mchnrqsts.print_transp_nakl');
//между УМТС и ИП
//Route::get('mchnrqsts/print_transp_nakl_2/{id}', 'MchnrqstController@print_transp_nakl_2')->name('mchnrqsts.print_transp_nakl_2');
Route::match(array('POST', 'GET'), '/mchnrqsts/print_transp_nakl_2/{id}', "MchnrqstController@print_transp_nakl_2")
    ->name('mchnrqsts.print_transp_nakl_2');
//для Банка: между Заказчиком и ИП
//Route::get('mchnrqsts/print_transp_nakl_3/{id}', 'MchnrqstController@print_transp_nakl_3')->name('mchnrqsts.print_transp_nakl_3');
Route::match(array('POST', 'GET'), '/mchnrqsts/print_transp_nakl_3/{id}', "MchnrqstController@print_transp_nakl_3")
    ->name('mchnrqsts.print_transp_nakl_3');

Route::get('/cnt/cntActiveMchnRqsts', function () {
    return UserAct::cntActiveMchnRqsts();
});

//Учет фактического времени работы -------------------------------------------------------------------------------------
Route::get('mchnrqst_facts/create/{rqstid}/', "MchnrqstFactController@create")->name('mchnrqst_facts.create');
Route::get('mchnrqst_facts/{id}/', 'MchnrqstFactController@edit')->name('mchnrqst_facts.edit');
Route::match(array('POST', 'PUT'), 'mchnrqst_facts/{id}', "MchnrqstFactController@update")->name('mchnrqst_facts.update');
Route::put('mchnrqst_facts/{id}/delete', "MchnrqstFactController@destroy")->name("mchnrqst_facts.delete");
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


//организации, управляющие техникой
Route::get('mchncontrorgs/create', "MchncontrorgController@create")->name('mchncontrorgs.create');
Route::get('mchncontrorgs/edit/{id}', 'MchncontrorgController@edit')->name('mchncontrorgs.edit');
Route::match(array('POST', 'PUT'), 'mchncontrorgs/{id}', "MchncontrorgController@update")->name('mchncontrorgs.update');
Route::put('mchncontrorgs/{id}/delete', "MchncontrorgController@destroy")->name("mchncontrorgs.delete");


//Фото для продуктов
Route::get('ri_img/{refitmid}', 'RiImageController@create')->name('ri_images.load');
Route::post('ri_img', 'RiImageController@store')->name('ri_image.upload');
Route::get('ri_img/delete/{id}', 'RiImageController@destroy')->name('ri_img.destroy');

//Оценка текущих цен на материалы
Route::get('ri_estprices/create/{refitmid}', 'RiEstpriceController@create')->name('ri_estprices.create');
Route::get('ri_estprices/edit/{id}', 'RiEstpriceController@edit')->name('ri_estprices.edit');
Route::match(array('POST', 'PUT'), 'ri_estprices/{id}', "RiEstpriceController@update")->name('ri_estprices.update');
Route::get('ri_estprices/delete/{id}', 'RiEstpriceController@destroy')->name('ri_estprices.delete');
Route::get('/ri_estprices/addfromoffers', 'RiEstpriceController@addfromoffers')->name('ri_estprices.addfromoffers');

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

//Справочник Базовые типы работ
Route::match(array('GET', 'POST'), '/baseworktypes', 'BaseworktypeController@index')->name('baseworktypes.index');
Route::get('/baseworktypes/create', 'BaseworktypeController@create')->name('baseworktypes.create');
Route::get('/baseworktypes/{id}/edit', "BaseworktypeController@edit")->name('baseworktypes.edit');
Route::match(array('POST', 'PUT'), 'baseworktypes/update/{id}', "BaseworktypeController@update")->name('baseworktypes.update');
Route::put('/baseworktypes/{id}/delete', "BaseworktypeController@destroy")->name("baseworktypes.delete");
Route::get('/baseworktypes/info/params/', 'BaseworktypeController@info_params');

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

Route::get('refitems/{id}', 'refItemController@edit')->name('refitems.edit');
Route::match(array('POST', 'PUT'), 'refitems/{id}', "refItemController@update")
    ->name('refitems.update');
Route::put('refitems/{id}/delete', "refItemController@destroy")->name("refitems.del");
Route::put('refitems/{id}/admindelete', "refItemController@admindelete")->name("refitems.admindelete");

Route::get('refitems/{orditemid}/assemble', 'refItemController@assembleItem')->name('refitems.assembleitem');

Route::get('refitems/fill_ordr', 'refItemController@fillOrdr')->name('refitems.fillordr');

Route::get('refitems/{srcid}/{tgtid}/join', 'refItemController@join')->name('refitems.join');

//Цены поставщиков товаров (идем от поставщика)
Route::get('ri_org_prices/{id}/edit', "RiOrgPriceController@edit")->name('ri_org_prices.edit');
Route::get('ri_org_prices/{orgid}/create', "RiOrgPriceController@create")->name('ri_org_prices.create');
Route::match(array('POST', 'PUT'), 'ri_org_prices/{id}', "RiOrgPriceController@update")->name('ri_org_prices.update');
Route::put('ri_org_prices/{id}/delete', "RiOrgPriceController@destroy")->name("ri_org_prices.delete");



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
//Route::get('/buildobjs/{buildobjid}/buildopertypes/params/', 'BuildobjController@listbuildopertypes');
Route::get('/buildobjs/buildopertypes/params/', 'BuildobjController@listbuildopertypes');
Route::get('/buildobjs/m15_src_tgt/params/', 'BuildobjController@listbuildobjs_m15srctgt');
Route::get('/api/buildobjs/budget_owner/', 'BuildobjController@listbuildobjs_budget_owner');
Route::get('/api/buildobjs/for_', 'BuildobjController@list_for');
Route::get('/api/buildobjs/get_item', 'BuildobjController@get_item');
Route::get('/api/orgs/m15_tgt/', 'orgController@listorgs_m15tgt');
Route::get('/api/orgs/for_/', 'orgController@list_for');
Route::get('/api/orgs/for_ac/', 'orgController@list_for_ac');
Route::get('/api/orgdeps/for_/', 'OrgdepController@list_for');
Route::get('/api/orgposts/for_/', 'OrgpostController@list_for');
Route::get('/api/orgposts/stdlimunits', 'OrgpostController@stdlimunits');
Route::get('/api/orgposts/dep_posts', 'OrgpostController@dep_posts');
Route::get('/api/buildopertypes/for_budget', 'BuildopertypeController@listbuildopertypes_forbudget');
Route::get('/api/buildopertypes/for_', 'BuildopertypeController@listbuildopertypes_for');
Route::get('/api/buildopertypes/m15', 'BuildopertypeController@listbuildopertypes_m15');
Route::get('/buildobjs/contracts/params/', 'BuildobjController@listcontracts');
Route::get('/api/contracts/params/', 'ContractController@listcontracts');
Route::get('/api/contracts/buildopertypeid/', 'ContractController@list_for_buildopertypeid');
Route::get('/api/contracts/for_/', 'ContractController@list_for');
Route::get('/api/contractroles/typeid/', 'ContractroleController@list_for_contracttypeid');
Route::get('/api/regnum_srcs/', 'RegnumSrcController@list_for');
Route::get('/api/budgets/for_/', 'BudgetController@list_for');
Route::get('/api/budgetitms/org/buildobj/', 'BudgetItemController@items4org_buildobj');
Route::get('/buildobjs/staff/params/', 'BuildobjController@listorgstaff');
Route::get('/api/refitems/for_ac/', 'refItemController@list_for_ac');
Route::get('/api/machines/for_/', 'MachineController@list_for');
Route::get('/api/machines/for_ac/', 'MachineController@list_for_ac');
Route::get('api/buildobj_staffs/_ac', 'BuildobjStaffController@list_for_ac');
Route::get('/api/mchn_opertypes/for_/', 'MchnOpertypeController@list_for');
Route::get('/api/mchn_raids/data_for_driver_works/', 'MchnRaidController@data_for_driver_works');
Route::get('/orgstaff/staff/params/', 'orgstaffController@listorgstaff');
Route::get('/orgstaff/fio_name/params/', 'orgstaffController@liststafffio');
Route::get('/buildobjs/buildobjstaff/params/', 'BuildobjController@listbuildobjstaff');
Route::get('/orgs/info/params/', 'orgController@info_params');
Route::get('/orgs/addrs/params/', 'OrgPlaceController@addrs_params');
Route::get('/projects/buildobjs/params/', 'ProjectController@listbuildobjs');
Route::get('/api/invoices/child_docs', 'InvoiceController@list_child_docs');
Route::get('/api/equiprqst/upd/bdgtitmsums', 'InvoiceController@list_bdgtitmsums_by_er_upd');
Route::get('/api/invoice_items/items', 'InvoiceItemController@list_items');
Route::get('/api/invoice_items/item', 'InvoiceItemController@get_item');
Route::get('/api/wrhs/for_/', 'WrhController@list_for');
Route::get('/api/wrh_boxes/for_/', 'WrhBoxController@list_for');
Route::get('/stock/wrhdoctypes/params', 'WrhdoctypeController@params');
Route::get('/api/budget_itmsums/rest', 'BudgetItmsumController@rest_info');
Route::get('/api/doctypes/ac_/', 'DoctypeController@get_for');
Route::get('/api/doctypes/params/', 'DoctypeController@list_for');
Route::get('/api/orgstaff/ac_/', 'orgstaffController@get_for');
Route::get('/api/booking/by_id', 'BookingController@rest_info');
Route::get('/api/booking/resobj_ts', 'BookingController@resobj_ts');
Route::get('/api/ocl_items/params/', 'OclItemController@list_for');


Route::get('/api/stat/day30qchecks', 'QcheckController@day30qchecks');
Route::get('/api/stat/dayacntrests', 'OrgacntSumController@days30acntrests');


//Бюджеты
Route::match(array('GET', 'POST'), '/budgets/', "BudgetController@index")->name('budgets.index');
Route::get('budgets/create', "BudgetController@create")->name('budgets.create');
Route::get('budgets/{id}', 'BudgetController@edit')->name('budgets.edit');
Route::match(array('POST', 'PUT'), 'budgets/{id}', "BudgetController@update")->name('budgets.update');
Route::put('budgets/{id}/delete', "BudgetController@destroy")->name("budgets.delete");

Route::match(array('POST', 'GET'), 'budgets/grid/{budgetid}', "BudgetController@grid")->name('budgets.grid');
Route::get('budgets/make_child/{parid}', 'BudgetController@make_child')->name('budgets.make_child');

//печатные формы
Route::get('budgets/{id}/print/1', 'BudgetController@print_1')->name('budgets.print_1');

//Состав бюджета
Route::get('budget_items/create/{budgetid}/{parid}', "BudgetItemController@create")->name('budget_items.create');
Route::get('budget_items/edit/{id}', 'BudgetItemController@edit')->name('budget_items.edit');
Route::match(array('POST', 'PUT'), 'budget_items.edit/{id}', "BudgetItemController@update")->name('budget_items.update');
Route::put('budget_items.edit/{id}/delete', "BudgetItemController@destroy")->name("budget_items.delete");
Route::put('budget_items/{id}/admindelete', "BudgetItemController@admindelete")->name("budget_items.admindelete");
Route::get('budget_items/createbybuildobj/{budgetid}', "BudgetItemController@create_by_buildobjid")->name('budget_items.createbybuildobj');

Route::match(array('POST', 'PUT'), 'budget_items.fill/{id}', "BudgetItemController@fillfromparitm")->name('budget_items.fillfromparitm');

Route::get('budget_items/data/{parid}/{typeid}', "BudgetItemController@edit_by_parid_typeid")->name('budget_items.by_parid_typeid');

//суммы бюджета
Route::get('budget_itmsums/create/{budgetid}/{itmid}', "BudgetItmsumController@create")->name('budget_itmsums.create');
Route::get('budget_itmsums/edit/{id}', 'BudgetItmsumController@edit')->name('budget_itmsums.edit');
Route::match(array('POST', 'PUT'), 'budget_itmsums.edit/{id}', "BudgetItmsumController@update")->name('budget_itmsums.update');
Route::put('budget_itmsums.edit/{id}/delete', "BudgetItmsumController@destroy")->name("budget_itmsums.delete");

Route::get('budget_itmsums/unlinkchilds/{id}', 'BudgetItmsumController@unlink_childs')->name('budget_itmsums.unlink_childs');
Route::get('budget_itmsums/getPln2Fct/{id}', 'BudgetItmsumController@getFullPln2Fct')->name('budget_itmsums.getPln2Fct');

//факт бюджета
Route::get('budget_facts/create/{budgetid}/{parid}', "BudgetFactController@create")->name('budget_facts.create');
Route::get('budget_facts/edit/{id}', 'BudgetFactController@edit')->name('budget_facts.edit');
Route::match(array('POST', 'PUT'), 'budget_facts.edit/{id}', "BudgetFactController@update")->name('budget_facts.update');
Route::put('budget_facts.edit/{id}/delete', "BudgetFactController@destroy")->name("budget_facts.delete");


//Операции по бюджету
Route::get('budget_opers/create/{itmid}', "BudgetOperController@create")->name('budget_opers.create');
Route::get('budget_opers/edit/{id}', 'BudgetOperController@edit')->name('budget_opers.edit');
Route::match(array('POST', 'PUT'), 'budget_opers.edit/{id}', "BudgetOperController@update")->name('budget_opers.update');
Route::put('budget_opers.edit/{id}/delete', "BudgetOperController@destroy")->name("budget_opers.delete");


//Текущие остатки средств на р/счетах
Route::match(array('GET', 'POST'), 'orgacnt_sums', "OrgacntSumController@index")->name("orgacnt_sums.index");
Route::get('orgacnt_sums/create', "OrgacntSumController@create")->name('orgacnt_sums.create');
Route::get('orgacnt_sums/{id}/edit', "OrgacntSumController@edit")->name('orgacnt_sums.edit');
Route::match(array('POST', 'PUT'), 'orgacnt_sums/{id}', "OrgacntSumController@update")->name('orgacnt_sums.update');
Route::put('orgacnt_sums/{id}/delete', "OrgacntSumController@destroy")->name("orgacnt_sums.delete");
Route::get('orgacnt_sums/today_print', "OrgacntSumController@today_print")->name('orgacnt_sums.today_print');
Route::get('orgacnt_sums/today_print_all', "OrgacntSumController@today_print_all")->name('orgacnt_sums.today_print_all');

//Внешние ИС, связанные с организациями
Route::match(array('GET', 'POST'), 'org_extservices', "OrgExtserviceController@index")->name("org_extservices.index");
Route::get('org_extservices/create', "OrgExtserviceController@create")->name('org_extservices.create');
Route::get('org_extservices/{id}/edit', "OrgExtserviceController@edit")->name('org_extservices.edit');
Route::match(array('POST', 'PUT'), 'org_extservices/{id}', "OrgExtserviceController@update")->name('org_extservices.update');
Route::put('org_extservices/{id}/delete', "OrgExtserviceController@destroy")->name("org_extservices.delete");
Route::get('org_extservices/refresh/{extsysid}', "OrgExtserviceController@refresh")->name('org_extservices.refresh');
Route::get('org_extservices/upddaysums/{srvcid}', "OrgExtserviceController@upddaysums")->name('org_extservices.upddaysums');

//Текущие остатки средств на суб-счетах провайдеров услуг
Route::match(array('GET', 'POST'), 'extsrvc_sums', "ExtsrvcSumController@index")->name("extsrvc_sums.index");
Route::get('extsrvc_sums/create', "ExtsrvcSumController@create")->name('extsrvc_sums.create');
Route::get('extsrvc_sums/{id}/edit', "ExtsrvcSumController@edit")->name('extsrvc_sums.edit');
Route::match(array('POST', 'PUT'), 'extsrvc_sums/{id}', "ExtsrvcSumController@update")->name('extsrvc_sums.update');
Route::put('extsrvc_sums/{id}/delete', "ExtsrvcSumController@destroy")->name("extsrvc_sums.delete");
Route::get('extsrvc_sums/refresh/{extsysid}', "ExtsrvcSumController@refresh")->name('extsrvc_sums.refresh');

//Реестр счетов на оплату
Route::match(array('GET', 'POST'), 'invoices', "InvoiceController@index")->name("invoices.index");
Route::get('invoices/create/{pardocid}', "InvoiceController@create")->name('invoices.create');
//загрузка счета из файла в формате XLS
Route::get('invoices/load/', "InvoiceController@load")->name('invoices.load');
Route::put('invoices/import/', "InvoiceController@import")->name('invoices.import');

Route::get('invoices/{id}/edit', "InvoiceController@edit")->name('invoices.edit');
Route::match(array('POST', 'PUT'), 'invoices/{id}', "InvoiceController@update")->name('invoices.update');
Route::put('invoices/{id}/delete', "InvoiceController@destroy")->name("invoices.delete");

Route::get('invoices/{id}/send2pay', "InvoiceController@send2pay")->name('invoices.send2pay');
Route::get('invoices/{id}/create_child', "InvoiceController@create_child")->name('invoices.create_child');
Route::get('invoices/{id}/create_child_upd', "InvoiceController@create_child_upd")->name('invoices.create_child_upd');

Route::match(array('POST', 'PUT'), 'invoices/{id}/upd_upd_items', "InvoiceController@update_upd_items")->name('invoices.update_upd_items');

Route::match(array('POST', 'PUT'), 'invoices/{id}/upd_enddate', "InvoiceController@update_enddate")->name('invoices.update_enddate');

Route::get('invoices/export/', "InvoiceController@export")->name('invoices.export');

Route::get('invoices/{id}/make_contract_exe', "InvoiceController@make_contract_exe")->name('invoices.make_contract_exe');
Route::get('invoices/make_contract_exe/all', "InvoiceController@make_all_contract_exes")->name('invoices.make_all_contract_exes');

//создание заявки по счету с позициями
Route::get('invoices/{id}/make_equiprqst', "InvoiceController@make_equiprqst")->name('invoices.make_equiprqst');

//Состав счета
Route::get('invoice_items/create/{docid}/', "InvoiceItemController@create")->name('invoice_items.create');
Route::get('invoice_items/{id}/', 'InvoiceItemController@edit')->name('invoice_items.edit');
Route::match(array('POST', 'PUT'), 'invoice_items/{id}', "InvoiceItemController@update")->name('invoice_items.update');
Route::put('invoice_items/{id}/delete', "InvoiceItemController@destroy")->name("invoice_items.delete");
//добавление материала из состава счета
Route::get('invoice_items/{id}/add2refitems', 'InvoiceItemController@add2refitems')->name('invoice_items.add2refitems');


//отчеты по счетам/УПД
Route::match(array('POST', 'GET'), '/reports/rep/24', "InvoiceReportController@rep24")->name('reports.rep24');
Route::match(array('POST', 'GET'), '/reports/rep/25', "InvoiceReportController@rep25")->name('reports.rep25');
Route::match(array('POST', 'GET'), '/reports/rep/34', "InvoiceReportController@rep34")->name('reports.rep34');
Route::match(array('POST', 'GET'), '/reports/rep/36', "InvoiceReportController@rep36")->name('reports.rep36');
Route::match(array('POST', 'GET'), '/reports/rep/38', "InvoiceReportController@rep38")->name('reports.rep38');

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


//Документы
Route::match(array('GET', 'POST'), 'docs_101', "Doc101Controller@index")->name("docs101.index");

Route::get('docs101/{id}/edit', "Doc101Controller@edit")->name('docs101.edit');
Route::match(array('POST', 'PUT'), 'docs101/{id}', "Doc101Controller@update")->name('docs101.update');
Route::get('docs101/create', "Doc101Controller@create")->name('docs101.create');
Route::put('docs101/{id}/delete', "Doc101Controller@destroy")->name("docs101.delete");

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

//Документы М15
Route::match(array('GET', 'POST'), 'm15docs', "M15docController@index")->name("m15docs.index");

Route::get('m15docs/{id}/edit', "M15docController@edit")->name('m15docs.edit');
Route::match(array('POST', 'PUT'), 'm15docs/{id}', "M15docController@update")->name('m15docs.update');
Route::get('m15docs/create', "M15docController@create")->name('m15docs.create');
Route::put('m15docs/{id}/delete', "M15docController@destroy")->name("m15docs.delete");

Route::match(array('POST', 'PUT'), 'm15docs/{id}/add_items', "M15docController@add_items")->name('m15docs.add_items');

//Доверенности организаций холдинга
Route::match(array('GET', 'POST'), 'grantdocs', "GrantdocController@index")->name("grantdocs.index");
Route::get('grantdocs/create', "GrantdocController@create")->name('grantdocs.create');
Route::get('grantdocs/{id}/edit', "GrantdocController@edit")->name('grantdocs.edit');
Route::match(array('POST', 'PUT'), 'grantdocs/{id}/upd', "GrantdocController@update")->name('grantdocs.update');
Route::get('grantdocs/{id}/delete', "GrantdocController@destroy")->name("grantdocs.delete");
Route::get('grantdocs/{id}/make_template', "GrantdocController@make_template")->name('grantdocs.make_template');

//Входящие письма по проектам
Route::get('/proj_mails/check', "ProjMailController@check")->name("proj_mails.check");

Route::match(array('GET', 'POST'), 'proj_mails', "ProjMailController@index")->name("proj_mails.index");
//Route::get('grantdocs/create', "ProjMailController@create")->name('proj_mails.create');
Route::get('proj_mails/{id}/edit', "ProjMailController@edit")->name('proj_mails.edit');
Route::match(array('POST', 'PUT'), 'grantdocs/{id}', "ProjMailController@update")->name('proj_mails.update');
Route::get('proj_mails/{id}/delete', "ProjMailController@destroy")->name("proj_mails.delete");


//Учетные периоды организации
Route::match(array('GET', 'POST'), '/orgacntperiods', "OrgacntperiodController@index")->name("orgacntperiods.index");
Route::get('orgacntperiods/create', "OrgacntperiodController@create")->name('orgacntperiods.create');
Route::get('orgacntperiods/{id}/edit', "OrgacntperiodController@edit")->name('orgacntperiods.edit');
Route::match(array('POST', 'PUT'), 'orgacntperiods/{id}', "OrgacntperiodController@update")->name('orgacntperiods.update');
Route::put('orgacntperiods/{id}/delete', "OrgacntperiodController@destroy")->name("orgacntperiods.delete");

//уведомления пользователей
Route::get('user_notices/{id}/delete/{route}', "UserNoticeController@destroy")->name("user_notices.delete");


Route::get('/gantt/{sysobjid}/{objid}', function () {
    return view('gantt.gantt');
})->name('gantt');

Route::get('/tst1', function () {
    return view('test.tst1');
});
Route::get('/tst2', function () {
    return view('test.tst2');
});

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

//Аналитические отчеты
Route::match(array('POST', 'PUT'), '/reports/rep/32/set', "AnaliticsController@rep32setparams")->name('reports.rep32set');
Route::match(array('POST', 'GET'), '/reports/rep/32', "AnaliticsController@rep32")->name('reports.rep32');

Route::match(array('POST', 'PUT'), '/reports/rep/45/set', "AnaliticsController@rep45setparams")->name('reports.rep45set');
Route::match(array('POST', 'GET'), '/reports/rep/45', "AnaliticsController@rep45")->name('reports.rep45');


Route::get('/selectable000', function () {
    return view('selectable');
});

//Route::get('/calendar', function () {
//    return view('calendar.index');
//});

Route::match(array('GET', 'POST'), 'events_index', "EventController@index")->name("events.index");

Route::get('events/{id}/edit', "EventController@edit")->name('events.edit');
Route::match(array('POST', 'PUT'), 'events/{id}', "EventController@update")->name('events.update');
Route::get('events/create/{sysobjid}/{objid}', "EventController@create")->name('events.create');
Route::put('events/{id}/delete', "EventController@destroy")->name("events.delete");

// сформировать уведомления по событиям
Route::get('events/notify', "EventController@notify")->name('events.notify');


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
Route::put('wrhdocs/{id}/sign', "WrhdocController@sign")->name("wrhdocs.sign");
Route::put('wrhdocs/{id}/unsign', "WrhdocController@unsign")->name("wrhdocs.unsign");
Route::get('wrhdocs/{id}/makediffdoc', "WrhdocController@make_diffdoc")->name("wrhdocs.make_diffdoc");

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

// Информеры -----------------------------------------------------------------------------------------------------------
Route::match(array('GET', 'POST'), '/informers/', "InformerController@index")->name('informers.index');
Route::get('informers/create', "InformerController@create")->name('informers.create');
Route::get('informers/{id}', 'InformerController@edit')->name('informers.edit');
Route::match(array('POST', 'PUT'), 'informers/{id}', "InformerController@update")->name('informers.update');
Route::put('informers/{id}/delete', "InformerController@destroy")->name("informers.delete");

// Источники регистрационных номеров (Нумераторы) ----------------------------------------------------------------------
Route::match(array('GET', 'POST'), '/regnum_srcs/', "RegnumSrcController@index")->name('regnum_srcs.index');
Route::get('regnum_srcs/create', "RegnumSrcController@create")->name('regnum_srcs.create');
Route::get('regnum_srcs/{id}', 'RegnumSrcController@edit')->name('regnum_srcs.edit');
Route::match(array('POST', 'PUT'), 'regnum_srcs/{id}', "RegnumSrcController@update")->name('regnum_srcs.update');
Route::put('regnum_srcs/{id}/delete', "RegnumSrcController@destroy")->name("regnum_srcs.delete");


//collectors - Справочник коллекционеров/типов подборок (документов)
Route::get('/collectors/create', "CollectorController@create")->name('collectors.create');
Route::match(array('GET', 'POST'), '/collectors', "CollectorController@index")->name("collectors.index");
Route::get('collectors/{id}', 'CollectorController@edit')->name('collectors.edit');
Route::match(array('POST', 'PUT'), 'collectors/{id}', "CollectorController@update")
    ->name('collectors.update');
Route::put('collectors/{id}/delete', "CollectorController@destroy")->name("collectors.delete");
//Route::put('collectors/{id}/admindelete', "CollectorController@admindelete")->name("collectors.admindelete");


//dt_collectors - Интерес коллекционеров к типам документов
Route::get('/dt_collectors/create/{collectorid}', "DtCollectorController@create")->name('dt_collectors.create');
Route::get('dt_collectors/{id}', 'DtCollectorController@edit')->name('dt_collectors.edit');
Route::match(array('POST', 'PUT'), 'dt_collectors/{id}', "DtCollectorController@update")
    ->name('dt_collectors.update');
Route::put('dt_collectors/{id}/delete', "DtCollectorController@destroy")->name("dt_collectors.delete");


Route::get('booking/day/{id}', "BookingController@edit")->name('booking.day');
Route::get('/booking/get', 'BookingController@get');
Route::post('/booking/create', 'BookingController@create');
Route::post('/booking/update', 'BookingController@update');
Route::post('/booking/move', 'BookingController@move');
Route::post('/booking/delete', 'BookingController@destroy');

Route::get('/isotope', 'HomeController@isotope');
