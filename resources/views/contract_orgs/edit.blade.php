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
        $sysobjid = 152;
        $sysobjcode = 'contract_orgs';
        $thisTitle = "Участник";

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
                            {!! Form::hidden('contractid', $rec->contractid) !!}

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

                                <div class="form-group">
                                    <label for="syscontractid">Для записи:</label>
                                    "<b>{{$rec->objname}}"</b>,<br> тип: "{{$rec->sysobj->name}}"
                                </div>


                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="name">Организация, Роль:</label>
                                        <?php
                                        $imax = ($rec->id == -1) ? 7 : 1;
                                        //dd($rec);
                                        ?>
                                        @if ($usrrights['save'])
                                            @for ($i = 0; $i < $imax; $i++)
                                                <div class="input-group mb-3 ">
                                                    <input type="text" name="orgname[]"
                                                           class="ac_orgname form-control"
                                                           value="{{old('org_name'.$i,$rec->org->name)}}">
                                                    <input type="text"
                                                           class="form-control text-center small ac_status"
                                                           style="display: none; border: #d7f3e3; " readonly>
                                                    <input type="hidden" name="orgid[]" class="ac_orgid"
                                                           value="{{old('org_name'.$i,$rec->orgid)}}">

                                                    <span style='width:190px'>
{{--                                                        {!! Form::select('roletypeid[]', $rec->roletypes??[], $rec->roletypeid,--}}
                                                        {!! Form::select('roletypeid[]', $rec->roletypes??[], $rec->roleid,
                                                                 [
                                                                 'class' => 'form-control',
                                                                 'placeholder' => '',
                                                                ]) !!}
                                                    </span>
                                                </div>
                                            @endfor
                                        @else
                                            <div class="font-weight-bold">{{$rec->org->name}}</div>
                                        @endif
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

                                @if ($rec->id != -1)
                                    <div class="small" style="color: gray; margin:8px;">
                                        создана: {{$rec->created_at}} / {{$rec->whocrt->name}} &nbsp;
                                        изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}} &nbsp;
                                        <a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                    </div>
                                @endif
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
        <script src="{{ asset('js/callListStaff.js') }}" defer></script>
        <script src="{{ asset('js/obj_orgs_edit.js') }}" defer></script>
    @endif
@endsection
