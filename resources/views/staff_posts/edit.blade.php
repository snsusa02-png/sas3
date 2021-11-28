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
        $sysobjid = 1205;
        $sysobjcode = 'staff_posts';
        $thisTitle = "Должность";

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
                            {!! Form::hidden('orgid', $rec->orgid) !!}

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
                                    <label for="sysobjid">Сотрудник:</label>
                                    <b>{{$rec->staff->name}}</b>
                                </div>


                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="orgid">Организация:</label>
                                        <div>
                                            <input type="hidden" name="orgid" value="{{$rec->orgid}}">
                                            <b>{{$rec->staff->org->name}}</b>
                                        </div>
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label for="depname" class="required">Подразделение:</label>
                                        <div class="input-group">

                                            {!! Form::select('depid', $rec->orgdeps??[], $rec->depid,
                                             [
                                             'id' => 'depid',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             ]) !!}

                                            <input type="text" class="form-control" name="depname"
                                                   id="depname"
                                                   maxlength="60"
                                                   value="{{ old('depname',$rec->depname) }}"/>

                                            <a class="btn btn-light" id="depid_lnk"
                                               target="_blank">
                                                <i class="fa fa-info text-info" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </div>

                                </div>


                                <div class="row">

                                    <div class="form-group col-md-7">
                                        <label for="postid" class="required">Должность:</label>
                                        <div class="input-group">
                                            {!! Form::select('postid', $rec->orgposts??[], $rec->postid,
                                             [
                                             'id' => 'postid',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             ]) !!}

                                            <input type="text" class="form-control" name="postname"
                                                   id="postname" maxlength="60"
                                                   value="{{ old('postname',$rec->postname) }}"/>

                                            <a id="postid_lnk" class="btn btn-light">
                                                <i class="fa fa-info text-info" aria-hidden="true"></i>
                                            </a>

                                        </div>
                                    </div>

                                    <div class="form-group offset-md-0 col-md-2" id="jobfraction_div">
                                        <label for="jobfraction">Ставка:</label>
                                        <input type="number" class="form-control text-right"
                                               name="jobfraction" id="jobfraction"
                                               min="0" step="0.25" max="1.75"
                                               value="{{ old('jobfraction',$rec->jobfraction) }}"/>
                                    </div>

                                </div>


                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-5">
                                        <label for="begdate">Дата вступления в должность:</label>
                                        <input type="date" class="form-control"
                                               name="begdate" id="begdate"
                                               value="{{ old('begdate',$rec->begdate) }}"/>
                                    </div>

                                    <div class="form-group offset-md-0 col-md-5">
                                        <label for="enddate">Дата окончания:</label>
                                        <input type="date" class="form-control"
                                               name="enddate" id="enddate"
                                               value="{{ old('enddate',$rec->enddate) }}"/>
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

        {{--        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">--}}
        {{--        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>--}}
        {{--        <script src="{{ asset('js/callListStaff.js') }}" defer></script>--}}
        {{--        <script src="{{ asset('js/obj_orgs_edit.js') }}" defer></script>--}}

        <script src="{{ asset('js/orgstaff_edit.js') }}" defer></script>
    @endif
@endsection
