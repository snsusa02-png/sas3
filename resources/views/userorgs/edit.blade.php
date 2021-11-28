@extends('layouts.edit')

@section('content')
    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

    <script src="{{ asset('js/ri_ac_userorgs.js') }}" defer></script>
    <script src="{{ asset('js/callListOrgs.js') }}" defer></script>

    @if (!isset( $rec ))
        <?php
        redirect()->route('userorgs.search');
        header("Location:" . route('userorgs.search'));
        die();
        ?>
    @else
        <?php
        $sysobjid = 126;
        $ThisTitle = "Представляемая организация";
        $retRoute = route('users.edit', $rec->userid);


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
                              action="{{ route('userorgs.update', $rec->id) }}">

                            @method('PUT')
                            @csrf
                            {{ Form::hidden('userid', $rec->userid) }}
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
                                    <label for="name">Пользователь:</label>
                                    <div class="mb-3 ">
                                        {{$rec->user->name}}
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="name">Организация:</label>
                                    <div class="input-group mb-3 ">
                                        @if ($rec->id==-1)
                                            <input type="text" class="form-control" name="orgname"
                                                   id="orgname"
                                                   value="{{$rec->orgname }}"
                                                {{--											{{$inputMode}}--}}
                                            />
                                            <input type="text" class="form-control text-center small"
                                                   style="display: none; border: #d7f3e3;" id="ac_orgid" readonly>
                                            <input type="hidden" name="orgid" id="orgid"
                                                   value="{{$rec->orgid}}">


                                            @if (1==1 and $usrrights['save'])
                                                <div class="input-group-append">
                                                    <a onclick="callListOrgs({{$rec->orgid}})" title="Поиск"
                                                       class="btn btn-sm btn-primary form-control">
                                                        <i class="fa fa-search" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            @endif
                                        @else
                                            {{ Form::hidden('orgid', $rec->orgid) }}
                                            <input type="text" name="orgname"
                                                   class="form-control" readonly
                                                   value="{{$rec->org->name}} (ИНН:{{$rec->org->inn}}, КПП:{{$rec->org->kpp}})"
                                            />
                                        @endif
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="name">Должность:</label>
                                    <div class="input-group mb-3 ">
                                        <input type="text" class="form-control" name="postname"
                                               id="postname"
                                               value="{{$rec->postname }}"
                                            {{--											{{$inputMode}}--}}
                                        />
                                    </div>
                                </div>


                                @if(1==0)
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="active">Может контролировать все заказы данной организации
                                                    (клиента):</label>
                                                {!! Form::checkbox('curator', 1, $rec->curator==1, ['class="form-control"']) !!}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="row">
                                    <div class="col-md-5">
                                        <label for="name">Начало:</label>
                                        <input type="date" class="form-control text-center" name="begdt"
                                               value="{{date("Y-m-d",strtotime($rec->begdt))}}"/>
                                    </div>
                                    <div class="col-md-5">
                                        <label for="name">Окончание:</label>
                                        @php($v=is_null($rec->enddt)?null:date("Y-m-d",strtotime($rec->enddt)))
                                        <input type="date" class="form-control text-center" name="enddt"
                                               value="{{$v}}"/>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="acs_contracts">Давать доступ к договорам организации:</label>
                                            {!! Form::checkbox('acs_contracts', 1, $rec->acs_contracts==1, ['class="form-control"']) !!}
                                        </div>
                                    </div>
{{--                                    <div class="col-md-6">--}}
{{--                                        <div class="form-group">--}}
{{--                                            <label for="acs_contracts">Доступ к плану платежей организации:</label>--}}
{{--                                            {!! Form::checkbox('acs_orgplnpays', 1, $rec->acs_orgplnpays==1, ['class="form-control"']) !!}--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="active">Действует:</label>
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
                                            formaction="{{ route('userorgs.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись об акции?')"
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

                <div class="col-md-5 col-sm-12">

                    @include('userorgs._usrsysrights')

                    <?php $FlagsHeader = "Состояние " ?>
                    @include('objflags/objflags')

                </div>

            </div>

        </div>

@endsection
@endif
