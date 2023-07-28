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
            $thisSysObjId = 1223;
            $sysobjid = $thisSysObjId;
            $sysobjcode = 'srs_hr_items';
            $thisTitle = "По-часовая ставка для расчета заработной платы";

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
                                    {{ Form::hidden('srs_id', $rec->srs_id) }}
                                    {{ Form::hidden('id', $rec->id,['id'=>'id']) }}

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="name" class="">Для:</label>
                                            <input type="text" class="form-control"
                                                   readonly
                                                   value="{{ $rec->_obj_info }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-2 col-md-8">
                                            <label for="chargetype_name" class="required">Вид работ:</label>
                                            <div class="input-group">

                                                {!! Form::select('wrktypeid', $rec->wrktypes??[], $rec->wrktypeid,
                                                 [
                                                 'id' => 'wrktypeid',
                                                 'class' => 'form-control required',
                                                 'placeholder' => '',
                                                 'required' => 'required',
                                                 ]) !!}
                                                <a class="btn btn-light" id="wrktypeid_lnk"
                                                   target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-2 col-md-4">
                                            <label for="name" class="required">Стаж работы (от), лет:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group">
                                                    <input type="number"
                                                           class="form-control text-center font-weight-bold"
                                                           name="min_wrkexp" id="min_wrkexp" required
                                                           min="0"
                                                           max="99.9"
                                                           step="0.1"
                                                           value="{{old('min_wrkexp',$rec->min_wrkexp)}}"/>
                                                </div>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">
                                                    {{$rec->min_wrkexp}}
                                                    {{ Form::hidden('min_wrkexp', $rec->min_wrkexp,['id'=>'min_wrkexp']) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="name" class="required">Стаж работы (до), лет:</label>
                                            <div
                                                class="font-weight-bold text-center">
                                                {{$rec->max_wrkexp}}
                                                {{ Form::hidden('max_wrkexp', $rec->max_wrkexp,['id'=>'max_wrkexp']) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-2 col-md-4">
                                            <label for="name" class="required">Ставка "День", руб/час:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group">
                                                    <input type="number"
                                                           class="form-control text-center font-weight-bold"
                                                           name="hr_day_rate" id="hr_day_rate" required
                                                           min="0"
                                                           step="0.01"
                                                           value="{{old('hr_day_rate',$rec->hr_day_rate)}}"/>
                                                </div>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">
                                                    {{$rec->hr_day_rate}}
                                                    {{ Form::hidden('hr_day_rate', $rec->hr_day_rate,['id'=>'hr_day_rate']) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="name" class="required">Ставка "Ночь", руб/час:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group">
                                                    <input type="number"
                                                           class="form-control text-center font-weight-bold"
                                                           name="hr_night_rate" id="hr_night_rate" required
                                                           min="0"
                                                           step="0.01"
                                                           value="{{old('hr_night_rate',$rec->hr_night_rate)}}"/>
                                                </div>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">
                                                    {{$rec->hr_night_rate}}
                                                    {{ Form::hidden('hr_night_rate', $rec->hr_night_rate,['id'=>'hr_night_rate']) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    {{--                                    <div class="row">--}}
                                    {{--                                        <div class="form-group offset-md-0 col-md-12">--}}
                                    {{--                                            <label for="name" class="">Примечание:</label>--}}
                                    {{--                                            <input type="text" class="form-control" name="notes" maxlength="160"--}}
                                    {{--                                                   value="{{ old('notes',$rec->notes) }}"/>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}

                                    <hr>
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
                                                formaction="{{ route($sysobjcode.'.delete', $rec->id)}}"
                                                formmethod="post"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                                title="Удалить запись"
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
            <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"
                    integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
            <script src="{{ asset('js/stf_salary_edit.js') }}" defer></script>

        @endif
    @endguest
@endsection
