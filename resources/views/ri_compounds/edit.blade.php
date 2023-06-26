@extends('layouts.edit')

@section('content')

    @if (!isset( $rec ))
        <?php
        redirect()->route('wrhdocs.index');
        header("Location:" . route('wrhdocs.index'));
        die();
        ?>
    @else
        <?php
        $thisTitle = "Состав комплектующих для производства";
        $thisSysObjCode = 'ri_compounds';
        $thisSysObjId = 147;
        $sysobjid = $thisSysObjId;

        $retURL = \Request::get('returl') ?? $rec->retURL ?? (route($thisSysObjCode . '.index') . "?page=" . session($thisSysObjCode . '_pageno') . '#' . $rec->id);

        //Отображать или нет Цену/Сумму определяется типом документа
        if ($rec->id != -1) {
            $isDocSigned = ($rec->docsigned == 1);

        } else {
            $isDocSigned = false;
        }

        $hdr_span_colno = 5;
        $tot_span_colno = 3;

        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        $inputReadOnly = "readonly";
        if ($usrrights['save']) $inputReadOnly = "";
        //$usrrights['safe_save'] = false;
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
            <div class="row ">
                <div class="col-md-9">
                    <div class="card mt-3">

                        @include('layouts.edit_msgs')

                        <div class="card-header">
                            <?php
                            $simplename = "Состав изделия";
                            ?>
                            <span class="sm-caps">{{$simplename}}</span>

                            <a class="btn btn-close btn-info btn-sm"
                               style="float:right;"
                               href="{{ $retURL }}"
                               title="Вернуться в список ">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>

                        </div>
                        <div class="card-body">

                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route('ri_compounds.update', $rec->id) }}">
                                @method('PUT')
                                @csrf

                                <div class="row">
                                    <div class="col-md-6" id="refitem" class="">
                                        <label for="name" class="required"><span
                                                id="lbl_refitem">Изделие</span>:</label>
                                        @if ($usrrights['safe_save']??false)
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="refitm_name" id="refitm_name"
                                                       class="ac_name ac_refitm_name form-control font-weight-bold"
                                                       value="{{old('ri_name',$rec->refitem->name)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       title=""
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="refitmid" class="ac_id" id="refitmid"
                                                       value="{{old('refitmid',$rec->refitmid)}}">
                                                <a class="btn btn-light id_lnk" data-id="refitmid" data-obj="refitems"
                                                   target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                            <div></div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->refitem->name}}</div>
                                        @endif
                                    </div>
                                    <div class="col-md-6" id="ownorg">
                                        <label for="name" class="required"><span
                                                id="lbl_ownorg">Производитель</span>:</label>
                                        @if ($usrrights['safe_save']??false)
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="ownorg_name" id="ownorg_name"
                                                       class="ac_name ac_org_name form-control font-weight-bold"
                                                       data-gk="1"
                                                       value="{{old('ownorg_name',$rec->ownorg->info)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       title=""
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="ownorgid" class="ac_id" id="ownorgid"
                                                       value="{{old('ownorgid',$rec->ownorgid)}}">
                                                <a class="btn btn-light id_lnk" data-id="orgid" data-obj="orgs"
                                                   target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                            <div></div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->ownorg->info}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="remarks">Примечания:</label>

                                    @if ($usrrights['safe_save'])
                                        <textarea class="form-control rounded-0" name="notes" id="descript"
                                                  rows="2">{{$rec->notes}}</textarea>
                                    @else
                                        {{ Form::hidden('notes', $rec->notes) }}
                                        <p><b>{{$rec->notes}}</b></p>
                                    @endif
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-3 col-sm-5">
                                        <label for="begdate">Начало действия:</label>
                                        @if ($usrrights['safe_save'])
                                            <input type="date" class="form-control" name="begdate" id="begdate"
                                                   value="{{old('begdate',$rec->begdate)}}"/>
                                        @else
                                            {{ Form::hidden('begdate', $rec->begdate) }}
                                            <p><b>{{date_create($rec->begdate)->format('d.m.Y')}}</b></p>
                                        @endif
                                    </div>
                                    <?php
                                    $t_date = $rec->enddate;
                                    //dd($t_date);
                                    ?>
                                    <div class="form-group col-md-3 col-sm-5">
                                        <label for="begdate">Окончание действия:</label>
                                        @if ($usrrights['safe_save'])
                                            <input type="date" class="form-control" name="enddate"
                                                   value="{{old('enddate',$rec->enddate)}}"/>
                                        @else
                                            {{ Form::hidden('enddate', $rec->enddate) }}
                                            <p>
                                                <b>{{(isset($rec->enddate))?date_create($rec->enddate)->format('d.m.Y'):'-нет-'}}</b>
                                            </p>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="active" style="color: rgb(73, 80, 87);">Активный:</label>
                                        @if ($usrrights['safe_save']??false)
                                            {!! Form::checkbox('active', 1, $rec->active==1,
                                             [
                                             'class' => 'form-control',
                                             ]) !!}
                                        @else
                                            {{ Form::hidden('active', $rec->active) }}
                                            <p><b>{{(isset($rec->active) and $rec->active==1)?'-да-':'-нет-'}}</b></p>
                                        @endif
                                    </div>
                                </div>

                                <hr size="1">
                                @if ($usrrights['save'] or $usrrights['safe_save'] or (!$isDocSigned))

                                    <button type="submit" class="btn btn-success">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                <a class="btn btn-close btn-info" href="{{ $retURL }}"
                                   title="Вернуться">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($usrrights['delete'])
                                    <button type="submit"
                                            class="btn btn-danger"
                                            style="margin-left:24px"
                                            formaction="{{ route('ri_compounds.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись о перечне комплектующих?')"
                                            title="Удалить"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>

                                @elseif($usrrights['admindelete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px; margin-right:8px;"
                                            formaction="{{ route($thisSysObjCode.'.admindelete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Документ будет удален административно - без учета ограничений!\n\nПродолжать?')"
                                            title="Административно удалить документ"
                                    >
                                        <i class="fa fa-bomb" aria-hidden="true"></i>
                                    </button>
                                @endif

                                @if ($usrrights['docsign'])
                                    <button type="submit"
                                            class="btn btn-warning"
                                            style="margin-left:24px"
                                            formaction="{{ route('ri_compounds.sign', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите утвердить документ?')"
                                            title="Утвердить документ"
                                    >
                                        <i class="fa fa-thumbs-up" aria-hidden="true"></i>
                                        Утвердить
                                    </button>
                                @endif
                                @if ($usrrights['docunsign'])
                                    <button type="submit"
                                            class="btn btn-danger"
                                            style="margin-left:24px"
                                            formaction="{{ route('ri_compounds.unsign', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите отменить согласование документа?')"
                                            title="Отменить утверждение документа"
                                    >
                                        <i class="fa fa-thumbs-o-down" aria-hidden="true"></i>
                                    </button>
                                @endif

                                @if ($usrrights['save_active'] and $isDocSigned)
                                    @if ($rec->active==0)
                                        <button type="submit" class="btn btn-sm btn-success"
                                                formaction="{{ route('ri_compounds.trg_active', $rec->id)}}"
                                                formmethod="post"
                                                style="margin-left:24px"
                                        >
                                            <i class="fa fa-check" aria-hidden="true"></i>
                                            Активировать
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-sm btn-warning"
                                                formaction="{{ route('ri_compounds.trg_active', $rec->id)}}"
                                                formmethod="post"
                                                style="margin-left:24px"
                                        >
                                            <i class="fa fa-close" aria-hidden="true"></i>
                                            Перевести в черновик
                                        </button>
                                    @endif
                                @endif

                                @if ($rec->id<>-1)
                                    {{--                                    <a class="btn btn-close btn-warning btn hide_chngd ml-3"--}}
                                    {{--                                       href="{{ route($thisSysObjCode .'.print', $rec->id) }}"--}}
                                    {{--                                       target="_blank" id="print_rqst"--}}
                                    {{--                                       title="Напечатать">--}}
                                    {{--                                        <i class="fa fa-print" aria-hidden="true"></i>--}}
                                    {{--                                    </a>--}}
                                @endif

                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>
                <?php
                ?>
            </div>

            @if ($rec->id!=-1)
                @include("ri_compounds.lst_std")

            @endif

            <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
            <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

            <script src="{{ asset('js/callListWrhs.js') }}" defer></script>
            <script src="{{ asset('js/ri_compound_edit.js') }}" defer></script>

        </div>
    @endif
@endsection
