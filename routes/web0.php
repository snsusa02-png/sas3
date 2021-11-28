<?php

use Illuminate\Support\Facades\Route;

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

//Route::get('/', function () {
//    return view('welcome');
//});
Route::get('/', 'HomeController@welcome')->name('welcome');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/admin', 'AdminController@index')->name('admin');

Route::get('/rqsts', function () {
    return view('rqsts.index');
})->middleware('auth')->name('rqsts');

Route::get('/office', function () {
    return view('office.index');
})->middleware('auth')->name('office');

Route::get('/planning', function () {
    return view('planning.index');
})->middleware('auth')->name('planning');

Route::get('/rprts', function () {
    return view('reports.pub_index');
    //return redirect('/admin#nsi-rep');
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



//Отчеты
Route::get('reports/create', "ReportController@create")->name('reports.create');
Route::match(array('GET', 'POST'), '/admin/reports', "ReportController@index")->name("reports.index");
Route::get('reports/edit/{id}', 'ReportController@edit')->name('reports.edit');
Route::match(array('POST', 'PUT'), 'reports.edit/{id}', "ReportController@update")->name('reports.update');
Route::put('reports/del/{id}/delete', "ReportController@destroy")->name("reports.delete");

Route::match(array('GET', 'POST'), '/reports', "ReportController@pub_index")->name("reports.pub_index");

//Телефонный и почтовый справочник
Route::match(array('GET', 'POST'), 'contacts', 'orgContactController@index')->name('orgcontacts.index');

//Смена текущей представляемой пользователем организации
Route::get('/setuserorg/{orgid}', "UserManage@setCurOrgID")->name('usersetcurorg');

Route::get('userorgs/{id}', 'UserorgController@edit')->name('userorgs.edit');
Route::get('userorgs/create/{userid}', 'UserorgController@create')->name('userorgs.create');
Route::match(array('POST', 'PUT'), 'userorgs/{id}', "UserorgController@update")
    ->name('userorgs.update');
Route::put('userorgs/{id}/delete', "UserorgController@destroy")->name("userorgs.delete");


Route::get('orgs/org_groups/{orgid}', "orgController@org_groups_edit")->name('org_groups.edit');
Route::match(array('POST', 'PUT'), 'orgs/org_groups//{orgid}', "orgController@org_groups_update")
    ->name('org_groups.update');


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

Route::get('orgs/org_users/{orgid}', "orgController@org_users")->name('org_users.index');
Route::get('orgs/org_staff/{orgid}', "orgController@org_staff")->name('org_staff.index');
Route::get('orgs/org_curators/{orgid}', "orgController@org_curators")->name('org_curators.index');
Route::get('orgs/org_supoffers/{orgid}', "orgController@org_supoffers")->name('org_supoffers.index');
Route::get('orgs/org_extids/{orgid}', "orgController@org_extids")->name('org_extids.index');

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


//
Route::get('org_places/{id}/edit', "OrgPlaceController@edit")->name('org_places.edit');
Route::get('org_places/{orgid}/create', "OrgPlaceController@create")->name('org_places.create');
Route::match(array('POST', 'PUT'), 'org_places/{id}', "OrgPlaceController@update")->name('org_places.update');
Route::put('org_places/{id}/delete', "OrgPlaceController@destroy")->name("org_places.delete");


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


