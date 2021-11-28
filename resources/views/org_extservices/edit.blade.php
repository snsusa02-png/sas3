@extends('layouts.edit')

@section('content')
    <?php
    $sysobjid = 981;
    $sysobjcode = 'org_extservices';
    $ThisTitle = "Связь организации с внешней ИС";

    $route_index = route($sysobjcode . '.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;

    $retRoute = ($rec->retURL)
        ? ($rec->retURL . '#org_extservices')
        : route($sysobjcode . '.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;

    ?>

    @if (!isset( $rec))
        <?php
        redirect()->route($sysobjcode . '.index');
        header("Location:" . route($sysobjcode . '.index'));
        die();
        ?>
    @endif

    <script src="{{ asset('js/collapse.js') }}" defer></script>

    <style>
        label {
            color: gray;
            margin-bottom: 0px;
        }
    </style>

    <div class="container">

        @includeIf('layouts.edit_msgs')

        <div class="row ">
            <div class="col-md-8">
                <div class="card p-2 my-2 my-md-3" style="background-color: #fbf6f6">
                    <div class="card-header" style="background-color: #ead9e2">
                        <b>{{$ThisTitle}}</b>
                        <a class="btn btn-close btn-light btn-sm"
                           style="float:right;"
                           href="{{ $retRoute }}"
                           title="Вернуться в список">
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="card-body">

                        @includeIf('layouts.err_msgs')

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', $rec->id) }}"
                              enctype="multipart/form-data">
                            @method('PUT')
                            @csrf
                            {{ Form::hidden('ttt', 1) }}

                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="address">Поставщик:</label>
                                    <div class="input-group mb-3 ">
                                        {{ Form::hidden('srvcorgid', $rec->srvcorgid, ['id'=>'srvcorgid','class'=>'orgid']) }}
                                        @if ($usrrights['save']??false)
                                            <input type="text" class="form-control orgname" name="orgname"
                                                   id="srvcorgname"
                                                   value="{{$rec->srvcorg->name}}"
                                                   data-toggle="tooltip" data-placement="right"
                                                   title=""
                                            />
                                            <input type="text" class="form-control text-center small"
                                                   style="display: none; border: #d7f3e3;" id="ac_orgid"
                                                   readonly>
                                            <div class="input-group-append">

                                                <a onclick="callListOrgs($('#srvcorgid').val())" title="Поиск"
                                                   id="org_search_btn" style0="display: none"
                                                   class="btn btn-primary form-control">
                                                    <i class="fa fa-search" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                            <a href="{{route('orgs.create',0)}}" title="Добавить нового контрагента"
                                               id="new_org_link" style="display: none"
                                               target="_blank" class="btn btn-sm0 btn-warning ">
                                                <i class="fa fa-plus " aria-hidden="true"></i>
                                            </a>
                                        @else
                                            <input type="text" name="srvcorgname" id="srvcorgname"
                                                   class="form-control" readonly
                                                   value="{{$rec->srvcorg->name}} (ИНН:{{$rec->srvcorg->inn}}, КПП:{{$rec->srvcorg->kpp}})"
                                            />
                                        @endif
                                        @if(isset($rec->srvcorgid))
                                            <a href="{{route('orgs.edit',$rec->srvcorgid)}}" id="org_link"
                                               target="_blank" class="btn btn-light"><i class="fa fa-external-link"
                                                                                        aria-hidden="true"></i></a>
                                        @endif

                                    </div>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="address">Получатель:</label>
                                    <div class="input-group">
                                        @if ($usrrights['save']??false)
                                            {!! Form::select('orgid', $rec->ownorgs, $rec->orgid,
                                             [
                                             'id' => 'orgid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             'required' => '-выбор-',
                                             ]) !!}
                                        @else
                                            <input type="text" name="ownorgname"
                                                   class="form-control" readonly
                                                   value="{{$rec->org->name}}"
                                            />
                                        @endif
                                        @if(isset($rec->orgid))
                                            <a href="{{route('orgs.edit',$rec->orgid)}}" id="ownorg_link"
                                               target="_blank" class="btn btn-light"><i class="fa fa-external-link"
                                                                                        aria-hidden="true"></i></a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group offset-md-0 col-md-12">
                                    <label for="orgcontractid">Договор:</label>
                                    @if ($usrrights['save'] and isset($rec->contracts))
                                        {!! Form::select('contractid', $rec->contracts, $rec->contractid,
                                         [
                                         'id' => 'contractid',
                                         'class' => 'form-control',
                                         'placeholder' => '',
                                         ]) !!}
                                    @else
                                        <div class="">
                                            {{$rec->contract->shortInfo}}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group offset-md-0 col-md-12 ">
                                    <label for="name" class="required">Название услуги:</label>
                                    @if ($usrrights['save'])
                                        <input type="text" class="form-control"
                                               name="name" id="name" maxlength="60"
                                               value="{{old('name',$rec->name)}}"/>
                                    @else
                                        <div class="font-weight-bold">{{$rec->name}}</div>
                                    @endif
                                </div>

                            </div>

                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="descript">Описание/Подробности:</label>
                                    @if ($usrrights['save'])
                                        <textarea class="form-control rounded-0"
                                                  name="descript" id="descript" maxlength="360"
                                                  rows="4">{{ old('descript',$rec->descript) }}</textarea>
                                    @else
                                        <div class="font-weight-bold text-left">{{$rec->descript}}</div>
                                    @endif
                                </div>
                            </div>


                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label class="required">Порог блокировки, руб:</label>
                                    @if ($usrrights['save'])
                                        <input type="number" class="form-control text-right"
                                               name="lock_limsum" id="lock_limsum"
                                               min="0" step="0.01"
                                               value="{{old('lock_limsum',$rec->lock_limsum)}}"/>
                                    @else
                                        <div class="font-weight-bold text-center">{{$rec->lock_limsum}}</div>
                                    @endif
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Порог уведомления, руб:</label>
                                    @if ($usrrights['save'])
                                        <input type="number" class="form-control text-right"
                                               name="notify_limsum" id="notify_limsum"
                                               min="0" step="0.01"
                                               value="{{old('notify_limsum',$rec->notify_limsum)}}"/>
                                    @else
                                        <div class="font-weight-bold text-center">{{$rec->notify_limsum}}</div>
                                    @endif
                                </div>
                                @if($rec->id<>-1 and isset($rec->rest_dt))
                                    <div class="form-group col-md-4">
                                        <label>Остаток, руб:</label>
                                        <div class="font-weight-bold text-center"> {{$rec->rest_sum}}</div>
                                        <div class="small mt-2"> по данным
                                            на {{date_create($rec->rest_dt)->format('d.m.Y H:i')}}</div>
                                    </div>
                                @endif
                            </div>

                            <div class="row">
                                <div class="form-group col-md-2">
                                    <div class="form-group">
                                        <label for="active">Активно:</label>
                                        {!! Form::checkbox('active', 1, $rec->active==1, ['class="form-control"']) !!}
                                    </div>
                                </div>

                                <div class="form-group offset-md-0 col-md-5 ">
                                    <label for="name" class="required">URL ЛК:</label>
                                    @if ($usrrights['save'])
                                        <input type="text" class="form-control"
                                               name="lk_url" id="lk_url" maxlength="60"
                                               value="{{old('lk_url',$rec->lk_url)}}"/>
                                    @elseif(isset($rec->lk_url))
                                        <div class="font-weight-bold"><a href="{{$rec->lk_url}}"
                                                                         target="_blank">{{$rec->lk_url}}</a></div>
                                    @endif
                                </div>
                                <div class="form-group offset-md-0 col-md-5">
                                    <label for="acnts">Внешняя ИС:</label>
                                    {!! Form::select('extsysid', $rec->extsystems, $rec->extsysid,
                                     [
                                     'id' => 'extsysid',
                                     'class' => 'form-control font-weight-bold',
                                     'placeholder' => '-выбор-',
                                     ]) !!}
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label>Включить в информер если прогноз блокировки менее, дней:</label>
                                    @if ($usrrights['save'])
                                        <input type="number" class="form-control text-right"
                                               name="inform_limdays" id="inform_limdays"
                                               min="0" step="1" max="255"
                                               value="{{old('inform_limdays',$rec->inform_limdays)}}"/>
                                    @else
                                        <div class="font-weight-bold text-center">{{$rec->inform_limdays}}</div>
                                    @endif
                                </div>

                            </div>

                            <hr>
                            @if ($usrrights['save'])
                                <button type="submit" class="btn btn-success" title="Сохранить изменения">
                                    <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                    Сохранить
                                </button>
                            @endif
                            &nbsp;
                            <a class="btn btn-close btn-info" href="{{ $retRoute }}"
                               title="Вернуться в список">
                                <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                Закрыть
                            </a>
                            @if ($rec->id != -1 and $usrrights['delete'])
                                <button type="submit"
                                        class="btn btn-danger btn-sm"
                                        style="margin-left:24px"
                                        formaction="{{ route($sysobjcode.'.delete', $rec->id)}}"
                                        formmethod="post"
                                        onclick="return confirm('Вы действительно хотите удалить запись?')"
                                        title="Удалить запись"
                                >
                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                </button>
                            @endif
                        </form>
                    </div>
                    <div class="card-footer">
                        @if ($rec->id != -1)
                            <div class="small" style="margin-top: 8px; color:gray;">
                                создана: {{$rec->created_at}} / {{$rec->whocrt->FirstLast}},
                                изменена: {{$rec->updated_at}} / {{$rec->whoupd->FirstLast}}
                                <br><a
                                    href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                @include('obj_readers._readers')
                @includeif($sysobjcode.'._extsrvc_sums')
                {{--                @include($sysobjcode.'.obj_files')--}}
            </div>
        </div>

    </div>

    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

    <script src="{{ asset('js/callListOrgs.js') }}" defer></script>
    <script src="{{ asset('js/orgextservice_edit.js') }}" defer></script>
@endsection
