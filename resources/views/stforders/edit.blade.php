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
        $sysobjid = 1202;
        $sysobjcode = 'stforders';
        $thisTitle = "Приказ";

        $retRoute = "/";
        $retURL = $rec->retRoute ?? Request::get('returl');

        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        $inputReadOnly = "readonly";
        if ($usrrights['save']) $inputReadOnly = "";

        ?>
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .btn {
                margin-bottom: 4px;
            }

        </style>
        <div class="container">

            <div class="row">
                <div class="col-md-8 col-sm-12">

                    <div class="card mt-3">

                        @include('layouts.edit_msgs')

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', [$rec->id]) }}">

                            @method('PUT')
                            @csrf
                            {!! Form::hidden('retURL', $retURL) !!}
                            {!! Form::hidden('staffid', $rec->staffid) !!}
                            {!! Form::hidden('orgid', $rec->orgid,['id'=>'orgid']) !!}
                            {!! Form::hidden('ttt', 1) !!}

                            <div class="card-header">
                                {{$thisTitle}}

                                <a class="btn btn-close btn-info btn-sm"
                                   style="float:right;"
                                   href="{{ $retURL }}"
                                   title="Вернуться к списку">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body">

                                @include('layouts.err_msgs')

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="orgid">Сотрудник:</label>
                                        <div>
                                            <input type="hidden" name="orgid" value="{{$rec->orgid}}">
                                            <b>{{$rec->staff->name}}</b>
                                        </div>
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label for="orgid">Организация:</label>
                                        <div>
                                            <b>{{$rec->org->name}}</b>
                                        </div>
                                    </div>

                                </div>


                                <div class="row">

                                    <div class="form-group col-md-4">
                                        <label for="ordtypeid" class="required">Тип приказа:</label>
                                        @if($rec->id==-1)
                                            {!! Form::select('ordtypeid', $rec->ordtypes??[], $rec->ordtypeid,
                                             [
                                             'id' => 'ordtypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             ]) !!}
                                        @else
                                            <div class="font-weight-bold">
                                                {{$rec->ordtypes[$rec->ordtypeid]??'?'}}
                                                {!! Form::hidden('ordtypeid', $rec->ordtypeid, ['id'=>'ordtypeid']) !!}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="ordnum" class="required">№:</label>
                                        <input type="text" class="form-control"
                                               name="ordnum" id="ordnum" required
                                               value="{{ old('ordnum',$rec->ordnum) }}"/>
                                    </div>

                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="orddate" class="required">Дата приказа:</label>
                                        <input type="date" class="form-control"
                                               name="orddate" id="orddate" required
                                               value="{{ old('orddate',$rec->orddate) }}"/>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-4 col-md-8">
                                        <label for="ordnum" class="required">Основание:</label>
                                        <input type="text" class="form-control"
                                               name="reason" id="reason" required
                                               value="{{ old('reason',$rec->reason) }}"/>
                                    </div>
                                </div>

                                <div class="t10_show t20_show">
                                    <div class="row">

                                        <div class="form-group col-md-6">
                                            <label for="depname" class="required">Подразделение:</label>
                                            <div class="input-group">

                                                {!! Form::select('depid', $rec->orgdeps??[], $rec->depid,
                                                 [
                                                 'id' => 'depid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '',
                                                 ]) !!}

                                                <a class="btn btn-light" id="depid_lnk"
                                                   target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="postid" class="required">Должность:</label>
                                            <div class="input-group">
                                                {!! Form::select('postid', $rec->orgposts??[], $rec->postid,
                                                 [
                                                 'id' => 'postid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '',
                                                 ]) !!}

                                                <a id="postid_lnk" class="btn btn-light">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>

                                            </div>
                                        </div>

                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="jobtype">Тип работы:</label>
                                            {!! Form::select('jobtype', $rec->jobtypes??[], $rec->aux->jobtype??null,
                                             [
                                             'id' => 'jobtype',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             ]) !!}
                                        </div>

                                        <div class="form-group offset-md-0 col-md-2">
                                            <label for="jobfraction">Ставка:</label>
                                            <input type="number" class="form-control text-right"
                                                   name="jobfraction" id="jobfraction"
                                                   min="0" step="0.25" max="1.75"
                                                   value="{{ old('jobfraction',$rec->aux->jobfraction??null) }}"/>
                                        </div>

                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="salary">ЗП:</label>
                                            <input type="number" class="form-control text-right"
                                                   name="salary" id="salary"
                                                   min="0" step="0.01"
                                                   value="{{ old('salary',$rec->aux->salary??null) }}"/>
                                        </div>
                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="salary">Надбавка:</label>
                                            <input type="text" class="form-control text-center"
                                                   name="bonus" id="bonus"
                                                   value="{{ old('bonus',$rec->aux->bonus??null) }}"/>
                                        </div>
                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="salary" title="Испытательный срок, месяцев">Испыт. срок,
                                                мес:</label>
                                            <input type="text" class="form-control text-center"
                                                   name="testterm" id="testterm"
                                                   value="{{ old('testterm',$rec->aux->testterm??null) }}"/>
                                        </div>
                                    </div>
                                </div>


                                <div class="t50_show">
                                    {{--//Приказ на отпуск--}}
                                    <div class="row">

                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="vactype" class="required">Тип отпуска:</label>
                                            {!! Form::select('vactypeid',  $rec->vactypes??[], $rec->aux->vactypeid??null,
                                             [
                                             'id' => 'vactypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             ]) !!}
                                        </div>

                                        <div class="form-group offset-md-0 col-md-4 vt1_show">
                                            <label for="wrkbegdate">За период работы (с):</label>
                                            <input type="date" class="form-control"
                                                   name="wrkbegdate" id="wrkbegdate"
                                                   value="{{ old('wrkbegdate',$rec->aux->wrkbegdate??null) }}"/>
                                        </div>

                                        <div class="form-group offset-md-0 col-md-4 vt1_show">
                                            <label for="wrkenddate">За период работы (по):</label>
                                            <input type="date" class="form-control"
                                                   name="wrkenddate" id="wrkenddate"
                                                   value="{{ old('wrkenddate',$rec->aux->wrkenddate??null) }}"/>
                                        </div>

                                        <div class="form-group offset-md-2 col-md-5 vt1_show">
                                            <label for="privacdays">Кол-во дней основ. отпуска:</label>
                                            <input type="number" class="form-control text-right"
                                                   title="кол-во календарных дней основного отпуска"
                                                   name="privacdays" id="privacdays"
                                                   min="0" step="1"
                                                   value="{{ old('privacdays',$rec->aux->privacdays??null) }}"/>
                                        </div>

                                        <div class="form-group offset-md-0 col-md-5 vt1_show">
                                            <label for="priholdays">Кол-во праздников в основ. отпуске:</label>
                                            <input type="number" class="form-control text-right"
                                                   title="кол-во дней праздников, пришедшихся на основной отпуск"
                                                   name="priholdays" id="priholdays"
                                                   min="0" step="1"
                                                   value="{{ old('priholdays',$rec->aux->priholdays??null) }}"/>
                                        </div>

                                        <div class="form-group offset-md-4 col-md-4 vt1_show">
                                            <label for="pribegdate">Начало осн. отпуска:</label>
                                            <input type="date" class="form-control"
                                                   name="pribegdate" id="pribegdate"
                                                   title="Дата начала основного отпуска"
                                                   value="{{ old('pribegdate',$rec->aux->pribegdate??null) }}"/>
                                        </div>

                                        <div class="form-group offset-md-0 col-md-4 vt1_show">
                                            <label for="prienddate">Посл. день осн. отпуска:</label>
                                            <input type="date" class="form-control"
                                                   title="Последний день осн. отпуска"
                                                   name="prienddate" id="prienddate"
                                                   value="{{ old('prienddate',$rec->aux->prienddate??null) }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-4 vt1_show">
                                            <label for="secvactype" title="Тип дополнительного отпуска">Тип доп.
                                                отпуска:</label>
                                            <input type="text" class="form-control text-center"
                                                   name="secvactype" id="secvactype"
                                                   title="Тип дополнительного отпуска"
                                                   value="{{ old('secvactype',$rec->aux->secvactype??null) }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-2 col-md-5 vt1_show">
                                            <label for="secvacdays">Кол-во дней доп. отпуска:</label>
                                            <input type="number" class="form-control text-right"
                                                   title="кол-во календарных дней основного отпуска"
                                                   name="secvacdays" id="secvacdays"
                                                   min="0" step="1"
                                                   value="{{ old('secvacdays',$rec->aux->secvacdays??null) }}"/>
                                        </div>

                                        <div class="form-group offset-md-0 col-md-5 vt1_show">
                                            <label for="secholdays">Кол-во праздников в доп. отпуске:</label>
                                            <input type="number" class="form-control text-right"
                                                   title="кол-во дней праздников, пришедшихся на основной отпуск"
                                                   name="secholdays" id="secholdays"
                                                   min="0" step="1"
                                                   value="{{ old('secholdays',$rec->aux->secholdays??null) }}"/>
                                        </div>

                                        <div class="form-group offset-md-4 col-md-4 vt1_show">
                                            <label for="secbegdate">Начало доп. отпуска:</label>
                                            <input type="date" class="form-control"
                                                   name="secbegdate" id="secbegdate"
                                                   title="Дата начала основного отпуска"
                                                   value="{{ old('secbegdate',$rec->aux->secbegdate??null) }}"/>
                                        </div>

                                        <div class="form-group offset-md-0 col-md-4 vt1_show">
                                            <label for="secenddate">Посл. день доп. отпуска:</label>
                                            <input type="date" class="form-control"
                                                   title="Последний день осн. отпуска"
                                                   name="secenddate" id="secenddate"
                                                   value="{{ old('secenddate',$rec->aux->secenddate??null) }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-5 vt1_show">
                                            <label for="compensatedays">Кол-во дней компенсации:</label>
                                            <input type="number" class="form-control text-right"
                                                   title="Кол-во дней отпуска, замененных денежной компенсацией"
                                                   name="compensatedays" id="compensatedays"
                                                   min="0" step="1"
                                                   value="{{ old('compensatedays',$rec->aux->compensatedays??null) }}"/>
                                        </div>

                                    </div>

                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-4 col-md-4 vt1_hide">
                                        <label for="begdate">Начало действия:</label>
                                        <input type="date" class="form-control"
                                               name="begdate" id="begdate"
                                               value="{{ old('begdate',$rec->begdate) }}"/>
                                    </div>

                                    <div class="form-group offset-md-0 col-md-4 t10_hide t90_hide vt1_hide ">
                                        <label for="enddate">Окончание действия:</label>
                                        <input type="date" class="form-control"
                                               name="enddate" id="enddate"
                                               value="{{ old('enddate',$rec->enddate) }}"/>
                                    </div>

                                </div>


                                <hr size="1">

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="name">Подпись 1 (за руководителя предприятия):</label>
                                        <div class="input-group mb-3 ">
                                            <input type="text" name="username[]"
                                                   class="ac_orgstaff_name form-control"
                                                   placeholder="-ФИО-"
                                                   value="{{$rec->signer1->name}}">
                                            <input type="text"
                                                   class="form-control text-center small ac_status"
                                                   style="display: none; border: #d7f3e3; " readonly>
                                            <input type="hidden" name="signerid[]"
                                                   class="ac_orgstaff_id"
                                                   value="{{$rec->signer1id}}">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="name">Подпись 2 (за главного бухгалтера):</label>
                                        <div class="input-group mb-3 ">
                                            <input type="text" name="username[]"
                                                   class="ac_orgstaff_name form-control"
                                                   placeholder="-ФИО-"
                                                   value="{{$rec->signer2->name}}">
                                            <input type="text"
                                                   class="form-control text-center small ac_status"
                                                   style="display: none; border: #d7f3e3; " readonly>
                                            <input type="hidden" name="signerid[]"
                                                   class="ac_orgstaff_id"
                                                   value="{{$rec->signer2id}}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="name">Подпись 3 (согласование ОК):</label>
                                        <div class="input-group mb-3 ">
                                            <input type="text" name="username[]"
                                                   class="ac_orgstaff_name form-control"
                                                   placeholder="-ФИО-"
                                                   value="{{$rec->signer3->name}}">
                                            <input type="text"
                                                   class="form-control text-center small ac_status"
                                                   style="display: none; border: #d7f3e3; " readonly>
                                            <input type="hidden" name="signerid[]"
                                                   class="ac_orgstaff_id"
                                                   value="{{$rec->signer3id}}">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="name">Подпись 4 (руководитель сотрудника):</label>
                                        <div class="input-group mb-3 ">
                                            <input type="text" name="username[]"
                                                   class="ac_orgstaff_name form-control"
                                                   placeholder="-ФИО-"
                                                   value="{{$rec->signer4->name}}">
                                            <input type="text"
                                                   class="form-control text-center small ac_status"
                                                   style="display: none; border: #d7f3e3; " readonly>
                                            <input type="hidden" name="signerid[]"
                                                   class="ac_orgstaff_id"
                                                   value="{{$rec->signer4id}}">
                                        </div>
                                    </div>
                                </div>

                                <hr size="1">
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success"
                                            title="Сохранить изменения">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif

                                <a class="btn btn-close btn-info" href="{{ $retURL }}">
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

                                @if ($usrrights['print'])
                                    <span>
                    <a class="btn btn-close btn-warning ml-3 btn-sm "
                       href="{{ route($sysobjcode.'.print', $rec->id) }}"
                       target="_blank"
                       title="Напечатать">
                        <i class="fa fa-print" aria-hidden="true"></i>
                    </a>
                </span>
                                @endif

                                @include('layouts._who_when')
                            </div>
                        </form>
                        &nbsp;
                    </div>
                </div>

                <div class="col-md-6 col-sm-12">

                </div>
            </div>

        </div>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

        <script src="{{ asset('js/stforder_edit.js') }}" defer></script>
    @endif
@endsection
