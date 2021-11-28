@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('jobtimesheets.index');
        header("Location:" . route('jobtimesheets.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
        <link rel="stylesheet"
              href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css"/>
        <script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>


        <?php
        $sysobjid = 1712;
        $thisSysObjId = $sysobjid;
        $sysobjcode = 'ocl_items';
        $objcode = 'ocl_items';
        $ThisTitle = "Дело";

        //$route_index = route('qchecks.edit', $rec->ocl_id);
        //$route_index = route('jobtimesheets.index') . '#' . $rec->id;
        $route_index = route('org_caselists.edit', $rec->ocl_id) . '#item_' . $rec->id;


        $inputReadOnly = '';
        ?>
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .org-aux {
                width: 100%
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

            @includeIf('layouts.edit_msgs')

            <div class="row ">
                <div class="col-md-6">
                    <div class="card p-2 my-2 my-md-3" style="background-color: #f8f8f8">
                        <div class="card-header">
                            {{$ThisTitle}} "<b>{{$rec->org_caselist->org->name}}</b>, {{$rec->org_caselist->begdate}}"
                            <a class="btn btn-close btn-light btn-sm"
                               style="float:right;"
                               href="{{ $route_index }}"
                               title="Вернуться в список">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="card-body">

                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($objcode.'.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('ocl_id', $rec->ocl_id) }}
                                {{ Form::hidden('ttt', 0) }}


                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-3">
                                        <label class="required">Индекс дела</label>
                                        @if ($usrrights['save'])
                                            <input type="text" name="code" id="code"
                                                   class="form-control text-center" required
                                                   value="{{$rec->code}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->code}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group col-md-9">
                                        <label for="name" class="required">Заголовок дела (тома, частей):</label>
                                        @if ($usrrights['save'])
                                            <input type="text" class="form-control rounded-0 font-weight-bold"
                                                   name="name"
                                                   id="name" required
                                                   value="{{old('name',$rec->name)}}">
                                        @else
                                            <div class="font-weight-bold">{{$rec->name}}</div>
                                        @endif
                                    </div>

                                </div>


                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="name" class="required">Подразделение:</label>
                                        @if ($usrrights['save'])
                                            <div class="input-group">

                                                {!! Form::select('orgdepid', $rec->orgdeps??[], $rec->orgdepid,
                                                 [
                                                 'id' => 'orgdepid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '',
                                                 'required' => 'required',
                                                 ]) !!}

                                                <a class="btn btn-light" id="depid_lnk"
                                                   target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>

                                        @else
                                            <div class="font-weight-bold">{{$rec->name}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="case_qty" style="color: rgb(73, 80, 87);" class="required"
                                               title="Кол-во дел (томов, частей)">Кол-во дел:</label>
                                        <input type="number" class="form-control text-right"
                                               name="case_qty"
                                               min="1" required
                                               value="{{$rec->case_qty}}"/>
                                    </div>
                                    <div class="form-group col-md-9">
                                        <label for="name" class=""
                                               title="Срок хранения дела и номера статей по перечню">Срок хранения
                                            дела:</label>
                                        @if ($usrrights['save'])
                                            <input type="text" class="form-control rounded-0 font-weight-bold"
                                                   name="shelflife_reason"
                                                   id="shelflife_reason"
                                                   value="{{old('name',$rec->shelflife_reason)}}">
                                        @else
                                            <div class="font-weight-bold">{{$rec->shelflife_reason}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="offset-md-0 col-md-12">
                                        <div class="form-group">
                                            <label for="decision">Примечание:</label>
                                            @if ($usrrights['save'] )
                                                <textarea class="form-control rounded-0"
                                                          name="notes" id="notes"
                                                          rows="1">{{old('notes',$rec->notes)}}</textarea>
                                            @else
                                                <div class="font-weight-bold">
                                                    <div class="font-weight-bold">{{$rec->notes??'-'}}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="offset-md-0 col-md-12">
                                        <div class="form-group">
                                            <label for="descript" class="required">Категория информации (для доступа):</label>
                                            @if($usrrights['acs.edit'] and $usrrights['save']??false)
                                                {!! Form::select('acsid', $rec->acs??[], $rec->acsid,
                                                                                         [
                                                                                         'class' => 'form-control',
                                                                                         'placeholder' => '-выбор-',
                                                                                         'required' => 'required',
                                                                                         ]) !!}
                                            @else
                                                <div class="border p-2">{{$rec->ac->name}}&nbsp;</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="ordr" style="color: rgb(73, 80, 87);"
                                               title="Порядок вывода в списках">Очередность:</label>
                                        <input type="number" class="form-control text-right"
                                               name="ordr"
                                               min="0" step="10"
                                               value="{{$rec->ordr}}"/>
                                    </div>
                                </div>


                                @if(1==0)
                                    <div class="row">
                                        <div class="form-group offset-md-1 col-md-5">
                                            <label for="active" style="color: rgb(73, 80, 87);">Готовность:
                                            </label>
                                            <?php
                                            $activetypes = [0 => 'черновик', 1 => 'опубликовано'];
                                            ?>
                                            @if ($usrrights['save'] )
                                                {!! Form::select('active', $activetypes
                                                    , old('active', $rec->active),
                                                     [
                                                     'id' => 'active',
                                                     'class' => 'form-control',
                                                     'placeholder' => '-',
                                                     ]) !!}
                                            @else
                                                <div
                                                    class="font-weight-bold">{{$activetypes[$rec->active]??'?'}}</div>
                                            @endif

                                        </div>
                                    </div>
                                @endif

                                <hr>
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения"
                                            name="update">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                &nbsp;
                                <a class="btn btn-close btn-info" href="{{ $route_index }}"
                                   title="Вернуться в список проектов">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($rec->id != -1 and $usrrights['delete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($objcode.'.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                @endif
                                &nbsp;
                                @if ($rec->id != -1)
                                    <div class="small" style="margin-top: 8px; color:gray;">
                                        создана: {{$rec->created_at}} / {{$rec->whocrt->short_fio()}}
                                        &nbsp;
                                        изменена: {{$rec->updated_at}} / {{$rec->whoupd->short_fio()}}
                                        <br><a
                                            href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
                @if($rec->id<>-1)
                    <div class="col-md-6">
                        {{--                        @include('objfiles.obj_files')--}}
                        {{--                        @include('obj_readers._readers')--}}
                    </div>
                @endif
            </div>
        </div>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/ocl_items_edit.js') }}" defer></script>

    @endif
@endsection
