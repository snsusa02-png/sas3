@extends('layouts.edit')

@section('content')

    <?php
    $thisTitle = "Информация об отчете";
    $thisSysObjCode = 'reports';
    $thisSysObjId = 855;
    $sysobjid = $thisSysObjId;

    ?>

    @if (!isset( $rec ))
        <?php
        redirect()->route($thisSysObjCode . '.pub_index');
        header("Location:" . route($thisSysObjCode . '.pub_index'));
        die();
        ?>
    @else
        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .btn {
                margin-bottom: 4px;
            }
        </style>

        <?php
        $route_index = route($thisSysObjCode . '.pub_index') . "?page=" . session('pageno') . '#' . $rec->id;

        $thisTitle .= ' "' . $rec->name . '"';

        $bgcols = array('#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC', '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7');
        ?>
        {{--dd(get_defined_vars())--}}
        <div class="container">

            {{--            @include('layouts.edit_msgs')--}}

            <div class="row">
                <div class="col-md-7 col-sm-12" style="min-width:450px;max-width:900px;">

                    <div class="card p-2 my-2 my-md-3 ">
                        <form name="forEdit" id="forEdit" method="post">

                            {{--                            @method('PUT')--}}
                            {{--                            @csrf--}}

                            <div class="card-header">
								<span data-toggle="collapse" data-target="#main" style="cursor: pointer">
				 {{$thisTitle}} </span>

                                <div class="float-right">
                                    @if (1==0)
                                        <button id="btn1" type="button"
                                                class="btn btn-light btn-sm mr-1" data-toggle="collapse"
                                                data-target="#main"
                                        ><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                                    @endif
                                    <a class="btn btn-close btn-light btn-sm"
                                       style="float:right;"
                                       href="{{ $route_index }}"
                                       title="Вернуться в список">
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    </a>
                                </div>

                            </div>

                            <div class="card-body collapse {{(1==1 or ($rec->id==-1) or ($errors->any()))?' show':' '}}"
                                 id="main">

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="notes">Название:</label>
                                        <input type="text" readonly value="{{$rec->name}}"
                                               class="form-control">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="descript" class="">Описание:</label>
                                        <div class="border p-2">{{$rec->descript}}&nbsp;</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="descript">Тэги:</label>
                                        <div class="border p-2">{{$rec->tags_lst}}&nbsp;</div>
                                    </div>
                                </div>

                                <hr size="1">
                                <a class="btn btn-close btn-info" href="{{ $route_index }}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ( $rec->id != -1 )

                                    @if (1==0 and $rec->active==1)
                                        <span class="float-right">
                                        &nbsp;&nbsp;&nbsp;<a
                                                href="{{route('reports.rep'.$rec->id,['route'=>Route::current()->getName()])}}">Запросить</a>
                                        </span>
                                    @endif



                                    {{--TODO:Доделать условия для вывода печатных форм											--}}
                                    @if (1==0 and $usrrights[$thisSysObjCode.'.print']??false)
                                        <div class="row">
                                            <div class="form-group offset-md-4 col-md-8">
                                                <label for="printform">Печать:</label>
                                                {!! Form::select('printform', $printforms ,0,['class' => 'form-control small','onChange'=>'if(this.value) window.open(this.value,"Печатная форма");']) !!}
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <div class="card-footer">
                                @if ($rec->id != -1)
                                    <div class="small" style="margin-top: 8px; color: gray;">
                                        создан: {{$rec->created_at}} / {{$rec->whocrt->name}}
                                        <br>изменен: {{$rec->updated_at}} / {{$rec->whoupd->name}}
                                        {{--                                        <span class="float-right">--}}
                                        {{--                                        &nbsp;&nbsp;&nbsp;<a--}}
                                        {{--                                                href="{{route('objevntlog',['sysobjid'=>$thisSysObjId, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>--}}
                                        {{--                                        </span>--}}
                                    </div>
                                @endif
                            </div>

                        </form>
                    </div>
                </div>

                <div class="col-md-5 col-sm-12">
                    {{--                    @include('objfiles.obj_files')--}}
{{--                    @include('obj_readers/_readers')--}}

                    @include('reports2/_potential_readers')
                    @include('reports2/_users_stat')
                </div>

            </div>

        </div>
    @endif

@endsection
