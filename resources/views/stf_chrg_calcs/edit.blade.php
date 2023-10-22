@extends('layouts.edit')
@section('content')

    <style>
        label {
            color: gray;
            margin-bottom: 0px;
        }
    </style>
    @guest
        <?php
        redirect()->route('login');
        //Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();
        ?>
    @else
        @if (!isset( $rec))
            <?php
            redirect()->route('home');
            header("Location:" . route('home'));
            die();
            ?>
        @else
            <?php
            $thisSysObjId = 1212;
            $sysobjid = $thisSysObjId;
            $sysobjcode = 'stf_chrg_calcs';
            $thisTitle = "Регистрация начисления/удержания для сотрудника";

            $retRoute = $rec->retURL;

            //для блокировки текстовых полей пользователям, не имеющим право на редактирование
            $inputReadOnly = "readonly";
            if ($usrrights['save']) $inputReadOnly = "";

            //Отображать или нет Цену/Сумму определяется типом документа
            //$showPrice = ($rec->orgcharge->chargetype->useprice == 1);
               // dd($rec->org_charge->chargetype->use_price);
            $showPrice = ($rec->org_charge->chargetype->use_price == 1);
            //$usrrights['save'] = (1 == 0);

            $sum_ro = '';
            if ($showPrice) {
                $sum_ro = 'readonly';
            }
            ?>

            <div class="container">

                @include('layouts.edit_msgs')

                <div class="row ">

                    <div class="col-md-7">
                        <div class="card mt-3">
                            <div class="card-header">
                                {{$thisTitle}}
                                <a class="btn btn-close btn-light btn-sm"
                                   style="float:right;"
                                   href="{{ $retRoute }}"
                                   title="Вернуться">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body">

                                @include('layouts.err_msgs')

                                <form name="forEdit" id="forEdit" method="post"
                                      action="{{ route($sysobjcode.'.update', $rec->id) }}">
                                    @method('PUT')
                                    @csrf
                                    {{ Form::hidden('id', $rec->id, ['id'=>'id']) }}
                                    {{ Form::hidden('retURL', $rec->retURL, ['id'=>'retURL']) }}

                                    <div class="row">
                                        <div class="form-group col-md-12 driver_info" style="">
                                            <label for="name" class="required">Сотрудник: </label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group mb-3 ">
                                                    <input type="text" name="staff_name" id="staff_name"
                                                           class="staff_name form-control ac_name font-weight-bold"
                                                           {{$inputReadOnly}}
                                                           value="{{old('staff_name',$rec->_obj_info)}}">
                                                    <input type="text" class="form-control text-center small ac_status"
                                                           style="display: none; border: #d7f3e3; max-width: 30px"
                                                           readonly>
                                                    <input type="hidden" name="staffid" class="ac_id" id="staffid"
                                                           value="{{old('staffid', $rec->staffid)}}">
                                                </div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->_obj_info}}</div>
                                                <input type="hidden" name="staffid" id="staffid"
                                                       value="{{$rec->staffid}}">
                                            @endif
                                            <input type="hidden" name="orgid" id="orgid"
                                                   value="{{$rec->orgstaff->orgid}}">
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <label for="orgcharge_name" class="required">Тип
                                                начисления/удержания:</label>
                                            <div class="input-group">
                                                @if ($usrrights['save'])
                                                    <div class="input-group mb-3 "><input type="text"
                                                                                          name="orgcharge_name"
                                                                                          id="orgcharge_name"
                                                                                          class="orgcharge_name form-control ac_name font-weight-bold"
                                                                                          value="{{old('orgcharge_name',$rec->org_charge->chargetype->name)}}">
                                                        <input type="text"
                                                               class="form-control text-center small ac_status"
                                                               style="display: none; border: #d7f3e3; max-width: 30px"
                                                               readonly>
                                                        <input type="hidden" name="orgchargeid" class="ac_id"
                                                               id="orgchargeid"
                                                               value="{{old('orgchargeid', $rec->orgchargeid)}}">
                                                        <a class="btn btn-light" id="orgchargeid_lnk"
                                                           target="_blank">
                                                            <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                        </a>
                                                    </div>
                                                @else
                                                    <div
                                                        class="font-weight-bold">{{$rec->org_charge->chargetype->name}}</div>
                                                    <input type="hidden" name="orgchargeid" id="orgchargeid"
                                                           value="{{$rec->orgchargeid}}">
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        @if ($showPrice)
                                            <div class="offset-md-3 col-md-3 offset-sm-4 col-sm-4 col-xs-6">
                                                <div class="form-group list-inline">
                                                    <label for="">Количество, ЕИ:</label>
                                                    @if ($usrrights['save'])
                                                        <input type="text"
                                                               class="form-control text-right "
                                                               id="charge_qty" name="charge_qty"
                                                               value="{{$rec->charge_qty}}"
                                                        />
                                                    @else
                                                        <div
                                                            class="font-weight-bold text-right">
                                                            {{number_format($rec->charge_qty,2)}}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="offset-md-0 col-md-3 col-sm-4 col-xs-6">
                                                <div class="form-group list-inline">
                                                    <label for="price">Ставка, &#x20bd;/ЕИ:</label>
                                                    @if ($usrrights['save'])
                                                        <input type="text" class="form-control text-right bold"
                                                               id="charge_price" name="charge_price"
                                                               value="{{$rec->charge_price}}"
                                                        />
                                                    @else
                                                        <div
                                                            class="font-weight-bold text-right">
                                                            {{number_format($rec->charge_price,2)}}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="offset-md-3 col-md-6 col-sm-4 col-xs-6">
                                            </div>
                                        @endif

                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="charge_sum" class="required">Сумма, &#8381;:</label>
                                            @if ($usrrights['save'])
                                                <input type="number"
                                                       class="charge_sum form-control font-weight-bold text-right"
                                                       name="charge_sum" id="charge_sum"
                                                       {{$sum_ro}}
                                                       value="{{ old('charge_sum',$rec->charge_sum) }}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-right">
                                                    {{number_format($rec->charge_sum,2)}}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="name" class="required">Дата:</label>
                                            @if ($usrrights['save'])
                                                <input type="date" class="form-control text-center font-weight-bold"
                                                       name="docdate" id="docdate"
                                                       min="{{$rec->wrkdate_min}}"
                                                       max="{{today()->format('Y-m-d')}}"
                                                       value="{{old('docdate',$rec->docdate)}}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">
                                                    {{date_create($rec->docdate)->format('d.m.Y')}}
                                                    {{ Form::hidden('docdate', $rec->docdate,['id'=>'docdate']) }}
                                                </div>
                                            @endif
                                        </div>
                                        {{--                                        <div class="form-group offset-md-0 col-md-4">--}}
                                        {{--                                            <label for="name" class="required">№ документа:</label>--}}
                                        {{--                                            @if ($usrrights['edit'])--}}
                                        {{--                                                <input type="text" class="form-control text-center font-weight-bold"--}}
                                        {{--                                                       name="docnum" id="docnum"--}}
                                        {{--                                                       value="{{old('docnum',$rec->docnum)}}"/>--}}
                                        {{--                                            @else--}}
                                        {{--                                                <div--}}
                                        {{--                                                    class="font-weight-bold text-center">--}}
                                        {{--                                                    {{$rec->docnum}}--}}
                                        {{--                                                    {{ Form::hidden('docnum', $rec->docnum,['id'=>'docnum']) }}--}}
                                        {{--                                                </div>--}}
                                        {{--                                            @endif--}}
                                        {{--                                        </div>--}}
                                        <div class="form-group offset-md-0 col-md-8">
                                            <label for="name" class="">Примечание:</label>
                                            @if ($usrrights['save'])
                                                <input type="text" class="form-control" name="notes" maxlength="160"
                                                       value="{{ old('notes',$rec->notes) }}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">
                                                    {{$rec->notes}}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{--                                    <div class="row">--}}
                                    {{--                                        <div class="form-group offset-md-4 col-md-8">--}}
                                    {{--                                            <label for="name" class="required">За период работы:</label>--}}
                                    {{--                                            @if ($usrrights['edit'])--}}
                                    {{--                                                <div class="input-group">--}}
                                    {{--                                                    <input type="date" class="form-control text-center font-weight-bold"--}}
                                    {{--                                                           name="forbegdate" id="forbegdate" required--}}
                                    {{--                                                           min="{{$rec->wrkdate_min}}"--}}
                                    {{--                                                           max="{{today()->format('Y-m-d')}}"--}}
                                    {{--                                                           value="{{old('forbegdate',$rec->forbegdate)}}"/>--}}
                                    {{--                                                    &nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;--}}
                                    {{--                                                    <input type="date" class="form-control text-center font-weight-bold"--}}
                                    {{--                                                           name="forenddate" id="forenddate" required--}}
                                    {{--                                                           min="{{$rec->wrkdate_max}}"--}}
                                    {{--                                                           --}}{{--                                                           max="{{today()->format('Y-m-d')}}"--}}
                                    {{--                                                           value="{{old('forenddate',$rec->forenddate)}}"/>--}}
                                    {{--                                                </div>--}}
                                    {{--                                            @else--}}
                                    {{--                                                <div--}}
                                    {{--                                                    class="font-weight-bold text-center">--}}
                                    {{--                                                    {{date_create($rec->forbegdate)->format('d.m.Y')}} ---}}
                                    {{--                                                    {{date_create($rec->forenddate)->format('d.m.Y')}}--}}
                                    {{--                                                    {{ Form::hidden('forbegdate', $rec->begdate,['id'=>'forbegdate']) }}--}}
                                    {{--                                                    {{ Form::hidden('forenddate', $rec->forenddate,['id'=>'forenddate']) }}--}}
                                    {{--                                                </div>--}}
                                    {{--                                            @endif--}}
                                    {{--                                        </div>--}}
                                    {{--                                        --}}{{--                                        <div class="form-group offset-md-0 col-md-2">--}}
                                    {{--                                        --}}{{--                                            <label for="active" style="color:rgb(73, 80, 87);">Действует:</label>--}}
                                    {{--                                        --}}{{--                                            {!! Form::checkbox('active', 1, $rec->active==1,['class'=>'form-control']) !!}--}}
                                    {{--                                        --}}{{--                                        </div>--}}
                                    {{--                                    </div>--}}

                                    {{--                                    <div class="row">--}}
                                    {{--                                        <div class="form-group offset-md-0 col-md-12">--}}
                                    {{--                                            <label for="name" class="">Примечание:</label>--}}
                                    {{--                                            <input type="text" class="form-control" name="notes" maxlength="160"--}}
                                    {{--                                                   value="{{ old('notes',$rec->notes) }}"/>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}


                                    @if ($usrrights['save'])
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                            Сохранить
                                        </button>
                                    @endif
                                    &nbsp;
                                    <a class="btn btn-close btn-info" href="{{ $retRoute }}">
                                        <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                        Закрыть
                                    </a>
                                    &nbsp;
                                    @if ($usrrights['delete'])
                                        <a class="btn btn-danger btn-sm"
                                           style="margin-left:24px"
                                           href="{{ route($sysobjcode.'.delete', $rec->id)}}"

                                           onclick="return confirm('Вы действительно хотите удалить запись?')"
                                           title="Удалить запись"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                </form>

                                @include('layouts._who_when')
                            </div>

                        </div>
                    </div>

                    @if($rec->id<>-1)
                        <div class="col-md-5">
                        </div>
                    @endif

                </div>
            </div>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"
                    integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
            <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
            <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
            <script src="{{ asset('js/stf_charge_calc_edit.js') }}" defer></script>

        @endif
    @endguest
@endsection
