@extends('layouts.edit')

@section('content')
    @if (!isset( $machine))
        <?php
        redirect()->route('machines.index');
        header("Location:" . route('machines.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

        <?php
        $sysobjid = 482;
        $objcode = 'machines';
        $ThisTitle = "Техника";

        $rec = $machine;
        $route_index = route($objcode . '.index') . "?page=" . session($objcode . '_pageno') . '#' . $rec->id;
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
                <div class="col-md-6">
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

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route('machines.update', $rec->id) }}">
                                @method('PUT')
                                @csrf

                                <div class="row">
                                    <div class="col-md-3">
                                        <?php
                                        $photo = '<img src="/images/signs/no-image.png" class="photo">';
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
                                    <div class="form-group col-md-9">
                                        <label for="name" class="required">Название:</label>
                                        <input type="text" class="form-control font-weight-bold" name="name"
                                               maxlength="120"
                                               value="{{old('name',$rec->name)}}"/>
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="regnum" class="required">Гос. рег. номер:</label>
                                        <input type="text" class="form-control font-weight-bold" name="regnum"
                                               maxlength="20"
                                               value="{{old('regnum',$rec->regnum)}}"/>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <label for="mchntypeid" class="required">Тип:</label>
                                        {!! Form::select('mchntypeid', $rec->mchntypes, $rec->mchntypeid,
                                         [
                                         'class' => 'form-control',
                                         'placeholder' => '-выбор-',
                                         ]) !!}
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="fueltypeid" title="Используемый вид топлива">Исп. топливо:</label><br><br>
                                        {!! Form::select('fueltypeid', $rec->fueltypes, $rec->fueltypeid,
                                         [
                                         'class' => 'form-control',
                                         'placeholder' => '-выбор-',
                                         ]) !!}
                                    </div>
                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="address">Норма расхода на 100км, л:</label>
                                        <input type="number" class="form-control" name="fuelper100km"
                                               min="0" step="0.1"
                                               value="{{old('fuelper100km',$rec->fuelper100km)}}"/>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="address">Норма расхода за 1ч, л:</label>
                                        <input type="number" class="form-control" name="fuelper1hour"
                                               min="0" step="0.1"
                                               value="{{old('fuelper1hour',$rec->fuelper1hour)}}"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="descript">Описание:</label>
                                    <textarea class="form-control rounded-0" name="descript" id="descript"
                                              rows="3">{{ $rec->descript }}</textarea>
                                </div>

                                <div class="row">

{{--                                    <div class="form-group col-md-12">--}}
{{--                                        <label for="orgid" class="required">Владелец:</label>--}}
{{--                                        {!! Form::select('orgid', $rec->ownorgs, $rec->orgid,--}}
{{--                                         [--}}
{{--                                         'class' => 'form-control',--}}
{{--                                         'placeholder' => '-выбор-',--}}
{{--                                         ]) !!}--}}
{{--                                    </div>--}}

                                    <div class="form-group offset-md-0 col-md-12">
                                        <label for="name" class="required"><span id="lbl_org">Владелец</span>:</label>
                                        @if ($usrrights['save'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="org_name" required id="org_name"
                                                       class="ac_name ac_org_name form-control font-weight-bold"
                                                       value="{{old('org_name',$rec->org->info)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       title=""
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="orgid" class="ac_id" id="orgid"
                                                       data-gk="{{$rec->org_gk}}"
                                                       value="{{old('orgid',$rec->orgid)}}">
                                                <a class="btn btn-light id_lnk" data-id="orgid" data-obj="orgs"
                                                   target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                            <div></div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->org->info}}</div>
                                        @endif
                                    </div>

                                </div>
                                <div class="form-group">
                                    <label for="active" style="color: rgb(73, 80, 87);">Активный:</label>
                                    {!! Form::checkbox('active', 1, $rec->active==1) !!}
                                </div>

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
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route('machines.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                @endif
                                &nbsp;
                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>

                @if (1==1 and $rec->id != -1)
                    <div class="col-md-6">

                        @include('objfiles.obj_files')
                        {{--@include('machines/obj_images')--}}

                        <div class="card d-none d-sm-block  p-2 my-2 my-md-3"
                             style="min-width:400px !important;">

                            <div class="hdr">
                                <div class="hdr-btn">
                                </div>
                                <div class="title">
                                    доп. информация
                                </div>
                            </div>
                            <div class="card-body">

                                <table class="table">
                                    <tbody>
                                    @foreach($auxinfo as $itm)
                                        <?php
                                        $btn_class = "btn-warning";
                                        if (isset($itm['btn-class'])) {
                                            $btn_class = $itm['btn-class'];
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                @if(isset($itm['route']))
                                                    <a href="{{ route($itm['route'],$rec->id)}}"
                                                       class="btn btn-sm org-aux {{$btn_class}}"
                                                       title="{{$itm['name']}}">
                                                        {{$itm['name']}}
                                                    </a>
                                                @else
                                                    {{$itm['name']}}
                                                @endif
                                            </td>
                                            <td class="l small">
                                                {!! $itm['sample'] !!}
                                            </td>
                                            <td class="r small">
                                                {{$itm['reccount']}}
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php $FlagsHeader = "Флаги объекта" ?>
                        @include('objflags/objflags')

                        @include('machines._opertypes')

                        @include('machines.machine_prices')
                        @include('machines.machine_controrgs')

                    </div>

                @endif


            </div>
        </div>
        <script src="{{ asset('js/machine_edit.js') }}" defer></script>
    @endif
@endsection
