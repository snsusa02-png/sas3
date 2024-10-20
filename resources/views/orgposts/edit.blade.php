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
        $sysobjid = 119;
        $sysobjcode = 'orgposts';
        $ThisTitle = "Должность";
        $retRoute = route('orgs.edit', $rec->orgid);

        $retRoute = ($rec->retURL)
            ? ($rec->retURL . '#orgposts')
            : route('orgs.edit', $rec->orgid) . '?#orgposts';

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
                            {{ Form::hidden('returl', $rec->retURL) }}

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
                                    @endif
                                </ul>


                                <!-- Tab panes -->
                                <div class="tab-content">

                                    @include('layouts.err_msgs')

                                    <div id="home" class="container tab-pane active"><br>

                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                <label for="name">Организация:</label>
                                                <div class="">
                                                    <b>{{$rec->org->name}}</b>
                                                </div>
                                            </div>

                                            <div class="form-group offset-md-0 col-md-6">
                                                <label for="depid" class="required">Подразделение:</label>
                                                {!! Form::select('depid', $rec->orgdeps ,$rec->depid,
                                                    ['class' => 'form-control',
                                                    'placeholder' => '',
                                                    'required' => 'required',
                                                    ]) !!}
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="form-group col-md-12">
                                                <label for="name" class="required">Должность:</label>
                                                <div class="input-group mb-3 ">
                                                    <input type="text" class="form-control" name="name"
                                                           id="name" required maxlength="90"
                                                           value="{{old('name',$rec->name) }}"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row ">
                                            <div class="form-group offset-md-0 col-md-4">
                                                <label for="stdlimunits" class="required">Штатных единиц:</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control text-right"
                                                           name="stdlimunits"
                                                           id="stdlimunits" required min="0" step="0.25"
                                                           value="{{old('stdlimunits',$rec->stdlimunits) }}"
                                                    />
                                                </div>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="tmplimunits" class="required">Временных единиц:</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control text-right"
                                                           name="tmplimunits"
                                                           id="tmplimunits" required min="0" step="0.25"
                                                           value="{{old('tmplimunits',$rec->tmplimunits) }}"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="offset-md-0 col-md-3">
                                                <div class="form-group">
                                                    <label for="active">Действующая:</label>
                                                    {!! Form::checkbox('active', 1, $rec->active==1, ['class="form-control"']) !!}
                                                </div>
                                            </div>

                                            <div class="form-group offset-md-0 col-md-4">
                                                <label for="name">Порядок вывода (1-999):</label>
                                                <input type="text" class="form-control text-right" name="ordr"
                                                       value="{{ old('ordr',$rec->ordr )}}"/>
                                            </div>

                                        </div>

                                    </div>

                                    <div id="menu1" class="container tab-pane fade"><br>

                                        <div class="row">
                                            <div class="offset-md-0 col-md-12">
                                                <div class="form-group">
                                                    <label for="wrkduties">Рабочие обязанности:</label>
                                                    <textarea class="form-control rounded-0" name="wrkduties"
                                                              id="wrkduties" maxlength="360"
                                                              rows="3">{{old('wrkduties',$rec->wrkduties)}}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="offset-md-0 col-md-12">
                                                <div class="form-group">
                                                    <label for="prsndmnds">Требования к кандидату:</label>
                                                    <textarea class="form-control rounded-0" name="prsndmnds"
                                                              id="prsndmnds" maxlength="360"
                                                              rows="3">{{old('prsndmnds',$rec->prsndmnds)}}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="offset-md-0 col-md-12">
                                                <div class="form-group">
                                                    <label for="wrkconds">Условия работы:</label>
                                                    <textarea class="form-control rounded-0" name="wrkconds"
                                                              id="wrkconds" maxlength="360"
                                                              rows="3">{{old('wrkconds',$rec->wrkconds)}}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="menu2" class="container tab-pane fade"><br>
                                        <div class="row mt-3">
                                            <div class="form-group offset-md-0 col-md-4">
                                                <label for="salary" class="">Ставка ЗП, руб:</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control text-right" name="salary"
                                                           id="salary" min="0"
                                                           value="{{old('salary',$rec->salary) }}"
                                                    />
                                                </div>
                                            </div>
                                            <div class="form-group offset-md-0 col-md-8">
                                                <label for="salary" class="">Бонус 1:</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control text-right"
                                                           name="bns1pcnt" id="bns1pcnt"
                                                           min="0" max="100" step="0.01"
                                                           value="{{old('bns1pcnt',$rec->bns1pcnt) }}"
                                                    />
                                                    <div class="input-group-append">
                                                        <span class="input-group-text" id="basic-addon2">%</span>
                                                    </div>
                                                    <input type="number" class="form-control text-right"
                                                           name="bns1sum" id="bns1sum" readonly
                                                           min="0" step="0.01"
                                                           value="{{old('bns1sum',$rec->bns1sum) }}"
                                                    />
                                                    <div class="input-group-append">
                                                        <span class="input-group-text" id="basic-addon2">&#x20bd;</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="form-group offset-md-4 col-md-8">
                                                <label for="bns2" class="">Бонус 2:</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control text-right"
                                                           name="bns2pcnt" id="bns2pcnt"
                                                           min="0" max="100" step="0.01"
                                                           value="{{old('bns2pcnt',$rec->bns2pcnt) }}"
                                                    />
                                                    <div class="input-group-append">
                                                        <span class="input-group-text" id="basic-addon2">%</span>
                                                    </div>
                                                    <input type="number" class="form-control text-right"
                                                           name="bns2sum" id="bns2sum" readonly
                                                           min="0" step="0.01"
                                                           value="{{old('bns2sum',$rec->bns2sum) }}"
                                                    />
                                                    <div class="input-group-append">
                                                        <span class="input-group-text" id="basic-addon2">&#x20bd;</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="form-group offset-md-8 col-md-4">
                                                <label for="salary" class="">Итого ЗП, руб:</label>
                                                <div class="input-group">
                                                    <input type="number"
                                                           class="form-control text-right font-weight-bold"
                                                           name="totsalary"
                                                           id="totsalary" readonly
                                                           value="{{old('totsalary',$rec->totsalary) }}"
                                                    />
                                                    <div class="input-group-append">
                                                        <span class="input-group-text" id="basic-addon2">&#x20bd;</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>


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

                <div class="col-md-5 col-sm-12">

                    @include('orgposts._staff')
                    @include('objfiles.obj_files')

                </div>
            </div>

        </div>
        <script src="{{ asset('js/orgpost_edit.js') }}" defer></script>
    @endif
@endsection
