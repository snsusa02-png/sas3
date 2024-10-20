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
            $thisSysObjId = 1951;
            $sysobjid = $thisSysObjId;
            $sysobjcode = 'obj_expenses';
            $thisTitle = "Доп. затраты";

            $retRoute = $rec->retURL;

            //для блокировки текстовых полей пользователям, не имеющим право на редактирование
            $inputReadOnly = "readonly";
            if ($usrrights['save']) $inputReadOnly = "";
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
                                    {{ Form::hidden('sysobjid', $rec->sysobjid) }}
                                    {{ Form::hidden('objid', $rec->objid) }}


                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="name" class="">Для:</label>
                                            <input type="text" class="form-control"
                                                   readonly
                                                   value="{{ $rec->_obj_info }}"/>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="name" class="">Организация:</label>
                                            <input type="hidden" name="orgid" value="{{$rec->orgid}}">
                                            <input type="text" class="form-control"
                                                   readonly
                                                   value="{{ $rec->org->name }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="expensetypeid" class="required">Тип затрат:</label>
                                            {!! Form::select('expensetypeid',  $rec->expensetypes??[], $rec->expensetypeid??null,
                                             [
                                             'id' => 'expensetypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             'required' => 'required',
                                             ]) !!}
                                        </div>
                                        <div class="form-group offset-md-0 col-md-8">
                                            <label for="name" class="required">Основание:</label>
                                            <input type="text" class="form-control" name="reason" maxlength="160"
                                                   required
                                                   value="{{ old('reason',$rec->reason) }}"/>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="name" class="required">Дата учета:</label>
                                            @if ($usrrights['save'])
                                                <input type="date" class="form-control text-center font-weight-bold required"
                                                       name="operdate" id="operdate" required
                                                       min="{{$rec->operdate_min}}"
                                                       max="{{today()->format('Y-m-d')}}"
                                                       value="{{old('operdate',$rec->operdate)}}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">{{date_create($rec->operdate)->format('d.m.Y')}}
                                                    {{ Form::hidden('operdate', $rec->operdate,['id'=>'operdate']) }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="name" class="" id="lbl_raid_qty">Кол-во, ЕИ:</label>
                                            @if ($usrrights['save'])
                                                <div class="input-group mb-3 ">
                                                    <input type="number" name="qty" id="qty"
                                                           class="form-control text-right font-weight-bold"
                                                           min="0" step="0.001"
                                                           value="{{old('qty',$rec->qty)}}">
                                                </div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->qty}}</div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="name" class="" title="Цена">Цена, &#8381;:</label>
                                            @if ($usrrights['save'])
                                                <div class="input-group mb-3 ">
                                                    <input type="number" name="price" id="price"
                                                           title="Цена за ЕИ"
                                                           class="form-control text-right font-weight-bold"
                                                           min="0" step="0.01"
                                                           value="{{old('price',$rec->price)}}">
                                                </div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->price}}</div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="name" class="required" title="Общая стоимость">Сумма, &#8381;:</label>
                                            @if ($usrrights['save'])
                                                <div class="input-group mb-3 ">
                                                    <input type="number" name="expense_sum" id="expense_sum" required
                                                           title="Сумма затрат"
                                                           class="form-control text-right font-weight-bold"
                                                           min="0" step="0.01"
                                                           value="{{old('expense_sum',$rec->expense_sum)}}">
                                                </div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->expense_sum}}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="active" style="color:rgb(73, 80, 87);">актуально:</label>
                                        {!! Form::checkbox('active', 1, $rec->active==1) !!}
                                    </div>

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
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                style="margin-left:24px"
                                                formaction="{{ route($sysobjcode.'.delete', $rec->id)}}?returl={{$retRoute}}"
                                                formmethod="post"
                                                title="Удалить"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
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

            <script src="{{ asset('js/obj_expense_edit.js') }}" defer></script>
        @endif
    @endguest
@endsection
