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

        <?php
        $sysobjid = 11;
        $objcode = 'objfiles';
        $ThisTitle = "Файл";


        $route_index = $retroute ?? route('' . 'contracts.index') . "?page=" . session($objcode . '_pageno') . '#' . $rec->id;

        $card_container_class = ($rec->id == -1) ? 'col-md-10' : 'col-md-6';
        ?>
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .photo {
                display: block;
                max-width: 116px;
                max-height: 136px;
                width: auto;
                height: auto;
                margin: auto;
            }
        </style>

        <div class="container">

            @includeIf('layouts.edit_msgs')

            <div class="row ">
                <div class="{{$card_container_class}}">
                    <div class="card p-2 my-2 my-md-3">
                        <div class="card-header">
                            {{$ThisTitle}}
                            <a class="btn btn-close btn-light btn-sm"
                               style="float:right;"
                               href="{{ $route_index }}"
                               title="Вернуться в список">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="card-body">

                            @include('layouts.err_msgs')

                            @if (isset($rec->_obj_info))
                                <?php
                                $lbl = "для записи";
                                if (isset($rec->_sysobj_name))
                                    $lbl .= " в подсистеме ИС '{$rec->_sysobj_name}'"
                                ?>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="notes">{{$lbl}}:</label>
                                        <div class="font-weight-bold">{{$rec->_obj_info}}</div>
                                    </div>
                                </div>
                            @endif

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($objcode.'.update', $rec->id) }}"
                                  enctype="multipart/form-data">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('returl', $retroute) }}
                                {{ Form::hidden('id', $rec->id) }}
                                {{ Form::hidden('sysobjid', $rec->sysobjid) }}
                                {{ Form::hidden('objid', $rec->objid) }}


                                @if($rec->id==-1)
                                    <div class="clone d-none">
                                        {{--Заготовка строки с файлом для загрузки--}}
                                        <div class="hdtuto control-group lst input-group increment"
                                             style="margin-top:10px">

                                            {{ Form::hidden('docid[]', $rec->id) }}
                                            <input type="file" name="doc[]" class="myfrm form-control"
                                                   accept="{{$rec->accept}}"
                                                   style="padding: 3px;"/>

                                            <div class="input-group" style='width:50%'>
                                                {{ Form::hidden('doctypeid[]', $rec->doctypeid,['class'=>'ac_doctypeid'])}}
                                                {{ Form::hidden('docsubtypeid[]', $rec->docsubtypeid,['class'=>'docsubtypeid'])}}
                                                @if ($usrrights['save']??false)
                                                    <input type="text" class="form-control ac_doctypename"
                                                           name="doctypename"
                                                           value="{{$rec->doctype->name}}"
                                                           placeholder="-тип документа-"
                                                    />
                                                    <input type="text"
                                                           class="form-control text-center small ac_status"
                                                           style="display: none; border: #d7f3e3;"
                                                           readonly>
                                                @else
                                                    <input type="text" name="doctypename[]"
                                                           class="form-control" readonly
                                                           value="{{$rec->doctype->name}}"
                                                    />
                                                @endif
                                            </div>

                                            <div class="input-group-btn">
                                                <button class="btn btn-danger btn-del-file" type="button"><i
                                                            class="fa fa-minus-square" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="input-group hdtuto control-group lst increment ">

                                        <div class="input-group">
                                            {{ Form::hidden('docid[]', $rec->id) }}
                                            <input type="file" name="doc[]" class="myfrm form-control docfile"
                                                   multiple="multiple" accept="{{$rec->accept}}"
                                                   style="padding: 3px;"/>

                                            @if(1==0)
                                                <span style='width:50%'>
                                                    {!! Form::select('doctypeid[]', $rec->doctypes, $rec->doctypeid,
                                                     [
                                                     'class' => 'form-control small',
                                                     'placeholder' => '-тип документа-',
                                                     ]) !!}

                                                    {!! Form::select('docsubtypeid[]', $rec->docsubtypes, $rec->docsubtypeid,
                                                     [
                                                     'class' => 'form-control small',
                                                     'placeholder' => '-подтип документа-',
                                                     ]) !!}
                                                </span>
                                            @else

                                                <div class="input-group mb-1 " style='width:50%'>
                                                    {{ Form::hidden('doctypeid[]', $rec->doctypeid,['class'=>'ac_doctypeid'])}}
                                                    {{ Form::hidden('docsubtypeid[]', $rec->docsubtypeid,['class'=>'docsubtypeid'])}}

                                                    @if ($usrrights['save']??false)
                                                        <input type="text" class="form-control ac_doctypename"
                                                               name="doctypename[]"
                                                               placeholder="-тип документа-"
                                                               value=""
                                                        />
                                                        <input type="text"
                                                               class="form-control text-center small ac_status"
                                                               style="display: none; border: #d7f3e3;"
                                                               readonly>
                                                    @else
                                                        <input type="text" name="doctypename"
                                                               class="form-control" readonly
                                                               value=""
                                                        />
                                                    @endif
                                                </div>

                                            @endif


                                            <div class="input-group-btn">
                                                <button class="btn btn-success add-file-line" type="button"><i
                                                            class="fa fa-plus-square"
                                                            aria-hidden="true"></i></button>
                                            </div>
                                        </div>
                                    </div>


                                @else
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php
                                            //$fileURL = Storage::disk('local')->url($rec->systemfilename);
                                            //override
                                            $fileURL = route('objfiles.getbyid', $rec->id);

                                            if (substr($rec->mimetype->mimetype, 0, 6) == 'image/') {
                                                //отображаемое напрямую
                                                $iconfile = $fileURL;
                                            } else {
                                                //отобразим иконкой типа файла
                                                $iconfile = $rec->mimetype->iconfile ?? '/images/fileicons/unknown.png';
                                            }

                                            ?>
                                            <a href="{{$fileURL}}" target="_blank"><img src="{{$iconfile}}"
                                                                                        class="img-thumbnail"
                                                                                        style="width:100%"/></a>
                                        </div>
                                        <div class="offset-md-1 col-md-8">
                                            <div class="form-group">
                                                <label for="a">Имя файла:</label>
                                                {{$rec->publicfilename}}
                                            </div>
                                            <div class="form-group">
                                                <label for="a">Размер файла:</label>
                                                {{round($rec->filesize/1024,0)}}КБ
                                            </div>
                                            <div>
                                                <label>Тип документа:</label>
                                                {!! Form::select('doctypeid[]', $rec->doctypes, $rec->doctypeid,
                                     [
                                     'class' => 'form-control small',
                                     'placeholder' => '-тип документа-',
                                     ]) !!}
                                                {!! Form::select('docsubtypeid[]', $rec->docsubtypes, $rec->docsubtypeid,
                                                     [
                                                     'class' => 'form-control small',
                                                     'placeholder' => '-подтип документа-',
                                                     ]) !!}
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="form-group">
                                    <label for="descript">Описание:</label>
                                    @if ($usrrights['save']??false)
                                        <textarea class="form-control rounded-0" name="notes" id="descript"
                                                  rows="2">{{ $rec->notes }}</textarea>
                                    @else
                                        {{ $rec->notes }}
                                    @endif
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-8 col-md-4">
                                        <label for="descript">Порядок вывода:</label>
                                        @if ($usrrights['save']??false)
                                            <input type="number"
                                                   min="0" max="999"
                                                   class="form-control text-center small"
                                                   name="ordr" value="{{old('ordr',$rec->ordr)}}"
                                            >
                                        @else

                                        @endif
                                    </div>
                                </div>

                                @if (false)
                                    <div class="row">
                                        <div class="form-group col-md-12  rt1_hide rt2_show rt3_hide">
                                            <label for="address">Тип документа:</label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('doctypeid', $rec->doctypes, $rec->doctypeid,
                                                 [
                                                 'id' => 'doctypeid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '-выбор-',
                                                 ]) !!}
                                            @else
                                                <div class="font-weight-bold">{{$rec->doctype->name}}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <hr>
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения">
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
                                    @if(1==0)
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                style="margin-left:24px"
                                                formaction="{{ route('objfiles.delete', $rec->id)}}"
                                                formmethod="post"
                                                onclick="return confirm('Вы действительно хотите удалить файл?')"
                                                title="Удалить запись"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    @endif

                                    <a href="{{ route('objfiles.destroy',$rec->id).'?returl='.$retroute}}"
                                       class="btn btn-danger btn-sm ml-4" title="Удалить файл"
                                       onclick="return confirm('Вы действительно хотите удалить файл?')">
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </a>

                                @endif
                                @if($usrrights['make_doc_by_file']??false)
                                    <a href="{{ route('objfiles.make_document',$rec->id).'?returl='.$retroute}}"
                                       class="btn btn-warning btn-sm ml-4" title="Создать документ"
                                       onclick="return confirm('Вы действительно хотите создать запись о документе на основе данного файла?')">
                                        <i class="fa fa-long-arrow-right mr-1" aria-hidden="true"></i><i class="fa fa-file-text-o" aria-hidden="true"></i>
                                    </a>
                                @endif
                                &nbsp;
                                @if ($rec->id != -1)
                                    @include('layouts._who_when')
                                @endif
                            </form>
                        </div>
                    </div>
                </div>

                @if (1==1 and $rec->id != -1)
                    <div class="col-md-6">

                        {{--						@include('machines/obj_images')--}}

                    </div>

                @endif


            </div>
        </div>
        <script src="{{ asset('js/objfile_edit.js') }}" defer></script>
    @endif
@endsection
