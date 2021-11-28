@extends('layouts.app')

@section('content')
    <?php
    $sysobjid = 3;
    ?>
    <style>
        .org_linked {
            background-color: #efc2c2
        }

        .objlog_link {
            font-size: 9px;
        }
    </style>

    <form name="forIndex" id="forIndex" method="post" action="{{ route('acslst.index',$data->sysobj->id) }}">
        @csrf

        <div class="container">

            <?php
            $thisTitle = 'ACL';

            $breadcrumbs = [
                'Настройки' => "/admin",
                'Admin' => "/admin?tab=nsi-admin",
                $thisTitle => null,
            ];
            ?>
            @include('layouts.breadcrumbs')

            <div class="row justify-content-center">
                <div class="row">
                    <div class="col-md-9">
                        <h3>{{$thisTitle}}: {{$data->sysobj->code}} - {{$data->sysobj->name}}
                            <span class="small  float-right">
                                <?php
                                $index_route = $data->sysobj->code . '.index';
                                ?>
                                @if( Route::has($index_route) )
                                    <a href="{{route($data->sysobj->code.'.index')}}" class="mr-2">список</a>
                                @endif
							<a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>0,'route'=>Route::current()->getName()])}}"
                               class="objlog_link "
                               title="Журнал общих событий"
                            >журнал</a>
                                </span>
                        </h3>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group ">
                            {!! Form::select('s_active', $data->statuses??[]
                            , $data->search_params['s_active']??'',
                                 [
                                 'class' => 'form-control',
                                 'placeholder' => '-',
                                 'onchange' => 'form.submit()',
                                 ]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">

                        @include('layouts.edit_msgs')

                        <table class="table table-striped table-hover">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Пользователь</th>
                                <th scope="col">Кол-во прав</th>
                                <th>@if ($usrrights['create'])
                                        <a href="{{ route('acslst.create',['sysobjid'=>$data->sysobj->id, 'usrid'=>0])}}"
                                           class="btn btn-warning btn-sm"
                                           title="Добавить запись">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif</th>
                            </tr>
                            <tr style="text-align: center;">
                                <td/>
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control text-center" name="s_name"
                                               value="{{$data->search_params['s_name'] ?? ''}}"/>
                                    </div>
                                </td>
                                <td>
                                    {!! Form::select('s_sysfuncid', $data->sysfuncs
                                        , $data->search_params['s_sysfuncid'],
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-все-',
                                             ]) !!}
                                </td>
                                <td>
                                    <div class="input-group-btn">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                formaction="{{ route('acslst.index',$data->sysobj->id) }}"
                                                formmethod="post" title="Поиск">
                                            <i class="fa fa-search" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </thead>
                            <tbody>
                            @if (count($recs)>0)

                                @foreach($recs as $rec)
                                    @php
                                        $linestyle = ($rec->active == 0)?"background-color:lightsalmon;":'';
                                    @endphp
                                    <tr style="{{$linestyle}}">
                                        <td scope="row" class="small text-right">{{$loop->index+1}}</td>
                                        <td>
                                            <a href="{{ route('acslst.edit',['sysobjid'=>$data->sysobj->id, 'usrid'=>$rec->id])}}"
                                               title="Просмотреть/Изменить запись">
                                                <div style="display: table-cell;padding-left: 8px;">{{$rec->name}}</div>
                                            </a>
                                        </td>
                                        <td class="text-center">{{$rec->rights_cnt}}</td>

                                        <td style="text-align: center;">
                                            <a href="{{ route('acslst.edit',['sysobjid'=>$data->sysobj->id, 'usrid'=>$rec->id])}}"
                                               class="btn btn-sm btn-primary"
                                               title="Просмотреть/Изменить запись">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center">Данные не найдены.</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>

                        @includeIf('layouts.paginate_links')

                    </div>
                </div>

            </div>
        </div>
    </form>
@endsection
