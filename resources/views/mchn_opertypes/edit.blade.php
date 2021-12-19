@extends('layouts.edit')

@section('content')

    @if (!isset( $rec ))
        <?php
        redirect()->route('/');
        header("Location:/");
        die();
        ?>
    @else
        <?php
        $sysobjid = 486;
        $sysobjcode = 'mchn_opertypes';
        $thisTitle = "Режим эксплуатации техники/механизма";
        $retRoute = ($rec->retURL)
            ? ($rec->retURL . '#mchn_opertypes')
            : route('machines.edit', $rec->machineid) . '?f=mchn_opertypes';

        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        $inputReadOnly = "readonly";
        if ($usrrights['save']) $inputReadOnly = "";

        ?>
        {{--        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">--}}
        {{--        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>--}}

        {{--        <script src="{{ asset('js/callListRefItems.js') }}" defer></script>--}}
        {{--        <script src="{{ asset('js/ri_ac_er_items.js') }}" defer></script>--}}

        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .btn {
                margin-bottom: 4px;
            }

            .highlight {
                color: green;
                font-weight: bold;
            }

            .ui-menu-item .ui-menu-item-wrapper.ui-state-active {
                background: #fff9c6 !important;
                font-weight: bold !important;
                color: #000 !important;
            }


        </style>
        <div class="container">

            <div class="row">
                <div class="col-md-7 col-sm-12">

                    <div class="card mt-3">

                        @includeIf('layouts.edit_msgs')

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', [$rec->id]) }}">

                            @method('PUT')
                            @csrf
                            {{ Form::hidden('retURL', $rec->retURL) }}
                            {{ Form::hidden('machineid', $rec->machineid) }}

                            <div class="card-header" style="background-color: #fffea6;">
                                <i class="fa fa-cogs" aria-hidden="true"></i> {{$thisTitle}}

                                <a class="btn btn-close btn-light btn-sm"
                                   style="float:right;"
                                   href="{{ $retRoute }}"
                                   title="Вернуться к списку">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body" style="background-color: #fff8f1;">

                                @include('layouts.err_msgs')

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="address" class="">Спецтехника:</label>
                                        <div class="font-weight-bold"> {{$rec->machine->info}}</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="" class="required">Режим эксплуатации:</label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('opertypeid', $rec->opertypes??[], old('opertypeid',$rec->opertypeid),
                                                 [
                                                     'id' => 'opertypeid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '-выбор-',
                                                 ]) !!}
                                            @else
                                                <div class="font-weight-bold">{{$rec->opertype->name}}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="refitmid" class="">Описание / Производительность:
                                        </label>
                                        <textarea name="descript" id="descript"
                                                  class="form-control text-left"
                                                  rows="2"
                                                  maxlength="160" {{$inputReadOnly}}>{{old('descript',$rec->descript)}}</textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-2 col-md-2">
                                        <label for="active" style="color: rgb(73, 80, 87);">Активный:</label>
                                        {!! Form::checkbox('active', 1, $rec->active==1,['class'=>'form-control']) !!}
                                    </div>
                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="address">Работа в час, &#8381;:</label>
                                        <input type="number" class="form-control text-right" name="hour_work_cost"
                                               min="0" step="0.01" readonly
                                               value="{{old('hour_work_cost',$rec->hour_work_cost)}}"/>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="hour_fuel_cost">Топливо в час, &#8381;:</label>
                                        <input type="number" class="form-control text-right" name="hour_fuel_cost"
                                               min="0" step="0.01" readonly
                                               value="{{old('hour_fuel_cost',$rec->hour_fuel_cost)}}"/>
                                    </div>
                                </div>

                                <div class="row">
                                </div>


                                <hr size="1">
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success"
                                            title="Сохранить изменения">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif

                                <a class="btn btn-close btn-info" href="{{ $retRoute }}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>

                                @if ($usrrights['delete'])
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

                            </div>
                            @if ($rec->id != -1)
                                <div class="card-footer">
                                    <div class="small" style="color: gray; margin:8px;">
                                        создана: {{$rec->created_at}} / {{$rec->whocrt->name}} &nbsp;
                                        изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}} &nbsp;
                                        <a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                    </div>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="col-md-5">

                    @include('mchn_opertypes._prices')
                    @include('objfiles.obj_files')
                </div>
            </div>

        </div>
        <script src="{{ asset('js/mchn_opertypes_edit.js') }}" defer></script>
    @endif
@endsection
