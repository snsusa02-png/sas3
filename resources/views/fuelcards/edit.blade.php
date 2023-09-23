@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('fuelcards.index');
        header("Location:" . route('fuelcards.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

        <?php
        $sysobjid = 561;
        $thisSysObjId = $sysobjid;
        $thisSysObjCode = 'fuelcards';
        $sysobjcode = $thisSysObjCode;
        $ThisTitle = "Топливная карта";

        $route_index = route($thisSysObjCode . '.index') . "?page=" . session($thisSysObjCode . '_pageno') . '#' . $rec->id;
        ?>
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .org-aux {
                width: 100%
            }

            .photo {
                display: block;
                max-width: 116px;
                max-height: 136px;
                width: auto;
                height: auto;
                margin: auto;
            }
        </style>
        <div class="container">

            @includeIf('layouts.edit_msgs')

            <div class="row ">
                <div class="col-md-6">
                    <div class="card p-2 my-2 my-md-3">
                        <div class="card-header">
                            {{$ThisTitle}}
                            <a class="btn btn-close btn-light btn-sm"
                               style="float:right;"
                               href="{{ $route_index }}"
                               title="Вернуться в список">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="card-body">
                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($thisSysObjCode . '.update', $rec->id) }}">
                                @method('PUT')
                                @csrf

                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label for="name" class="required">Номер:</label>
                                        <input type="text" class="form-control font-weight-bold" name="num"
                                               maxlength="16"
                                               value="{{old('num',$rec->num)}}"/>
                                    </div>
                                    <div class="form-group col-md-9">
                                        <label for="name" class="required">Название:</label>
                                        <input type="text" class="form-control font-weight-bold" name="name"
                                               maxlength="120"
                                               value="{{old('name',$rec->name)}}"/>
                                    </div>
                                </div>


                                <div class="form-group">
                                    <label for="descript">Описание:</label>
                                    <textarea class="form-control rounded-0" name="notes" id="notes"
                                              rows="3">{{ $rec->notes }}</textarea>
                                </div>

                                <div class="row">

{{--                                    <div class="form-group col-md-12">--}}
{{--                                        <label for="orgid" class="required">Владелец:</label>--}}
{{--                                        {!! Form::select('orgid', $data->orgs, $rec->orgid,--}}
{{--                                         [--}}
{{--                                         'class' => 'form-control',--}}
{{--                                         'placeholder' => '-выбор-',--}}
{{--                                         ]) !!}--}}
{{--                                    </div>--}}

                                    <div class="form-group offset-md-0 col-md-12">
                                        <label for="name" class="required"><span id="lbl_org">Владелец</span>:</label>
                                        @if ($usrrights['save'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="org_name" required id="org_name"
                                                       class="ac_name ac_org_name form-control font-weight-bold"
                                                       value="{{old('org_name',$rec->org->info??'')}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       title=""
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="orgid" class="ac_id" id="orgid"
                                                       data-gk="1"
                                                       value="{{old('orgid',$rec->orgid)}}">
                                                <a class="btn btn-light id_lnk" data-id="orgid" data-obj="orgs"
                                                   target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                            <div></div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->org->info??''}}</div>
                                        @endif
                                    </div>

                                </div>
                                <div class="form-group">
                                    <label for="active" style="color: rgb(73, 80, 87);">Активная:</label>
                                    {!! Form::checkbox('active', 1, $rec->active==1) !!}
                                </div>

                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                &nbsp;
                                <a class="btn btn-close btn-info" href="{{ $route_index }}"
                                   title="Вернуться в список">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($rec->id != -1 and $usrrights['delete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($thisSysObjCode . '.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                @endif
                                &nbsp;
                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>

                @if (1==0 and $rec->id != -1)
                    <div class="col-md-6">

                        @include('objfiles.obj_files')

                    </div>

                @endif


            </div>
        </div>
        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/fuelcard_edit.js') }}" defer></script>
    @endif
@endsection
