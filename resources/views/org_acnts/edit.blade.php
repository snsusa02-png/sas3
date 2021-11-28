@extends('layouts.edit')

@section('content')

    @if (!isset( $rec ))
        <?php
        redirect()->route('orgs.index');
        header("Location:" . route('orgs.index'));
        die();
        ?>
    @else
        <?php
        $sysobjid = 115;
        $sysobjcode = 'org_acnts';
        $ThisTitle = "Банковские реквизиты";
        $retRoute = route('orgs.edit', $rec->orgid);


        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        $inputReadOnly = "readonly";
        if ($usrrights['save']) $inputReadOnly = "";

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

            .btn {
                margin-bottom: 4px;
            }

            .ui-menu-item .ui-menu-item-wrapper:hover {
                /*border: none !important;*/
                border: 1px solid snow;
                color: #222222;
                background-color: lightyellow;
            }


        </style>
        <div class="container">

            <div class="row">
                <div class="col-md-7 col-sm-12">

                    @include('layouts.edit_msgs')

                    <div class="card mt-3">

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', $rec->id) }}">

                            @method('PUT')
                            @csrf
                            {{ Form::hidden('orgid', $rec->orgid) }}
                            <div class="card-header">
                                {{$ThisTitle}}

                                <a class="btn btn-close btn-info btn-sm"
                                   style="float:right;"
                                   href="{{ $retRoute }}"
                                   title="Вернуться">
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
                                    </div>
                                @endif

                                <div class="form-group">
                                    <label for="name">Контрагент:</label>
                                    <div class="mb-3 ">
                                        {{$rec->org->name}}
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-7">
                                        <label for="name">Банк:</label>
                                        <div class="input-group mb-3 ">
                                            <input type="text" class="form-control" name="bankname"
                                                   id="bankname"
                                                   value="{{old('bankname',$rec->bankname) }}"
                                            />
                                        </div>
                                    </div>

                                    <div class="form-group col-md-5">
                                        <label for="name">Р/счет:</label>
                                        <div class="input-group mb-3 ">
                                            <input type="text" class="form-control" name="rs_num"
                                                   id="rs_num" maxlength="20"
                                                   value="{{old('rs_num',$rec->rs_num) }}"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-3 col-md-4">
                                        <label for="address">БИК:</label>
                                        <div class="input-group mb-3 ">
                                            <input type="text" class="form-control" name="bic"
                                                   id="bic" maxlength="9"
                                                   value="{{old('bic',$rec->bic) }}"
                                            />
                                        </div>
                                    </div>
                                    <div class="form-group  col-md-5">
                                        <label for="name">Кор/счет:</label>
                                        <div class="input-group mb-3 ">
                                            <input type="text" class="form-control" name="cs_num"
                                                   id="cs_num" maxlength="20"
                                                   value="{{old('cs_num',$rec->cs_num) }}"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-7">
                                        <label for="name">Адрес банка:</label>
                                        <div class="input-group mb-3 ">
                                            <input type="text" class="form-control" name="bankaddr"
                                                   id="bankaddr"
                                                   value="{{old('bankaddr',$rec->bankaddr) }}"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-2 col-md-6">
                                        <label for="forpay" style="color: rgb(73, 80, 87);">Можно использовать для
                                            оплаты счетов:</label>
                                        {!! Form::checkbox('forpay', 1, $rec->forpay==1,
                                         [
                                         'class' => 'form-control',
                                         ]) !!}
                                    </div>
                                    <div class="offset-md-0 col-md-3">
                                        <div class="form-group">
                                            <label for="active">Действующий:</label>
                                            {!! Form::checkbox('active', 1, $rec->active==1, ['class="form-control"']) !!}
                                        </div>
                                    </div>
                                <!--
									<div class="form-group offset-md-4 col-md-6">
										<label for="name">Порядок вывода (1-255):</label>
										<input type="text" class="form-control text-right" name="ordr"
											   value="{{ $rec->ordr }}"/>
									</div>
									-->
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
                                <div class="card-footer small" style="color: gray; margin:8px;">
                                    создана: {{$rec->created_at}} / {{$rec->whocrt->name}} &nbsp;
                                    изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}} &nbsp;
                                    <a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-5 col-sm-12">

                <?php $FlagsHeader = "Состояние " ?>
                @include('objflags/objflags')

            </div>

        </div>

@endsection
@endif
