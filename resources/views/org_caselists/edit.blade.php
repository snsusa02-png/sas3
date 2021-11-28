@extends('layouts.edit')
@section('title')
    Учет рабочего времени
@endsection

@section('content')
    <?php
    $sysobjid = 1101;
    $thisSysObjCode = 'org_caselists';
    $objcode = $thisSysObjCode;
    $ThisTitle = "Номенклатура дел";

    $retURL = \Request::get('returl')
        ?? $rec->retURL
        ?? (route($thisSysObjCode . '.index') . "?page=" . session($thisSysObjCode . '_pageno') . '#' . $rec->id);

    $has_errors = null !== session()->get('error');
    //    $main_body_display = ($rec->id == -1 or $has_errors) ? '' : 'display:none';
    //    $main_ftr_display = ($rec->id == -1 or $has_errors) ? 'display:none' : '';
    $main_body_display = '';
    $main_ftr_display = 'display:none';


    if ($rec->id == -1) {
        $hdr_collapse = 'show';
        $items_collapse = 'collapse';
    } else {
        //$hdr_collapse = 'collapse';
        $hdr_collapse = '';
        $items_collapse = 'show';
    }
    ?>

    @if (!isset( $rec))
        <?php
        redirect()->route($objcode . '.index');
        header("Location:" . route($objcode . '.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

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
                <div class="col-md-12">
                    <div class="card p-2 my-2 my-md-3">
                        <div class="card-header " style="background-color: #5c5c92; color: #e6e6e6" id="main_hdr">
                            <span class="font-weight-bold">{{$ThisTitle}}</span>
                            @if(1==0)
                                <span class="ml-5 px-3 py-1"
                                      style="border: 1px solid white;border-radius: 14px;{{$rec->status_style}}"><span
                                            class="">статус:</span> <b>{{$rec->status_name}}</b></span>
                            @endif
                            @if($rec->id<>-1)
                                <span class="ml-5 px-3 py-1">
                                    Дата: <b>{{date_format(date_create($rec->docdate),'d.m.Y')}}</b>
                                </span>
                            @endif
                            <div class="float-right">
                                @if (1==1)
                                    <button data-toggle="collapse" data-target="#main_body"
                                            class="btn btn-light btn-sm mr-1"><i class="fa fa-eye-slash"
                                                                                 aria-hidden="true"></i></button>
                                @endif
                                <a class="btn btn-close btn-light btn-sm"
                                   style="float:right;"
                                   href="{{ $retURL }}"
                                   title="Вернуться в список">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>

                        <div class="card-body {{$hdr_collapse}}" id="main_body"
                             style="background-color: #fcf5f3;{{$main_body_display}}">

                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($objcode.'.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('items_count', count($rec->items)) }}

                                {{--                                <input type="hidden" name="items_count" value="{{count($rec->items)}}">--}}


                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="container">

                                            <div class="row">
                                                <div class="form-group col-md-6">
                                                    <label for="name" class="required">Организация:</label>
                                                    @if ($usrrights['save'] )
                                                        {!! Form::select('orgid', $rec->ownorgs??[], $rec->orgid,
                                                         [
                                                             'id' => 'orgid',
                                                         'class' => 'form-control',
                                                         'placeholder' => '',
                                                         ]) !!}
                                                    @else
                                                        <input type="hidden" name="orgid" value="{{$rec->orgid}}">
                                                        {{$rec->org->name}}
                                                    @endif
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="name" class="required">Начало действия:</label>
                                                    @if ($usrrights['save'])
                                                        <input type="date" class="form-control text-center"
                                                               name="begdate"
                                                               value="{{old('begdate',$rec->begdate)}}"/>
                                                    @else
                                                        <div
                                                                class="font-weight-bold">{{date_create($rec->begdate)->format('d.m.Y')}}
                                                            {{ Form::hidden('begdate', $rec->begdate) }}
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="form-group col-md-3">
                                                    <label for="name" class="required">Окончание действия:</label>
                                                    @if ($usrrights['save'])
                                                        <input type="date" class="form-control text-center"
                                                               name="enddate"
                                                               value="{{old('enddate',$rec->enddate)}}"/>
                                                    @else
                                                        <div
                                                                class="font-weight-bold">{{date_create($rec->enddate)->format('d.m.Y')}}
                                                            {{ Form::hidden('enddate', $rec->enddate) }}
                                                        </div>
                                                    @endif
                                                </div>

                                            </div>

                                            <div class="row">
                                                <div class="form-group offset-md-9 col-md-3">
                                                    <label for="statusid" class="required">Статус:</label>
                                                    {!! Form::select('statusid', $rec->statuses, $rec->statusid,
                                                     [
                                                     'class' => 'form-control',
                                                     'placeholder' => '-выбор-',
                                                     ]) !!}
                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="container">
                                            <div class="row">
                                                <div class="form-group offset-md-0 col-md-12">
                                                    @if ($usrrights['save'] or $usrrights['change_status']??false)
                                                        <button type="submit" class="btn btn-success mb-2 btn-sm"
                                                                style="width:100%"
                                                                title="Сохранить изменения">
                                                            <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                                            Сохранить
                                                        </button>
                                                    @endif
                                                    <a class="btn btn-close btn-info mb-2 btn-sm"
                                                       href="{{ $retURL }}"
                                                       style="width:100%"
                                                       title="Вернуться в список ">
                                                        <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                                        Закрыть
                                                    </a>
                                                    @if ($rec->id != -1 and $usrrights['delete']  )
                                                        <a href="{{ route($objcode.'.delete', $rec->id)}}"
                                                           class="btn btn-danger btn-sm mt-2 btn-sm float-right"
                                                           onclick="return confirm('Вы действительно хотите удалить запись?')"
                                                           title="Удалить запись"
                                                        >
                                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                        </a>

                                                    @endif
                                                    @if ($rec->id != -1)
                                                        <div class="small" style="margin-top: 8px; color:gray;">
                                                            <a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                                        </div>
                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if(1==0)
                                    <div class="form-group">
                                        <label for="address">Примечание:</label>
                                        @if ($usrrights['save'])
                                            <input type="text" class="form-control" name="notes"
                                                   value="{{old('notes',$rec->notes)}}"/>
                                        @else
                                            <div class="font-weight-bold">{{$rec->notes}}</div>
                                        @endif
                                    </div>
                                @endif

                            </form>
                        </div>
                        <div class="card-footer " id="main_ftr" style="{{$main_ftr_display}}">
                            <div class="row">
                                <div class="form-group offset-md-0 col-md-6">
                                    <label for="meet_begdt">Дата:</label>
                                    <div class="ml-3"><b>{{date_format(date_create($rec->docdate),'d.m.Y')}}</b></div>
                                </div>
                            </div>
                            @if (1==0 and $rec->active == 1)
                                <hr>
                                <a class="btn btn-close btn-warning btn-sm ml-1 "
                                   href="{{ route('qchecks.print1', $rec->id) }}"
                                   target="_blank"
                                   title="Напечатать протокол">
                                    <i class="fa fa-print" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if (1==0 and $rec->id != -1)
                    <div class="col-md-6">
                        @include('objfiles.obj_files')
                    </div>
                @endif

            </div>

            <div class="row">
                <div class="col-md-12">
                    @include('org_caselists.items')
                </div>
            </div>
        </div>
        <script src="{{ asset('js/ocl_item_edit.js') }}" defer></script>
@endsection
@endif
