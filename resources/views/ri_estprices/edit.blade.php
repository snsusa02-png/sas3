@extends('layouts.edit')

@section('content')
    <?php
    $sysobjid = 145;
    $thisSysObjId = 145;
    $sysobjcode = 'ri_estprices';
    $thisTitle = "Предложение поставщика";
    $userid = \Auth::user()->id;

    if (!isset($rec)) {
        redirect()->route($sysobjcode . '.index');
        header("Location:" . route($sysobjcode . '.index'));
        die();
    }


    $retURL = ($rec->retURL)
        ? ($rec->retURL . '#estprices')
        : route('refitems.index') . "?page=" . session($sysobjcode . '_pageno');



    if ($usrrights['save'] ?? false) {
        $readonly = '';
    } else {
        $readonly = 'readonly';
        $docsum_readonly = 'readonly';
    }
    ?>

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
                <div class="card p-2 my-2 my-md-3">
                    <div class="card-header" style="background-color: #c8ffa0;">
                        {{$thisTitle}}
                        <a class="btn btn-close btn-light btn-sm"
                           style="float:right;"
                           href="{{ $retURL }}"
                           title="Вернуться в список">
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="card-body" style="">

                        @include('layouts.err_msgs')

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', $rec->id) }}"
                              enctype="multipart/form-data">
                            @method('PUT')
                            @csrf
                            {{ Form::hidden('ttt', 1) }}
                            {{ Form::hidden('refitmid', $rec->refitmid) }}
                            {{ Form::hidden('retURL', $retURL) }}

                            <div class="row">
                                <div class="form-group col-md-3 col-sm-6">
                                    <label for="price" class="required">Цена
                                        руб/<b>{{$rec->refitem->unittype->name}}</b>
                                        :</label>

                                    @if ($usrrights['save']??false)
                                        <input type="number" class="form-control" name="price"
                                               min="0" step="0.01"
                                               value="{{old('price',$rec->price)}}"/>
                                    @else
                                        <input type="text" class="form-control" name="price" readonly
                                               value="{{$rec->price}}"/>
                                    @endif
                                </div>

                                <div class="form-group col-md-9">
                                    <label for="suporg" class="required">Поставщик:</label>
                                    <div class="input-group mb-3 ">
                                        {{ Form::hidden('suporgid', old('suporgid',$rec->suporgid),['id'=>'orgid']) }}
                                        @if ($usrrights['save']??false)
                                            <input type="text" class="form-control" name="orgname"
                                                   id="orgname"
                                                   value="{{old('suporg_name',$rec->suporg->name)}}"
                                                   data-toggle="tooltip" data-placement="right"
                                                   title=""
                                            />
                                            <input type="text" class="form-control text-center small"
                                                   style="display: none; border: #d7f3e3;" id="ac_orgid"
                                                   readonly>

                                            <div class="input-group-append">

                                                <a onclick="callListOrgs($('#orgid').val())" title="Поиск"
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
                                            <input type="text" name="orgname" id="orgname"
                                                   class="form-control" readonly
                                                   value="{{old('orgname',$rec->suporg->name)}} (ИНН:{{$rec->suporg->inn}}, КПП:{{$rec->org->kpp}})"
                                            />
                                        @endif
                                        @if(isset($rec->orgid))
                                            <a href="{{route('orgs.edit',$rec->suporgid)}}" id="org_link"
                                               target="_blank" class="btn btn-light"><i class="fa fa-external-link"
                                                                                        aria-hidden="true"></i></a>
                                        @endif

                                    </div>
                                </div>


                            </div>

                            <div class="row">
                                <div class="form-group offset-md-4 col-md-4 col-sm-6">
                                    <label for="begdate" class="required">Цена действительна с:</label>
                                    @if ($usrrights['save']??false)
                                        <input type="date" class="form-control" name="begdate"
                                               min="{{today()->modify('-3 day')->format('Y-m-d')}}"
                                               {{--                                               max="{{today()->format('Y-m-d')}}"--}}
                                               value="{{old('begdate',$rec->begdate)}}"/>
                                    @else
                                        <input type="date" class="form-control" name="begdate"
                                               readonly
                                               value="{{$rec->begdate}}"/>
                                    @endif
                                </div>

                                <div class="form-group col-md-4 col-sm-6">
                                    <label for="docnum" class="required">до (включ.):</label>
                                    @if ($usrrights['save'] ?? false)
                                        <input type="date" class="form-control" name="enddate"
                                               value="{{old('enddate',$rec->enddate)}}"/>
                                    @else

                                        <input type="date" class="form-control" name="enddate"
                                               value="{{old('enddate',$rec->enddate)}}" readonly/>
                                    @endif
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-md-4 c8_hide c3_hide">
                                    <label for="supwrkdays" title="Срок поставки в рабочих днях после оплаты"
                                    >Срок получения, дн:</label>
                                    @if ($usrrights['save']??false)
                                        <input type="number" class="form-control text-right supwrkdays"
                                               name="supwrkdays" min="0" step="1"
                                               title="Срок получения после оплаты (рабочих дней)"
                                               value="{{old('plngetwrkdays',$rec->supwrkdays)}}"
                                        />
                                    @else
                                        <div class="">
                                            <b>{{$rec->supwrkdays}}</b>
                                        </div>
                                    @endif

                                </div>

                                <div class="form-group col-md-8">
                                    <label for="notes">Условия поставки / Запас / Примечания:</label>
                                    @if ($usrrights['save']??false)
                                        <input type="text" class="form-control" name="sup_notes"
                                               maxlength="160"
                                               value="{{old('notes',$rec->sup_notes)}}"/>
                                    @else
                                        <input type="text" name="sup_notes"
                                               class="form-control" readonly
                                               value="{{$rec->sup_notes}}"
                                        />
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
                            <a class="btn btn-close btn-info" href="{{ $retURL }}"
                               title="Вернуться в список">
                                <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                Закрыть
                            </a>
                            @if ($rec->id != -1 and $usrrights['delete'])

                                <a href="{{route($sysobjcode.'.delete', $rec->id)."?retURL={$retURL}"}}"
                                   onclick="return confirm('Вы действительно хотите удалить запись?')"
                                   title="Удалить запись"
                                   class="btn btn-danger btn-sm ml-4">
                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                </a>

                            @endif
                        </form>
                    </div>

                    <div class="card-footer">
                        @include('layouts._who_when')
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                @include('objfiles.obj_files')
                @include('obj_readers._readers')
            </div>
        </div>

    </div>

    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

    <script src="{{ asset('js/callListOrgs.js') }}" defer></script>
    <script src="{{ asset('js/ri_estprice_edit.js') }}" defer></script>
@endsection

@section('title')
    {{$thisTitle}}
@endsection
