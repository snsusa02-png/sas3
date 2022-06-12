@extends('layouts.edit')

@section('content')
    @guest
        <?php

        redirect()->route('login');
        //Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();


        ?>
    @else
        @if (!isset( $rec ))
            <?php
            redirect()->route('wrhs.index');
            header("Location:" . route('wrhs.index'));
            die();
            ?>
        @else
            <?php
            if (!$usrrights['read']) {
                redirect()->route('admin');
                header("Location:" . route('admin'));
                die();
            }
            $sysobjid = 210;
            $thisSysObjCode = 'wrh_boxes';
            $thisTitle = "Отделение склада";

            $retURL = \Request::get('returl') ?? $rec->retURL ?? (route($thisSysObjCode . '.index') . "?page=" . session($thisSysObjCode . '_pageno') . '#' . $rec->id);
            ?>
            <style>

                label {
                    color: gray;
                    margin-bottom: 0px;
                }
            </style>

            <div class="container">

                @include('layouts.edit_msgs')

                <div class="row ">
                    <div class="col-md-7">
                        <div class="card mt-1">
                            <div class="card-header">
                                {{$thisTitle}}
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
                                      action="{{ route($thisSysObjCode.'.update', $rec->id) }}">
                                    @method('PUT')
                                    @csrf
                                    {{ Form::hidden('wrhid', old('wrhid',$rec->wrhid),['id'=>'wrhid']) }}

                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <label for="name">Склад:</label>
                                            <div class="font-weight-bold">{{$rec->wrh->name}}</div>
                                        </div>
                                    </div>

                                    <div class="row">
{{--                                        <div class="form-group col-md-2">--}}
{{--                                            <label for="name">Код:</label>--}}
{{--                                            <input type="text" class="form-control" name="code"--}}
{{--                                                   value="{{old('code',$rec->code)}}"--}}
{{--                                                   maxlength="16"/>--}}
{{--                                        </div>--}}
                                        <div class="form-group col-md-12">
                                            <label for="name">Название:</label>
                                            <input type="text" class="form-control" name="name"
                                                   value="{{old('name',$rec->name)}}"
                                                   maxlength="60"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-4 col-md-8">
                                            <label for="active" style="color: rgb(73, 80, 87);">Для объекта:</label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('buildobjid', $rec->buildobjs??[], $rec->buildobjid,
                                                 [
                                                 'id' => 'buildobjid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '-выбор-',
                                                 ]) !!}
                                            @else
                                                <div class="font-weight-bold">{{$rec->buildobj->name}}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-4 col-md-8">
                                            <label for="active" style="color: rgb(73, 80, 87);">Для вида работ:</label>

                                            @if ($usrrights['save'])
                                                {!! Form::select('buildopertypeid', $rec->buildopertypes??[], $rec->buildopertypeid,
                                                 [
                                                 'id' => 'buildopertypeid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '-выбор-',
                                                 ]) !!}
                                            @else
                                                <div class="font-weight-bold">{{$rec->buildopertype->name}}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-4 col-md-8">
                                            <label for="active" style="color: rgb(73, 80, 87);">Для подрядчика:</label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('orgcontractid', $rec->orgcontracts??[], $rec->orgcontractid,
                                                 [
                                                 'id' => 'orgcontractid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '-выбор-',
                                                 ]) !!}
                                            @else
                                                <div class="font-weight-bold">{{$rec->contract->name}}</div>
                                            @endif

                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <label for="active" style="color: rgb(73, 80, 87);">Доступен:</label>
                                            {!! Form::checkbox('active', 1, $rec->active==1,['class'=>'form-control']) !!}
                                        </div>
                                    </div>


                                    <hr size="1">
                                    @if ($usrrights['save'])
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                            Сохранить
                                        </button>
                                    @endif
                                    <a class="btn btn-close btn-info" href="{{ $retURL }}">
                                        <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                        Закрыть
                                    </a>
                                    @if ($usrrights['delete'] )
                                        <button type="submit"
                                                class="btn btn-danger"
                                                style="margin-left:24px"
                                                formaction="{{ route($thisSysObjCode.'.delete', $rec->id)}}"
                                                formmethod="post"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                                title="Удалить"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                    @if($rec->id<>-1)
                                        <div class="small" style="margin-top: 8px;">
                                            создана: {{$rec->created_at}} / {{$rec->whocrt->name}}
                                            &nbsp;&nbsp;
                                            изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}}
                                            <a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-5">
                        @include('wrh_boxes._stocks')
                    </div>

                </div>

                <script src="{{ asset('js/wrh_box_edit.js') }}" defer></script>

            </div>
        @endif
    @endguest
@endsection
