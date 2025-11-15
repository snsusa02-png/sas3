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
            redirect()->route('chargetypes.index');
            header("Location:" . route('chargetypes.index'));
            die();
            ?>
        @else
            <?php
            $sysobjid = 1210;
            $sysobjcode = 'chargetypes';
            $thisTitle = "Вид начисления/удержания";

//            $retRoute = (isset($rec->parent_id))
//                ? route($sysobjcode . '.edit', $rec->parent_id) . "#subitemspage"
//                : route($sysobjcode . '.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;

            $retRoute = route($sysobjcode . '.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;


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
                                      action="{{ route('chargetypes.update', $rec->id) }}">
                                    @method('PUT')
                                    @csrf
                                    {{ Form::hidden('parent_id', $rec->parent_id) }}

                                    @if(isset($rec->parent_id))

                                        @if(isset($rec->itmtypes_path) and count($rec->itmtypes_path)>0)
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <nav class="breadcrumb mt-0">
                                                        <a class="breadcrumb-item" href="{{route('chargetypes.index')}}"><i
                                                                class="fa fa-list text-info" aria-hidden="true"></i></a>
                                                        @foreach(array_reverse($rec->itmtypes_path,true) as $tid=>$name)
                                                            @if(isset($tid))
                                                                <a class="breadcrumb-item"
                                                                   href="{{route('chargetypes.edit',$tid)}}">{{$name}}</a>
                                                            @else
                                                                <span class="breadcrumb-item active">{{$name}}</span>
                                                            @endif
                                                        @endforeach
                                                    </nav>
                                                </div>
                                            </div>
                                        @endif
                                    @endif

                                    <div class="form-group">
                                        <label for="name">Наименование:</label>
                                        <input type="text" class="form-control font-weight-bold" name="name"
                                               value="{{ old('name',$rec->name) }}"/>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="name">Вид:</label>
                                        @if(isset($rec->dirs) and $usrrights['save'])
                                            {!! Form::select('dir', $rec->dirs, $rec->dir,
                                             [
                                                 'id' => 'dir',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             ]) !!}
                                        @else
                                            <input type="hidden" name="dir" value="{{$rec->dir}}">
                                            {{$rec->dir}}
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label for="descript">Описание:</label>
                                        <textarea class="form-control rounded-0" name="descript" id="descript"
                                                  rows="3">{{ old('descript',$rec->descript) }}</textarea>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="active" style="color:rgb(73, 80, 87);"
                                               title="Расчет от ставки">Расчет от ставки:</label>
                                        {!! Form::checkbox('use_price', 1, $rec->use_price==1
,['class'=>'form-control',
'title'=>'Расчет от ставки и количества']) !!}
                                    </div>
                                    @if(1==0)
                                        <div class="form-group">
                                            <label for="quantity">Ссылка на фотографию:</label>
                                            <input type="text" class="form-control" name="photourl"
                                                   value="{{ $rec->photourl }}"/>
                                        </div>
                                    @endif
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="name">Порядок вывода в списках:</label>
                                            <input type="number" class="form-control text-center" name="ordr"
                                                   type="number" min="1" max="255" step="1"
                                                   value="{{ $rec->ordr }}"/>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="active" style="color:rgb(73, 80, 87);"
                                                   title="Доступно для использования">Доступно для
                                                использования:</label>
                                            {!! Form::checkbox('active', 1, $rec->active==1
,['class'=>'form-control',
'title'=>'Доступно для использования']) !!}
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
                                                formaction="{{ route('chargetypes.delete', $rec->id)}}"
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

                    @if($rec->id<>-1)
                        <div class="col-md-5">

{{--                            @include('chargetypes.it_subtypes')--}}

                            {{--						@include('chargetypes.it_specs')--}}

                            @include('chargetypes.it_extids')
                            @include('chargetypes.charge_orgs')

                            {{--						@include('chargetypes.it_impgroups')--}}

                        </div>
                    @endif

                </div>
            </div>
        @endif
    @endguest
@endsection
