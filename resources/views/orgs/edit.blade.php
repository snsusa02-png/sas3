@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('orgs.index');
        header("Location:" . route('orgs.index'));
        die();
        ?>
    @else

        <script src="{{ asset('js/collapse.js') }}" defer></script>

        <?php
        $sysobjid = 111;
        $objcode = 'orgs';
        $ThisTitle = "Контрагент";

        $route_index = route($objcode . '.index') . "?page=" . session($objcode . '_pageno') . '#' . $rec->id;
        ?>
        {{--dd(get_defined_vars())--}}
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .org-aux {
                width: 100%
            }
        </style>
        <div class="container">

            @includeIf('layouts.edit_msgs')

            <div class="row ">
                <div class="col-md-6">
                    <div class="card p-2 my-2 my-md-3">
                        <div class="card-header">
                            Контрагент
                            <a class="btn btn-close btn-light btn-sm"
                               style="float:right;"
                               href="{{ $route_index }}"
                               title="Вернуться в список клиентов">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>
                        </div>

                        <div class="card-body">

                            {{--                            @include('layouts.err_msgs')--}}

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route('orgs.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('ttt', 1) }}
                                {{ Form::hidden('id', $rec->id) }}
                                <input type="hidden" name="isownorg" value="{{$rec->isownorg?1:0}}">


                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-toggle="tab" href="#home">Основное</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link hk2 hk3" data-toggle="tab" href="#menu1">Руководство</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link hk3" data-toggle="tab" href="#menu2">Описание</a>
                                    </li>
                                    @if($usrrights['private_acs']??false)
                                    @endif
                                </ul>


                                <!-- Tab panes -->
                                <div class="tab-content">

                                    @include('layouts.err_msgs')

                                    <div id="home" class="container tab-pane active"><br>


                                        <div class="row">
                                            <div class="offset-md-0 col-md-3">
                                                <div class="form-group">
                                                    <label for="kindid" class="small">Тип:</label>
                                                    {!! Form::select('kindid', $rec->kinds ,$rec->kindid,
                                                    ['class' => 'form-control small',
                                                    'id' => 'kindid',
                                                    'placeholder' => '',
                                                    ]) !!}
                                                </div>
                                            </div>

                                            <div class="form-group col-md-9 ">
                                                <label for="name" id="lbl_name">Название:</label>
                                                <input type="text" class="form-control font-weight-bold"
                                                       name="name" id="name"
                                                       value="{{old('name',$rec->name)}}"/>
                                            </div>

                                        </div>

                                        <div class="form-group hk2 hk3">
                                            <label for="fullname">Полное название:</label>
                                            <textarea class="form-control" rows="2"
                                                      name="fullname"
                                            >{{old('fullname',$rec->fullname)}}</textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="inn">ИНН:</label>
                                                    <input type="text" class="form-control" name="inn"
                                                           value="{{old('inn',$rec->inn)}}"/>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group hk2 hk3">
                                                    <label for="kpp">КПП:</label>
                                                    <input type="text" class="form-control" name="kpp"
                                                           value="{{old('kpp',$rec->kpp)}}"/>
                                                </div>
                                            </div>

                                            <div class="offset-md-0 col-md-4 hk2 hk3">
                                                <div class="form-group">
                                                    <label for="inn" class="small">Система налог. учета:</label>
                                                    {!! Form::select('taxsysid', $rec->taxsystems ,$rec->taxsysid,
                                                    ['class' => 'form-control',
                                                    'placeholder' => '',
                                                    ]) !!}
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group hk2 hk3">
                                                    <label for="inn">ОКПО:</label>
                                                    <input type="text" class="form-control" name="okpo" maxlength="8"
                                                           value="{{old('okpo',$rec->okpo)}}"/>
                                                </div>
                                            </div>

                                            <div class="col-md-4 hk2 hk3">
                                                <div class="form-group">
                                                    <label for="ogrn"
                                                           title="Основной государственный регистрационный номер">ОГРН:</label>
                                                    <input type="text" class="form-control" name="ogrn" maxlength="13"
                                                           value="{{old('ogrn',$rec->ogrn)}}"/>
                                                </div>
                                            </div>

                                            <div class="col-md-4 hk1 hk3">
                                                <div class="form-group">
                                                    <label for="ogrnip"
                                                           title="Основной государственный регистрационный номер индивидуального предпринимателя">ОГРНИП:</label>
                                                    <input type="text" class="form-control" name="ogrnip" maxlength="15"
                                                           value="{{old('ogrnip',$rec->ogrnip)}}"/>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row">
                                            <div class="form-group col-md-12">
                                                <label for="address" id="lbl_address">Адрес:</label>
                                                <textarea class="form-control" rows="2"
                                                          name="address"
                                                >{{old('address',$rec->address)}}</textarea>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group offset-md-0 col-md-5">
                                                <label for="phone">Телефон:</label>
                                                <input type="text" class="form-control" name="phone"
                                                       value="{{old('phone',$rec->phone)}}"/>
                                            </div>
                                            <div class="form-group offset-md-0 col-md-7">
                                                <label for="phone">e-mail:</label>
                                                <input type="text" class="form-control" name="email"
                                                       value="{{old('email',$rec->email)}}"/>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="form-group col-md-12 hk1 hk2">
                                                <label for="iddoc_info" id="lbl_iddoc">Паспортные данные:</label>
                                                <textarea class="form-control" rows="2" maxlength="160"
                                                          name="iddoc_info"
                                                >{{old('address',$rec->iddoc_info)}}</textarea>
                                            </div>
                                        </div>

                                    </div>


                                    <div id="menu1" class="container tab-pane fade"><br>

                                        <div class="p-2 mb-2" style="background-color: #d7f3e3; border-radius: 4px">
                                            <label class="font-weight-bold">Руководитель организации
                                                @if(isset($rec->boss_staffid))
                                                    <a href="{{route("orgstaff.edit",$rec->boss_staffid)}}"
                                                       target="_blank"><i class="fa fa-external-link-square text-info"
                                                                          aria-hidden="true"></i></a>
                                                @endif
                                            </label>
                                            <div class="row">
                                                <div class="col-md-7">
                                                    <div class="form-group">
                                                        <label for="boss_postname">Должность:</label>
                                                        <input type="text" class="form-control" name="boss_postname"
                                                               value="{{old('boss_postname',$rec->boss_postname)}}"/>
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <label for="boss_name">Фамилия И.О.:</label>
                                                        <input type="text" class="form-control" name="boss_name"
                                                               value="{{old('boss_name',$rec->boss_name)}}"/>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="boss_fullname">Фамилия Имя Отчество:</label>
                                                        <input type="text" class="form-control" name="boss_fullname"
                                                               value="{{old('boss_fullname',$rec->boss_fullname)}}"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="p-2 mb-2" style="background-color: #d2d5f3; border-radius: 4px">
                                            <label class="font-weight-bold">Главный бухгалтер организации
                                                @if(isset($rec->ca_staffid))
                                                    <a href="{{route("orgstaff.edit",$rec->ca_staffid)}}"
                                                       target="_blank"><i class="fa fa-external-link-square text-info"
                                                                          aria-hidden="true"></i></a>
                                                @endif
                                            </label>
                                            <div class="row">
                                                <div class="col-md-7">
                                                    <div class="form-group">
                                                        <label for="ca_postname">Должность:</label>
                                                        <input type="text" class="form-control" name="ca_postname"
                                                               value="{{old('ca_postname',$rec->ca_postname)}}"/>
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <label for="ca_name">Фамилия И.О.:</label>
                                                        <input type="text" class="form-control" name="ca_name"
                                                               value="{{old('ca_name',$rec->ca_name)}}"/>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="ca_fullname">Фамилия Имя Отчество:</label>
                                                        <input type="text" class="form-control" name="ca_fullname"
                                                               value="{{old('ca_fullname',$rec->ca_fullname)}}"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div id="menu2" class="container tab-pane fade"><br>

                                        <div class="p-2 mb-2" style="background-color: #e6faff; border-radius: 4px">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="bank_account_info" class="font-weight-bold">Основной
                                                            вид
                                                            деятельности:</label>
                                                        <textarea class="form-control" rows="5"
                                                                  name="main_activity"
                                                        >{{old('main_activity',$rec->main_activity)}}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        @if(isset($rec->flags))
                                            <?php
                                            $show = ($rec->id == -1) ? 'show' : '';

                                            ?>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="boss_fullname">Особенности организации:</label>
                                                        <button data-toggle="collapse" data-target="#orgflaglist"
                                                                type="button"
                                                                class="btn btn-light btn-sm"><i
                                                                    class="fa fa-eye-slash"
                                                                    aria-hidden="true"></i>
                                                        </button>

                                                        <ul class="collapse {{$show}}" id="orgflaglist">
                                                            <?php
                                                            $lstFlags = '';
                                                            ?>
                                                            @foreach($rec->flags as $flag)
                                                                <li><label><input type="checkbox" class=""
                                                                                  name="flagid[{{$flag->id}}]"
                                                                                {{(isset($flag->objflagid))?'checked':''}}/>&nbsp;{{$flag->name}}
                                                                    </label></li>
                                                                @php($lstFlags.=','.$flag->id)
                                                            @endforeach
                                                            <input type="hidden" name="lstflags" value="{{$lstFlags}}">
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="row">
                                            <div class="form-group col-md-2">
                                                <label for="active" style="color: rgb(73, 80, 87);" title="Организация
                                                    работает">Активная:</label>
                                                {!! Form::checkbox('active', 1, $rec->active==1,['title'=>'Организация
                                                    работает','class'=>'form-control']) !!}
                                            </div>

                                            <div class="form-group col-md-5">
                                                <label for="active" style="color: rgb(73, 80, 87);"
                                                       title="Дата регистрации в ЕГРН">Регистрация:</label>
                                                <input type="date" class="form-control" name="begdate"
                                                       value="{{old('begdate',$rec->begdate)}}"
                                                       title="Дата регистрации в ЕГРН"/>
                                            </div>
                                            <div class="form-group col-md-5">
                                                <label for="active" style="color: rgb(73, 80, 87);"
                                                       title="Дата исключения из ЕГРН">Ликвидация:</label>
                                                <input type="date" class="form-control" name="enddate"
                                                       value="{{old('enddate',$rec->enddate)}}"
                                                       title="Дата исключения из ЕГРН"/>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="form-group col-md-4">
                                                <label for="active" style="color: rgb(73, 80, 87);">Рейтинг:</label>
                                                {!! Form::select('rating', $rec->ratings ,$rec->rating,
                                                    ['class' => 'form-control',
                                                    'placeholder' => '',
                                                    ]) !!}
                                            </div>
                                        </div>

                                        @if (1==0 and isset($rec->isownorg) and !$rec->isownorg)
                                            <div class="form-group">
                                                <label for="active" style="color: rgb(73, 80, 87);">Взаимодействует
                                                    с:</label>
                                                {!! Form::select('ownorgid', $rec->ownorgs, $rec->ownorgid, ['class' => 'form-control']) !!}
                                            </div>
                                        @endif

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="bank_account_info" class="">Примечание:</label>
                                                    <textarea class="form-control" rows="2"
                                                              name="notes"
                                                    >{{old('notes',$rec->notes)}}</textarea>
                                                </div>
                                            </div>
                                        </div>


                                    </div>
                                </div>


                                <hr size="1">
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                &nbsp;
                                <a class="btn btn-close btn-info" href="{{ $route_index }}"
                                   title="Вернуться в список клиентов">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($rec->id != -1 and $usrrights['delete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route('org.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                @endif
                                @if (1==0 and $rec->id != -1 and $usrrights['save'])
                                    <a class="btn btn-sm btn-warning ml-3"
                                       href="{{ route('orgs.updfrm_zachestnyibiznes',$rec->id) }}"
                                       title="Загрузить основные данные об организации с портала ЗаЧестныйБизнес">
                                        <i class="fa fa-cloud-download" aria-hidden="true"></i>
                                    </a>
                                @endif
                                &nbsp;
                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>
                @if ($rec->id != -1)

                    <div class="col-md-5">

                        @include('orgs.org_saldos')

                        @include('orgs.org_aux')

                        @include('org_names._names')

                        @include('orgs.org_acnts')
                        @include('orgs.org_places')
                        @includeif('tasks/linked_tasks')
                        @include('obj_contacts._contacts')
                        {{--                        @includeif('orgs.org_deps')--}}
                        @include('orgs._orgposts')
                        @include('objfiles.obj_files')

                        <?php $FlagsHeader = "Флаги контрагента" ?>
                        {{--						@include('objflags/objflags')--}}

                        {{--						@include('orgs.org_saldos')--}}

                    </div>

                @endif


            </div>
        </div>
        @if($rec->id==-1)
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
            <link href="https://cdn.jsdelivr.net/npm/suggestions-jquery@21.6.0/dist/css/suggestions.min.css"
                  rel="stylesheet"/>
            <script src="https://cdn.jsdelivr.net/npm/suggestions-jquery@21.6.0/dist/js/jquery.suggestions.min.js" defer></script>
        @endif
        <script src="{{ asset('js/org_edit.js') }}" defer></script>
    @endif
@endsection
