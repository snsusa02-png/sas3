@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('orgstaff.index');
        header("Location:" . route('orgstaff.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

        <?php
        $sysobjid = 1213;
        $objcode = 'stf_chrg_calcs';
        $ThisTitle = $rec->title ?? "Импорт записей";

        $retroute = route($objcode . '.index');

        ?>
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
                        <div class="card-header">
                            {{$ThisTitle}}
                            <a class="btn btn-close btn-light btn-sm"
                               style="float:right;"
                               href="{{ $retroute }}"
                               title="Вернуться в список">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="card-body">
                            @include('layouts.err_msgs')

                            @if (isset($rec->_obj_info))
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="notes">для записи:</label>
                                        <div class="font-weight-bold">{{$rec->_obj_info}}</div>
                                    </div>
                                </div>
                            @endif

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($objcode.'.import') }}"
                                  enctype="multipart/form-data">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('retroute', $retroute) }}

                                @if(!isset($rec->result))
                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <label for="doc" class="required">Файл с данными (XLSX):</label>
                                            <input type="file" class="form-control" name="doc"
                                                   accept=".xls,.xlsx"
                                                   style="padding: 3px;"
                                                required/>
                                        </div>
                                    </div>
{{--                                    <div class="row">--}}
{{--                                        <div class="form-group col-md-5">--}}
{{--                                            <label for="extsystemid" class="required0" title="Внешняя система - источник данных">Источник данных:</label>--}}
{{--                                            {!! Form::select('extsystemid', $rec->extsystems, $rec->extsystemid??null,--}}
{{--                                             [--}}
{{--                                             'class' => 'form-control required',--}}
{{--                                             'placeholder' => '-выбор-',--}}
{{--                                             'required' => 'required0',--}}
{{--                                             ]) !!}--}}
{{--                                        </div>--}}
{{--                                    </div>--}}


                                    <div class="row">
                                        <div class="form-group offset-md-2 col-md-9">
                                            <label for="datatypeid" class="required"
                                                   title="Тип данных">Тип данных:</label>
                                            {!! Form::select('datatypeid', $rec->datatypes, $rec->datatypeid??null,
                                             [
                                             'class' => 'form-control required',
                                             'placeholder' => '-выбор-',
                                             'required' => 'required',
                                             ]) !!}
                                        </div>
                                    </div>
                                @else
                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <label for="notes">Результат обработки:</label>
                                            ошибок: {{$rec->result->err}}
                                            <hr>
                                            {!! str_replace(PHP_EOL,'<br>',$rec->result->msg) !!}

                                            @if(isset($rec->result->notload_items))
                                                <div>
                                                    <ul>
                                                        @foreach($rec->result->notload_items as $itm)
                                                            <?php
                                                            $shName = $itm->name;
                                                            if (isset($itm->refitmid))
                                                                $shName = "<a href='" . route('refitems.edit', $itm->refitmid) . "' target='_blank'>{$shName}</a>";
                                                            ?>
                                                            <li>{{$itm->code}}: {!! $shName !!}
                                                                ({{$itm->refitem_unittype_name}}),
                                                                {{$itm->qty}} <b>{{$itm->unit}}</b>
                                                                * {{$itm->smet_price}}
                                                                = {{round($itm->qty*$itm->smet_price,2)}} </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{--                                <div class="form-group">--}}
                                {{--                                    <label for="descript">Описание:</label>--}}
                                {{--                                    <textarea class="form-control rounded-0" name="notes" id="descript"--}}
                                {{--                                              rows="2">{{ $rec->notes }}</textarea>--}}
                                {{--                                </div>--}}

                                <hr>
                                @if ($usrrights['save'] and  !isset($rec->result))
                                    <button type="submit" class="btn btn-submit btn-success" title="Сохранить изменения"
                                            id="btnSubmit">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Загрузить
                                    </button>
                                @endif
                                &nbsp;
                                <a class="btn btn-close btn-info" href="{{ $retroute }}"
                                   title="Вернуться ">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                            </form>
                        </div>
                    </div>
                </div>


            </div>
        </div>

        <script src="{{ asset('js/bot_ri_lims_load.js') }}" defer></script>

@endsection
@endif
