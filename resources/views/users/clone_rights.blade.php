@extends('layouts.edit')

@section('content')
    @if (!isset( $data ))
        <?php
        redirect()->route('users.index');
        header("Location:" . route('users.index'));
        die();
        ?>
    @endif

    <?php
    $sysobjid = 3;
    $ThisTitle = "Копирование прав от пользователя";

    ?>
    {{--dd(get_defined_vars())--}}
    <style>
        .sysobjname {
            background-color: #d2f2fa;
            padding-left: 8px;
        }

        .rights_list ul li {
            font-size: 0.8em;
        }

        label {
            color: gray;
            margin-bottom: 0px;
        }

    </style>
    <div class="container">


        <div class="row ">
            <div class="col-md-10">

                @include('layouts.edit_msgs')

                <div class="card mt-3">
                    <div class="card-header">
                        {{$ThisTitle}}
                    </div>
                    <div class="card-body">

                        @include('layouts.err_msgs')

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route('users.clone_rights', $data->tgt_id) }}">
                            {{--                                @method('PUT')--}}
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="name" class="required">Пользователь - источник прав:</label>
                                    <input type="hidden" name="src_id" class="userid" value="{{ $data->src_id }}">
                                    <input type="text" class="form-control text-left username"
                                           name="src_name" required
                                           value="{{ $data->src_name }}"/>
                                    <input type="text"
                                           class="form-control text-center small ac_status"
                                           style="display: none; border: #d7f3e3; " readonly>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="name">Пользователь - получатель прав:</label>
                                    <input type="hidden" name="tgt_id" value="{{ $data->tgt_id }}">
                                    <input type="text" class="form-control text-left" name="tgt_name" readonly
                                           value="{{ $data->tgt_name }}"/>
                                </div>
                            </div>

                            <hr size="1">
                            @if ($usrrights['save'])
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                    Показать права
                                </button>
                            @endif
                            <a class="btn btn-close btn-info" href="{{ $data->returl }}">
                                <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                Закрыть
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        @if (isset($sysfunclst))

            <div class="row">
                <div class="col-md-10">
                    <div class="card mt-2">
                        <div class="card-header">
                            <h4 class="mb-0">Новые права</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-10">

                    <form
                        action="{{ route('users.save_cloned_rights', ['id'=>$data->tgt_id, 'limsysobjid'=>$data->limsysobjid??0, 'limobjid'=>$data->limobjid??0]) }}">
                        {{ Form::hidden('retURL', $data->returl) }}

                        <div class="accordion" id="accordionRights">

                            <?php $prevObjID = ""; ?>
                            @foreach($sysfunclst as $itm)
                                @if ($prevObjID != $itm->objid)
                                    @if ($prevObjID != "")
                        </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header" id="heading{{$itm->objid}}">
                <h2 class="mb-0">
                    <button type="button" class="btn btn-link sysobjgroup0" data-toggle="collapse"
                            data-target="#collapse{{$itm->objid}}">
                        <b>{{$itm->objname}}</b> ({{$itm->objcode}})
                    </button>
                </h2>
            </div>
            <div id="collapse{{$itm->objid}}" class="collapse show" aria-labelledby="heading{{$itm->objid}}"
                 data-parent="#accordionRights">
                <div class="card-body">

                    <?php $prevObjID = $itm->objid; ?>
                    @endif
                    <?php
                    if (!isset($itm->adminrightid)) {
                        $readonly = " disabled";
                    } else {
                        $readonly = "";
                    }

                    $checked = "";
                    $whowhn = "";
                    $checked = "checked";
                    ?>

                    <div class="row">
                        <div class="offset-md-1 col-md-11">
                            <input type="checkbox" {{$checked}} id="cb{{$itm->id}}"
                                   name="rightid[]"
                                   value="{{$itm->id}}"
                                {{$readonly}}
                            />
                            <label for="cb{{$itm->id}}">
                                {{$itm->funcname}} ({{$itm->id}})

                            </label>
                            <span class='whowhn'>
							{{$whowhn}}
						</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>


        <div class="row" id="actions0">
            <div class="col-md-12 text-center my-3 ">
                <button type="submit" class="btn btn-success">
                    <i class="fa fa-floppy-o" aria-hidden="true"></i>
                    Добавить отмеченные права
                </button>

                <a class="btn btn-close btn-info" href="{{ route('users.edit', $data->tgt_id) }}">
                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                    Закрыть
                </a>
            </div>
        </div>

        </form>

    </div>
    </div>
    @endif

    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
    {{--                <script src="{{ asset('js/callListStaff.js') }}" defer></script>--}}
    <script src="{{ asset('js/user_clone_rights.js') }}" defer></script>

@endsection
