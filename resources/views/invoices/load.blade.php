@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('contracts.index');
        header("Location:" . route('contracts.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

        <?php
        $sysobjid = 915;
        $objcode = 'invoices';
        $ThisTitle = "Импорт счета из XLS-файла";

        $retroute = (isset($rec->invoiceid)) ? route('invoices.edit', $rec->invoiceid) : route('invoices.index');

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
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div><br/>
                            @endif

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
                                            <label for="doc">Файл со счетом на оплату (XLSX):</label>
                                            <input type="file" class="form-control" name="doc"
                                                   accept=".xls,.xlsx"
                                                   style="padding: 3px;"/>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group offset-md-2 col-md-10">
                                            <label for="notes">Формат данных файла:</label>
                                            <div class="input-group">
                                                {!! Form::select('docimpformatid', $rec->docimpformats, null
                                            ,['class' => 'form-control small',
                                            'placeholder'=>'-выберите формат файла-',
                                            ]) !!}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-2 col-md-10">
                                            <label for="address">Покупатель:</label>
                                            {!! Form::select('ownorgid', $rec->ownorgs, null,
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             'required' => '-выбор-',
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
