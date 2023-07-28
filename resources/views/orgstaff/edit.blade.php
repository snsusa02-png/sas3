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
        @if (!isset( $rec))
            <?php
            redirect()->route('orgstaff.index');
            header("Location:" . route('orgstaff.index'));
            die();
            ?>
        @else

            <?php
            $sysobjid = 121;
            $thisSysObjCode = 'orgstaff';
            $orgid = $rec->orgid;

            $retURL = \Request::get('returl') ?? $rec->retURL ?? (route($thisSysObjCode . '.index') . "?page=" . session($thisSysObjCode . '_pageno') . '#' . $rec->id);

            ?>
            <style>
                label {
                    color: gray;
                    margin-bottom: 0px;
                }

                .photo {
                    display: block;
                    max-width: 120px;
                    max-height: 160px;
                    width: auto;
                    height: auto;
                    margin: auto;
                }
            </style>

            <div class="container">

                <div class="row ">
                    <div class="col-md-8">
                        <div class="card mt-3">
                            <div class="card-header">
                                Сотрудник
                                <a class="btn btn-close btn-info btn-sm"
                                   style="float:right;"
                                   href="{{ $retURL }}"
                                   title="Вернуться в список сотрудников">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body">

                                @include('layouts.edit_msgs')

                                <form name="forEdit" id="forEdit" method="post"
                                      action="{{ route('orgstaff.update', $rec->id) }}">
                                    @method('PUT')
                                    @csrf
                                    {!! Form::hidden('id', $rec->id,['id'=>'id']) !!}
                                    {!! Form::hidden('returl', $retURL) !!}

                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="tab" href="#home">Основное</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#menu1">Обязанности</a>
                                        </li>
                                        @if($usrrights['private_acs']??false)
                                            <li class="nav-item">
                                                <a class="nav-link" data-toggle="tab" href="#menu2">ЗП</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-toggle="tab" href="#menu3">ПД</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-toggle="tab" href="#menu4">ИС</a>
                                            </li>
                                        @endif
                                    </ul>


                                    <!-- Tab panes -->
                                    <div class="tab-content">

                                        @include('layouts.err_msgs')

                                        <div id="home" class="container tab-pane active"><br>
                                            {{--                                            <h3>HOME</h3>--}}

                                            <div class="row">

                                                <div class="col-md-3">
                                                    <?php
                                                    //dd($rec->photo);
                                                    $photo = '<img src="/images/signs/user-no-photo.jpg" class="photo">';
                                                    if ($rec->id != -1)
                                                        //$photo = '<a href="' . route('ri_images.load', $rec->id) . '" title="Добавить фото">' . $photo . '</a>';

                                                        if (isset($rec->photo)) {
                                                            $url = Storage::disk('local')->url($rec->photo->systemfilename);

                                                            if (isset($url)) {
                                                                $photo = '<img src=' . $url . ' class="photo">';
                                                                $photo = '<a href=' . $url . ' class="popup-image" title="' . $rec->name . '">' . $photo . '</a>';
                                                            }
                                                        }
                                                    ?>
                                                    {!! $photo!!}
                                                </div>
                                                <div class="col-md-9">
                                                    <div class="container">
                                                        <div class="row">
                                                            <div class="col-md-5">
                                                                <div
                                                                    class="form-group {{ ($errors->has('lname')) ? ' has-error' : '' }}">
                                                                    <label for="lname" class="required">Фамилия:</label>
                                                                    <input type="text" required
                                                                           class="form-control font-weight-bold"
                                                                           name="lname"
                                                                           value="{{ old('lname',$rec->lname) }}"/>
                                                                    @if ($errors->has('lname'))
                                                                        <span class="help-block text-danger">
                                                                        <strong>{{ $errors->first('lname') }}</strong>
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="form-group">
                                                                    <label for="fname" class="required">Имя:</label>
                                                                    <input type="text"
                                                                           class="form-control font-weight-bold"
                                                                           name="fname"
                                                                           required
                                                                           value="{{ old('fname',$rec->fname) }}"/>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label for="mname">Отчество:</label>
                                                                    <input type="text"
                                                                           class="form-control font-weight-bold"
                                                                           name="mname"
                                                                           value="{{ old('mname',$rec->mname) }}"/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="form-group col-md-6">
                                                                <label for="inn">ИНН:</label>
                                                                <input type="text" class="form-control" name="inn"
                                                                       maxlength="12"
                                                                       value="{{ old('inn',$rec->inn) }}"/>
                                                            </div>
                                                            <div class="form-group col-md-6">
                                                                <label for="snils">СНИЛС:</label>
                                                                <input type="text" class="form-control" name="snils"
                                                                       maxlength="14"
                                                                       value="{{ old('snils',$rec->snils) }}"/>
                                                            </div>
                                                        </div>

                                                    </div>

                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="form-group col-md-6">
                                                    <label for="orgid">Организация:</label>
                                                    <div>
                                                        @if (isset($orgid))
                                                            <input type="hidden" name="orgid" value="{{$orgid}}">
                                                            <b>{{$rec->org->name}}</b>
                                                        @else
                                                            {!! Form::select('orgid', $rec->orgs, $rec->$orgid,
                                                         [
                                                         'id' => 'orgid',
                                                         'class' => 'form-control',
                                                         'placeholder' => '',
                                                         ]) !!}
                                                        @endif
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
                                                               id="postname" maxlength="160"
                                                               value="{{ old('postname',$rec->postname) }}"/>

                                                        <a id="postid_lnk" class="btn btn-light">
                                                            <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                        </a>

                                                    </div>
                                                </div>

                                                <div class="form-group offset-md-0 col-md-2" id="stdpostunit_div">
                                                    <label for="stdpostunit">Ставка:</label>
                                                    <input type="number" class="form-control text-right"
                                                           name="stdpostunit" id="stdpostunit"
                                                           min="0" step="0.25" max="1.75"
                                                           value="{{ old('stdpostunit',$rec->stdpostunit) }}"/>
                                                </div>

                                            </div>


                                            <div class="row">
                                                <div class="form-group offset-md-0 col-md-5">
                                                    <label for="stdpostunit">Дата вступления в должность:</label>
                                                    <input type="date" class="form-control"
                                                           name="postbegdate" id="postbegdate"
                                                           value="{{ old('postbegdate',$rec->postbegdate) }}"/>
                                                </div>

                                                <div class="form-group offset-md-0 col-md-5">
                                                    <label for="post">Руководитель сотрудника:</label>
                                                    <input type="text" class="form-control" name="bossname"
                                                           value="{{ old('bossname',$rec->bossname) }}"/>
                                                </div>
                                            </div>

                                            <div class="row" id="outofoffice_div">
                                                <?php
                                                $ooo_statuses = [0 => 'на рабочем месте', 1 => 'отсутствует', 2 => 'не известно'];
                                                $ooo_status = 'на рабочем месте';
                                                if ($rec->outofoffice == 1) {
                                                    $ooo_status = "отсутствует";
                                                    if (isset($rec->ooo_reason))
                                                        $ooo_status .= " по причине: '{$rec->ooo_reason}'";
                                                    if (isset($rec->ooo_tilldate))
                                                        $ooo_status .= ". До " . date_create($rec->ooo_tilldate)->format('d.m.Y') . " включительно";
                                                }
                                                ?>
                                                <div class="form-group offset-md-0 col-md-9">
                                                    <label for="post">Нахождение на рабочем месте:</label>
                                                    <input type="text" class="form-control" name="outofoffice" readonly
                                                           value="{{ old('outofoffice',$ooo_status) }}"/>
                                                </div>
                                            </div>


                                        </div>

                                        <div id="menu1" class="container tab-pane fade"><br>
                                            {{--                                            <h3>Обязанности</h3>--}}

                                            <div class="row">
                                                <div class="offset-md-0 col-md-12">
                                                    <div class="form-group">
                                                        <label for="email">Рабочие обязанности:</label>
                                                        <textarea class="form-control rounded-0" name="jobduties"
                                                                  id="jobduties"
                                                                  rows="3">{{$rec->jobduties}}</textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            @if(isset($rec->flags))
                                                <?php
                                                $show = ($rec->id == -1) ? 'show' : '';

                                                ?>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label for="boss_fullname">Особенности сотрудника:</label>
                                                            <button data-toggle="collapse" data-target="#orgflaglist"
                                                                    type="button"
                                                                    class="btn btn-light btn-sm"><i
                                                                    class="fa fa-eye-slash"
                                                                    aria-hidden="true"></i>
                                                            </button>

                                                            <ul class="collapse {{$show}}" id="orgflaglist">
                                                                <?php
                                                                $lstFlags = '';
                                                                ?>
                                                                @foreach($rec->flags as $flag)
                                                                    <li><label><input type="checkbox" class=""
                                                                                      name="flagid[{{$flag->id}}]"
                                                                                {{(isset($flag->objflagid))?'checked':''}}/>&nbsp;{{$flag->name}}
                                                                        </label></li>
                                                                    @php($lstFlags.=','.$flag->id)
                                                                @endforeach

                                                                <hr>
                                                                <li><input type="checkbox" name="is_boss"
                                                                           value="1" {{($rec->is_boss==1)?'checked':''}}>
                                                                    Является
                                                                    руководителем
                                                                    предприятия</label>
                                                                </li>
                                                                <li><label class="checkbox-inline">
                                                                        <input type="checkbox" name="is_ca"
                                                                               value="1" {{($rec->is_ca==1)?'checked':''}}>
                                                                        Является
                                                                        главным бухгалтером предприятия</label>
                                                                </li>
                                                                <input type="hidden" name="lstflags"
                                                                       value="{{$lstFlags}}">
                                                            </ul>
                                                        </div>
                                                    </div>


                                                </div>
                                            @endif

                                            @if(1==0)
                                                <div class="row">
                                                    <div class="offset-md-0 col-md-6">
                                                        <div class="form-group">
                                                            <label for="email">Создает документы (тип):</label>
                                                            <textarea class="form-control rounded-0" name="gendoctypes"
                                                                      rows="4">{{$rec->gendoctypes}}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="offset-md-0 col-md-6">
                                                        <div class="form-group">
                                                            <label for="email">Согласует документы:</label>
                                                            <textarea class="form-control rounded-0"
                                                                      name="cnfrmdoctypes"
                                                                      rows="4">{{$rec->cnfrmdoctypes}}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="offset-md-0 col-md-6">
                                                        <div class="form-group">
                                                            <label for="email">Утверждает документы:</label>
                                                            <textarea class="form-control rounded-0" name="aprvdoctypes"
                                                                      rows="4">{{$rec->aprvdoctypes}}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="offset-md-0 col-md-6">
                                                        <div class="form-group">
                                                            <label>-</label>
                                                            <label class="checkbox-inline">
                                                                <input type="checkbox" name="is_boss"
                                                                       value="1" {{($rec->is_boss==1)?'checked':''}}>
                                                                Является
                                                                руководителем
                                                                предприятия</label>
                                                            <br>
                                                            <label class="checkbox-inline">
                                                                <input type="checkbox" name="is_ca"
                                                                       value="1" {{($rec->is_ca==1)?'checked':''}}>
                                                                Является
                                                                главным бухгалтером предприятия</label>

                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <div id="menu2" class="container tab-pane fade"><br>
                                            {{--                                            <h4>Разное</h4>--}}
                                            @if($usrrights['private_acs']??false)
                                                <div class="row">
                                                    <div class="form-group col-md-4">
                                                        <label for="active" style="color: rgb(73, 80, 87);">действующий
                                                            сотрудник:</label>
                                                        {!! Form::checkbox('active', 1, $rec->active==1,
['class'=>'form-control']) !!}
                                                    </div>

                                                    <div class="offset-md-0 col-md-4">
                                                        <div class="form-group">
                                                            <label for="begdate">Дата приема:</label>
                                                            <input type="date" class="form-control" name="begdate"
                                                                   value="{{ old('begdate',$rec->begdate) }}"/>
                                                        </div>
                                                    </div>
                                                    <div class="offset-md-0 col-md-4">
                                                        <div class="form-group">
                                                            <label for="enddate">Дата увольнения:</label>
                                                            <input type="date" class="form-control" name="enddate"
                                                                   value="{{ old('enddate',$rec->enddate) }}"/>
                                                        </div>
                                                    </div>
                                                </div>

                                                <h4>ЗП</h4>
{{--                                                <div class="row">--}}
{{--                                                    <div class="col-md-8">--}}
{{--                                                        <label for="fot_acnttypeid">Тип расчета ЗП:</label>--}}
{{--                                                        <input type="hidden" name="stf_payrolltype_id"--}}
{{--                                                               value="{{$rec->stf_payrolltype_idd}}">--}}
{{--                                                        {!! Form::select('payrolltypeid', $rec->payrolltypes, $rec->payrolltypeid,--}}
{{--                                                             [--}}
{{--                                                             'id' => 'payrolltypeid',--}}
{{--                                                             'class' => 'form-control small',--}}
{{--                                                             'placeholder' => '-выбор-',--}}
{{--                                                             ]) !!}--}}
{{--                                                    </div>--}}
{{--                                                    <div class="col-md-4">--}}
{{--                                                        <div class="form-group">--}}
{{--                                                            <label for="hour_salary">Начало действия:</label>--}}
{{--                                                            <input type="date" class="form-control" name="enddate"--}}
{{--                                                                   value="{{ old('enddate',$rec->enddate) }}"/>--}}
{{--                                                        </div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}

                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <label for="fot_acnttypeid">Источник ФОТ:</label>
                                                        {!! Form::select('fot_acnttypeid', $rec->fot_acnttypes, $rec->fot_acnttypeid,
             [
             'id' => 'fot_acnttypeid',
             'class' => 'form-control small',
             'placeholder' => '-выбор-',
             ]) !!}
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="hour_salary">Ставка ЗП в час:</label>
                                                            <input type="number" class="form-control text-right"
                                                                   name="hour_salary"
                                                                   min="0" step="0.01"
                                                                   value="{{ old('hour_salary',$rec->hour_salary) }}"/>
                                                        </div>
                                                    </div>
                                                    <div class="offset-md-0 col-md-4">
                                                        <div class="form-group">
                                                            <label for="hour_salary">Ставка ЗП в день:</label>
                                                            <input type="number" class="form-control text-right"
                                                                   name="day_salary"
                                                                   min="0" step="0.01"
                                                                   value="{{ old('day_salary',$rec->day_salary) }}"/>
                                                        </div>
                                                    </div>

                                                </div>
                                            @endif
                                        </div>

                                        <div id="menu3" class="container tab-pane fade"><br>
                                            <h4>Персональные данные</h4>
                                            @if($usrrights['private_acs']??false)

                                                <div class="row">
                                                    <div class="offset-md-0 col-md-4">
                                                        <div class="form-group">
                                                            <label for="email">Дата рождения:</label>
                                                            <input type="date" class="form-control" name="birthdate"
                                                                   value="{{ $rec->birthdate }}"/>
                                                        </div>
                                                    </div>
                                                    <div class="offset-md-0 col-md-6">
                                                        <div class="form-group">
                                                            <label for="email">Место рождения:</label>
                                                            <input type="text" class="form-control" name="birthplace"
                                                                   maxlength="50"
                                                                   value="{{ old('birthplace',$rec->birthplace) }}"/>
                                                        </div>
                                                    </div>
                                                    <div class="offset-md-0 col-md-2">
                                                        <div class="form-group">
                                                            <label for="sex">Пол:</label>
                                                            {!! Form::select('sex', $rec->sexes, $rec->sex,
                                                             [
                                                             'id' => 'sex',
                                                             'class' => 'form-control',
                                                             'placeholder' => '-выбор-',
                                                             ]) !!}
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="row">
                                                    <div class="offset-md-0 col-md-4">
                                                        <div class="form-group">
                                                            <label for="marriage">Состояние в браке:</label>
                                                            {!! Form::select('marriage', $rec->marriage_statuses, $rec->marriage,
                                                             [
                                                             'id' => 'marriage',
                                                             'class' => 'form-control',
                                                             'placeholder' => '-выбор-',
                                                             ]) !!}
                                                        </div>
                                                    </div>

                                                    <div class="offset-md-2 col-md-6">
                                                        <div class="form-group">
                                                            <label for="email">Образование:</label>
                                                            {!! Form::select('education_lvl', $rec->education_lvls, $rec->education_lvl,
                                                             [
                                                             'id' => 'education_lvl',
                                                             'class' => 'form-control',
                                                             'placeholder' => '-выбор-',
                                                             ]) !!}
                                                        </div>
                                                    </div>

                                                </div>

                                                {{--                                                <div class="row">--}}
                                                {{--                                                    <div class="offset-md-0 col-md-12">--}}
                                                {{--                                                        <div class="form-group">--}}
                                                {{--                                                            <label for="reg_address">Адрес регистрации:</label>--}}
                                                {{--                                                            <textarea class="form-control rounded-0" name="reg_address"--}}
                                                {{--                                                                      rows="2">{{$rec->reg_address}}</textarea>--}}
                                                {{--                                                        </div>--}}
                                                {{--                                                    </div>--}}
                                                {{--                                                </div>--}}


                                            @endif
                                        </div>

                                        <div id="menu4" class="container tab-pane fade">
                                            <div class="row">
                                                @if(isset($rec->users))
                                                    <div class="form-group col-md-8">
                                                        <label for="userid">Связан с пользователем ИС:</label>
                                                        {!! Form::select('userid', $rec->users, $rec->userid,
                                                             [
                                                             'id' => 'userid',
                                                             'class' => 'form-control',
                                                             'placeholder' => '-выбор-',
                                                             ]) !!}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="row">
                                                <div class="offset-md-0 col-md-6">
                                                    <div class="form-group">
                                                        <label for="rqrd_software">Требующееся ПО:</label>
                                                        <textarea class="form-control rounded-0" name="rqrd_software"
                                                                  maxlength="300"
                                                                  rows="4">{{$rec->rqrd_software}}</textarea>
                                                    </div>
                                                </div>
                                                <div class="offset-md-0 col-md-6">
                                                    <div class="form-group">
                                                        <label for="have_software">Установленное ПО:</label>
                                                        <textarea class="form-control rounded-0" name="have_software"
                                                                  maxlength="300"
                                                                  rows="4">{{$rec->have_software}}</textarea>
                                                    </div>
                                                </div>
                                            </div>


                                        </div>
                                    </div>


                                    <hr size="1">
                                    @if ($usrrights['save']??false)
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                            Сохранить
                                        </button>
                                    @endif
                                    &nbsp;
                                    <a class="btn btn-close btn-info"
                                       href="{{ $retURL }}">
                                        <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                        Закрыть
                                    </a>
                                    &nbsp;
                                    @if ($rec->id != -1 and $usrrights['delete']??false)
                                        <button type="submit"
                                                class="btn btn-danger"
                                                style="margin-left:24px"
                                                formaction="{{ route('orgstaff.del', $rec->id)}}"
                                                formmethod="post"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                                title="Удалить"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                    @include('layouts._who_when')
                                </form>

                            </div>
                        </div>
                    </div>

                    @if (1==1 and $rec->id != -1)
                        <div class="col-md-4">


                            {{--                            @include('stforders._orders')--}}
                            {{--                            @include('staff_posts._posts')--}}
                            @include('objfiles.obj_files')
                            @include('obj_contacts._contacts')
                            @include('obj_addresses._list')
                            @include('stf_payrolltypes._list')
                            @include('stf_charges._list')
                            @include('stf_chrg_calcs._list')
                            @include('stf_salaries._list')
{{--                            @include('objflags._flags')--}}

                            @if (count($rec->userrights)>0)
                                <div class="card ">
                                    <div class="card-header">
                                        Полномочия
                                    </div>
                                    <table class="table-striped small" style="width: 100%;">
                                        <thead>
                                        <tr>
                                            <td>#</td>
                                            <td>Право</td>
                                            <td>период использования</td>
                                            <td>Основание</td>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($rec->userrights as $itm)
                                            <tr>
                                                <td style="text-align: right;" class="small">{{$loop->iteration}}</td>
                                                <td>&nbsp;{{$itm->sysfuncid}}. {{$itm->rightname}} </td>
                                                <td>&nbsp;{{$itm->begdt.' .. '.$itm->enddt}}</td>
                                                <td>&nbsp;{{$itm->reason}}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                    @endif

                </div>
            </div>
            <script src="{{ asset('js/orgstaff_edit.js') }}" defer></script>

        @endif
    @endguest
@endsection
