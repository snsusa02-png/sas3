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
            redirect()->route('idcards.index');
            header("Location:" . route('idcards.index'));
            die();
            ?>
        @else
            <?php
            $sysobjid = 1960;
            $sysobjcode = 'idcard_staffs';
            $thisTitle = "Держатель карты";

            $retRoute = route('idcards.edit',$rec->cardid);
            //dd($retRoute);

            //для блокировки текстовых полей пользователям, не имеющим право на редактирование
            $inputReadOnly = "readonly";
            if ($usrrights['save']) $inputReadOnly = "";

            ?>

            <div class="container">

                @include('layouts.edit_msgs')

                <div class="row ">
                    <div class="col-md-6">
                        <div class="card mt-3">
                            <div class="card-header">
                                {{$thisTitle}}
                                <a class="btn btn-close btn-light btn-sm"
                                   style="float:right;"
                                   href="{{ $retRoute }}"
                                   title="Вернуться в список">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>

                            </div>
                            <div class="card-body">
                                @include('layouts.err_msgs')

                                <form name="forEdit" id="forEdit" method="post"
                                      action="{{ route('idcard_staffs.update', $rec->id) }}">
                                    @method('PUT')
                                    @csrf
                                    <input type="hidden" name="cardid"  value="{{old('cardid',$rec->cardid)}}">
                                    <div class="form-group offset-md-0 col-md-12">
                                        <label for="name" class="required">Держатель карты:</label>
                                        @if ($usrrights['save'] and $usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="staff_name"
                                                       class="staff_name0 stfname form-control font-weight-bold"
                                                       value="{{old('staff_name',$rec->orgstaff->name)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="staffid" class="ac_id" id="staffid"
                                                       value="{{old('staffid',$rec->staffid)}}">
                                                <a class="btn btn-light id_lnk" id="driverid_lnk"
                                                   data-id="staffid" data-obj="orgstaff" target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->orgstaff->name}}</div>
                                        @endif
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-2 col-md-8">
                                            <label for="name" class="required">Период применения:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group">
                                                    <input type="date" class="form-control text-center font-weight-bold"
                                                           name="begdate" id="begdate" required
                                                           min="{{$rec->wrkdate_min}}"
                                                           max="{{today()->format('Y-m-d')}}"
                                                           value="{{old('begdate',$rec->begdate)}}"/>
                                                    &nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;
                                                    <input type="date" class="form-control text-center font-weight-bold"
                                                           name="enddate" id="enddate" readonly
                                                           min="{{$rec->wrkdate_max}}"
                                                           max="{{today()->format('Y-m-d')}}"
                                                           value="{{old('enddate',$rec->enddate)}}"/>
                                                </div>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">
                                                    {{date_create($rec->begdate)->format('d.m.Y')}} -
                                                    {{date_create($rec->enddate)->format('d.m.Y')}}
                                                    {{ Form::hidden('begdate', $rec->begdate,['id'=>'begdate']) }}
                                                    {{ Form::hidden('enddate', $rec->enddate,['id'=>'enddate']) }}
                                                </div>
                                            @endif
                                        </div>
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
                                                formaction="{{ route('idcard_staffs.delete', $rec->id)}}"
                                                formmethod="post"
                                                title="Удалить"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                </form>
                            </div>

                            @include('layouts._who_when')
                        </div>
                    </div>

                    @if($rec->id <> -1)
                        <div class="col-md-5">
                        </div>
                    @endif

                </div>
            </div>
            <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
            <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
            <script src="{{ asset('js/idcard_holder_edit.js') }}" defer></script>

        @endif
    @endguest
@endsection
