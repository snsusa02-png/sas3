@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('contracts.index');
        header("Location:" . route('contracts.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>
        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

        <script src="{{ asset('js/callListOrgs.js') }}" defer></script>

        <?php
        $sysobjid = 151;
        $thisSysObjId = 151;
        $sysobjcode = 'contracts';
        $objcode = $sysobjcode;
        $thisTitle = $rec->name ?? "Договор";

        $route_index = route($sysobjcode . '.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;
        ?>
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

        </style>
        <div class="container">

            @includeIf('layouts.edit_msgs')

            <div class="row ">
                <div class="col-md-6">
                    <div class="card p-2 my-2 my-md-3">
                        <div class="card-header">
							<span class="font-weight-bold"
                                  style="max-width: 60%; overflow:hidden;"> {{$thisTitle}}</span>

                            <span class="float-right">
								@if($rec->id <> -1)
                                    <span class="mr-2">
										<label for="regnum" title="Регистрационный номер">Рег. №:</label>
										@if(!isset($rec->regnum))
                                            <label class="checkbox-inline text-danger">
												<input type="checkbox" id="setregnum"
                                                       value="1" title="Присвоить регистрационный номер"> Присвоить
											</label>
                                        @else
                                            <?php
                                            $tclass = ($rec->categoryid == 1) ? 'badge-success'
                                                : (($rec->categoryid == 2) ? 'badge-danger' : 'badge-warning');
                                            ?>
                                            <span class="badge badge-pill {{$tclass}}"
                                                  style="font-size: 16px;">{{$rec->regnum}}</span>
                                        @endif
									</span>
                                @endif

							<a class="btn btn-close btn-light btn-sm ml-1"
                               href="{{ $route_index }}"
                               title="Вернуться в список">
								<i class="fa fa-times" aria-hidden="true"></i>
							</a>
							</span>
                        </div>
                        <div class="card-body" style="background-color: #f4f4f4">

                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($sysobjcode . '.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('id', $rec->id,['id'=>'id']) }}
                                {{ Form::hidden('set_regnum', 0,['id'=>'set_regnum']) }}
                                {{ Form::hidden('ttt', 1) }}
                                {{ Form::hidden('tt2', 1) }}


                                <div class="row">
                                    <div class="form-group col-md-5">
                                        <label for="categoryid" class="required">Категория:</label>
                                        {!! Form::select('categoryid', $rec->categories, $rec->categoryid,
                                         [
                                         'id' => 'categoryid',
                                         'class' => 'form-control',
                                         'placeholder' => '-выбор-',
                                         ]) !!}
                                    </div>
                                    <div class="form-group col-md-7">
                                        <label for="contracttypeid" class="required">Тип:</label>
                                        {!! Form::select('contracttypeid', $rec->contracttypes, $rec->contracttypeid,
                                         [
                                         'id' => 'contracttypeid',
                                         'class' => 'form-control',
                                         'placeholder' => '-выбор-',
                                         ]) !!}
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12 col-sm-6 ">
                                        <label for="docnum">Название документа:</label>
                                        <input type="text" class="form-control text-left" name="name"
                                               value="{{old('name',$rec->name)}}"
                                               maxlength="210"/>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-5 col-sm-6 ">
                                        <label for="docnum" class="required">№ договора:</label>
                                        <input type="text" class="form-control text-center font-weight-bold"
                                               name="docnum"
                                               value="{{old('docnum',$rec->docnum)}}"/>
                                    </div>
                                    <div class="form-group col-md-5 col-sm-6">
                                        <label for="docnum" class="required">Дата договора:</label>
                                        <input type="date" class="form-control" name="docdate"
                                               value="{{old('docdate',$rec->docdate)}}"/>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="descript" class="required">Предмет договора:</label>
                                        <textarea class="form-control rounded-0" name="descript" id="descript"
                                                  rows="2">{{ old('descript',$rec->descript) }}</textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="outline" class="">
                                            <button type="button" class="btn btn-link"
                                                    style="background-color: aliceblue"
                                                    data-toggle="collapse"
                                                    data-target="#collapse_ocr">
                                                OCR >>>
                                            </button>
                                        </label>
                                        <div id="collapse_ocr" class="collapse">
                                        <textarea class="form-control rounded-0" name="outline" id="outline"
                                                  rows="2">{{ old('outline',$rec->outline) }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-8">
                                        <label for="ownorgid" class="required">Со стороны холдинга:
                                            @if(isset($rec->ownorgid))
                                                <a href="{{route("orgs.edit",$rec->ownorgid)}}"
                                                   target="_blank"><i class="fa fa-external-link-square text-info"
                                                                      aria-hidden="true"></i></a>
                                            @endif
                                        </label>
                                        {!! Form::select('ownorgid', $rec->ownorgs, $rec->ownorgid,
                                         [
                                         'id' => 'ownorgid',
                                         'class' => 'form-control',
                                         'placeholder' => '-выбор-',
                                         ]) !!}
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="ownorgroletypeid">Роль: </label>
                                        {!! Form::select('ownorgroletypeid', $rec->roletypes??[], old('ownorgroletypeid',$rec->ownorgroletypeid),
                                         [
                                         'id' => 'ownorgroletypeid',
                                         'class' => 'form-control small',
                                         'placeholder' => '-выбор-',
                                         ]) !!}
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="form-group col-md-8">
                                        <label for="orgname" class="required">Контрагент:
                                            @if(isset($rec->orgid))
                                                <a href="{{route("orgs.edit",$rec->orgid)}}"
                                                   target="_blank"><i class="fa fa-external-link-square text-info"
                                                                      aria-hidden="true"></i></a>
                                            @endif
                                            @if(1==1)
                                                <a href="{{route("orgs.create",0)}}"
                                                   target="_blank"><i class="fa fa-plus-square-o badge-warning"
                                                                      aria-hidden="true"></i></a>
                                            @endif
                                        </label>
                                        <div class="input-group mb-3 ">
                                            {{ Form::hidden('orgid', old('orgid',$rec->orgid),['id'=>'orgid']) }}
                                            @if ($usrrights['save']??false)
                                                <input type="text" class="form-control" name="orgname"
                                                       id="orgname"
                                                       value="{{old('orgname',$rec->org->name)}}"
                                                       style="height: 34px;"
                                                />
                                                <input type="text" class="form-control text-center small"
                                                       style="display: none; border: #d7f3e3;" id="ac_orgid"
                                                       readonly>
                                                <div class="input-group-append">
                                                    <a onclick="callListOrgs($('#orgid').val())" title="Поиск"
                                                       class="btn btn-sm btn-primary form-control">
                                                        <i class="fa fa-search" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <input type="text" name="orgname"
                                                       class="form-control" readonly
                                                       value="{{$rec->org->name}} (ИНН:{{$rec->org->inn}}, КПП:{{$rec->org->kpp}})"
                                                />
                                            @endif
                                        </div>

                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="ownorgroletypeid">Роль: </label>
                                        {!! Form::select('orgroletypeid', $rec->roletypes??[], old('orgroletypeid',$rec->orgroletypeid),
                                         [
                                         'id' => 'orgroletypeid',
                                         'class' => 'form-control small',
                                         'placeholder' => '-выбор-',
                                         ]) !!}
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-5">
                                        <label for="plnqty">Сумма договора, руб:</label>
                                        <input type="number" class="form-control text-right font-weight-bold"
                                               name="docsum"
                                               min="0" step="0.01"
                                               value="{{old('docsum',$rec->docsum)}}"/>
                                    </div>
                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="plnqty">Аванс, %:</label>
                                        <input type="number" class="form-control text-right font-weight-bold"
                                               name="advance_pcnt"
                                               min="0" step="1" max="100"
                                               value="{{old('advance_pcnt',$rec->advance_pcnt)}}"/>
                                    </div>
                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="plnqty">Аванс, руб:</label>
                                        <input type="number" class="form-control text-right font-weight-bold"
                                               name="advance_sum"
                                               min="0" step="0.01"
                                               value="{{old('advance_sum',$rec->advance_sum)}}"/>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-5 col-sm-6">
                                        <label for="begdate">Начало действия:</label>
                                        <input type="date" class="form-control" name="begdate" id="begdate"
                                               value="{{old('begdate',$rec->begdate)}}"/>
                                    </div>
                                    <div class="form-group col-md-5 col-sm-6">
                                        <label for="begdate">Окончание действия:</label>
                                        <input type="date" class="form-control" name="enddate"
                                               value="{{old('enddate',$rec->enddate)}}"/>
                                    </div>
                                    @if(1==0)
                                        <div class="form-group col-md-2">
                                            <label for="active" style="color: rgb(73, 80, 87);">Активный:</label>
                                            {!! Form::checkbox('active', 1, $rec->active==1,
                                             [
                                             'class' => 'form-control',
                                             ]) !!}
                                        </div>
                                    @endif
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-5">
                                        <label for="statusid" class="required">Статус:</label>
                                        {!! Form::select('statusid', $rec->statuses, $rec->statusid,
                                         [
                                         'class' => 'form-control',
                                         'placeholder' => '-выбор-',
                                         ]) !!}
                                    </div>
                                    <div class="form-group col-md-7">
                                        <label for="begdate">Пояснение к статусу:</label>
                                        <input type="text" class="form-control" name="status_notes"
                                               value="{{old('status_notes',$rec->status_notes)}}"
                                               maxlength="160"/>
                                    </div>

                                </div>

                                <div class="form-group">
                                    <label for="descript">Примечание:</label>
                                    <textarea class="form-control rounded-0" name="notes" id="notes"
                                              rows="2">{{ old('notes',$rec->notes) }}</textarea>
                                </div>

                                @if(1==0)
                                    <div class="form-group">
                                        <label for="descript">Категория информации (для доступа):</label>
                                        @if($usrrights['acs.edit'] and $usrrights['save']??false)
                                            {!! Form::select('acsid', $rec->acs, $rec->acsid,
                                                                                     [
                                                                                     'class' => 'form-control',
                                                                                     'placeholder' => '-выбор-',
                                                                                     ]) !!}
                                        @else
                                            <div class="border p-2">{{$rec->ac->name}}&nbsp;</div>
                                        @endif
                                    </div>
                                @else
                                @endif

                                @if($usrrights['updregnum']??false)
                                    <div class="row">
                                        <div class="form-group col-md-9">
                                            <label for="contracttypeid" class="required">Источник № регистрации:</label>
                                            {!! Form::select('regnum_srcid', $rec->regnum_srcs??[], old('regnum_srcid',$rec->regnum_srcid),
                                             [
                                             'id' => 'regnum_srcid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             ]) !!}
                                        </div>
                                        <div class="form-group offset-md-0 col-md-3 col-sm-6 ">
                                            <label for="regnum" title="Регистрационный номер">Рег. №:</label>
                                            <input type="number" class="form-control text-center"
                                                   name="regnum_num" id="regnum_num" step="1"
                                                   value="{{old('regnum_num',$rec->regnum_num)}}"/>
                                        </div>
                                    </div>
                                @endif

                                @if(1==0 and $rec->id<>-1)
                                    <div class="row">

                                        <div class="form-group col-md-5 col-sm-6 ">
                                            <label for="regnum" title="Регистрационный номер">Регистр. №:</label>
                                            <div class="input-group mb-3 ">
                                                @if(!isset($rec->regnum))

                                                    <label class="checkbox-inline text-danger">
                                                        <input type="checkbox" name="set_regnum"
                                                               value="1" title="Присвоить регистрационный номер">
                                                        Присвоить
                                                    </label>
                                                @else
                                                    <input type="text" class="form-control text-center"
                                                           name="regnum" id="regnum" readonly
                                                           value="{{old('regnum',$rec->regnum)}}"/>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif


                                <div style="background-color: #ddd7ef" class="p-1">
                                    <div class="form-group">
                                        <label for="descript">Тэги:</label>
                                        <textarea class="form-control rounded-0" name="tags" id="tags"
                                                  rows="2">{{ old('tags_lst',$rec->tags_lst) }}</textarea>
                                    </div>

                                    {{--                                    <div class="row">--}}
                                    {{--                                        <div class="form-group col-md-12  rt1_hide rt2_show rt3_hide">--}}
                                    {{--                                            <label for="address">Объект:</label>--}}
                                    {{--                                            @if ($usrrights['save'])--}}
                                    {{--                                                {!! Form::select('buildobjid', $rec->buildobjs??[], $rec->buildobjid,--}}
                                    {{--                                                 [--}}
                                    {{--                                                 'id' => 'buildobjid',--}}
                                    {{--                                                 'class' => 'form-control',--}}
                                    {{--                                                 'placeholder' => '-выбор-',--}}
                                    {{--                                                 ]) !!}--}}
                                    {{--                                            @else--}}
                                    {{--                                                <div class="font-weight-bold">{{$rec->buildobjname}}</div>--}}
                                    {{--                                            @endif--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                </div>

                                <div class="row">
                                    {{--                                    <div class="form-group col-md-7">--}}
                                    {{--                                        <label for="address">Обратный документ:</label>--}}
                                    {{--                                        {!! Form::select('opposite_docid', $rec->opposite_docs, $rec->opposite_docid,--}}
                                    {{--                                         [--}}
                                    {{--                                         'class' => 'form-control',--}}
                                    {{--                                         'placeholder' => '-выбор-',--}}
                                    {{--                                         ]) !!}--}}
                                    {{--                                        @if(isset($rec->opposite_docid))--}}
                                    {{--                                            <a href="{{route($sysobjcode . '.edit',$rec->opposite_docid)}}">открыть</a>--}}
                                    {{--                                        @endif--}}
                                    {{--                                    </div>--}}

                                    {{--                                    <div class="form-group offset-md-0 col-md-5">--}}
                                    {{--                                        <label for="address">Формат:</label>--}}
                                    {{--                                        {!! Form::select('doc_templateid', $rec->doc_templates, $rec->doc_templateid,--}}
                                    {{--                                         [--}}
                                    {{--                                         'class' => 'form-control',--}}
                                    {{--                                         'placeholder' => '-выбор-',--}}
                                    {{--                                         ]) !!}--}}
                                    {{--                                    </div>--}}
                                </div>

                                {{--                                <div class="row">--}}
                                {{--                                    <div class="form-inline offset-md-2 col-md-10">--}}
                                {{--                                        <input id="flag_184" name="flag_184" class=""--}}
                                {{--                                               type="checkbox" value="1" {{($rec->flag_184==1)?'checked':''}}>--}}
                                {{--                                        <label for="flag_184" style="color: rgb(73, 80, 87);">Не показывать в информере--}}
                                {{--                                            "Договоры, внесенные в систему за последние 7 дней"</label>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}

                                {{--                                <div class="row">--}}
                                {{--                                    <div class="form-inline col-md-10">--}}
                                {{--                                        <div class="custom-control custom-checkbox mr-sm-2">--}}
                                {{--                                            <input id="flag_184" name="flag_184" class="custom-control-input"--}}
                                {{--                                                   type="checkbox" value="1" {{($rec->flag_184==1)?'checked':''}}>--}}
                                {{--                                            <label class="custom-control-label" for="flag_184">Не показывать в информере--}}
                                {{--                                                "Договоры, внесенные в систему за последние 7 дней"</label>--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                </div>--}}

                                <hr>
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                &nbsp;
                                <a class="btn btn-close btn-info" href="{{ $route_index }}"
                                   title="Вернуться в список">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($rec->id != -1 and $usrrights['delete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($sysobjcode . '.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                @endif
                                @if ($rec->id != -1 and $usrrights['make_template']??true)
                                    <button type="submit"
                                            class="btn btn-info btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($sysobjcode . '.make_template', $rec->id)}}"
                                            formmethod="get"
                                            onclick="return confirm('Создать шаблон для новых записей на основе данных текущей записи?')"
                                            title="Создать шаблон на основе данных записи"
                                    >
                                        <i class="fa fa-clone" aria-hidden="true"></i>
                                    </button>

                                    @if(isset($rec->template_id))
                                        <a href="{{ route('user_templates.delete', $rec->template_id)}}?returl={{Request::url()}}"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Удалить текущий шаблон?')"
                                           title="Удалить текущий шаблон"
                                        >
                                            <i class="fa fa-minus-circle" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                @endif
                                &nbsp;
                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>

                @if (1==1 and $rec->id != -1)
                    <div class="col-md-6">

                        <?php $FlagsHeader = "Флаги объекта" ?>
                        @include('objflags/objflags')

                        @include('contracts.contract_orgs')
                        @include('objfiles/obj_files')
                        {{--                        @include('contracts.reviews')--}}
                        {{--                        @include('contracts.contract_prices')--}}
                        {{--                        @include('contracts._workplans')--}}
                        {{--@include('contracts/_pays')--}}
                        @include('contracts/_paydocs')
                        {{--                        @include('contracts/contract_exes')--}}
                        @include('contracts/linked_contracts')
                        @includeif('tasks/linked_tasks')
                        {{--                        @includeif('contracts/budgets')--}}
                        {{--                        @includeif('contracts/org_extservices')--}}
                        @include('obj_staffs/_staffs')
                        @include('obj_readers/_readers')
                        {{--                        @include('contracts/obj_readers')--}}
                        {{--						@include('contracts/obj_comments')--}}
                        {{--                        @include('obj_msgs._msgs')--}}

                    </div>
                @endif

            </div>
        </div>
        <script src="{{ asset('js/contract_edit.js') }}" defer></script>
    @endif
@endsection
@section('title')
    {{$thisTitle}}
@endsection
