@extends('layouts.app')

@section('content')

    <?php
    $thisTitle = "Отчеты ИС";
    $thisSysObjCode = 'reports';
    ?>

    <style>
        th {
            vertical-align: top !important;
        }
    </style>
    <form name="forIndex" id="forIndex" method="post"
          action="{{ route($thisSysObjCode.'.index') }}">
        @csrf

        <div class="container">

            <?php
            $breadcrumbs = [
                'Сервис' => "/admin?tab=nsi-dic#reports",
                'Справочники' => "/admin?tab=nsi-dic#reports",
                $thisTitle => null,
            ];
            ?>
            @includeIf('layouts.breadcrumbs')

            @include('layouts.edit_msgs')

            <div class="row justify-content-center">
                <div class="col-md-12 col-sm-12 ">
                    <h3>{{$thisTitle}}</h3>
                    <?php
                    //var_dump($sort_params);
                    $statuses = [0 => 'черновик', 1 => 'доступен'];
                    $status_css = [0 => 'background-color:#f9cbcb !important;', 1 => ''];
                    ?>
                    <table class="table table-striped table-condensed">
                        <thead>
                        @php
                            $sort_params = session('sort_params');
                            if (isset($sort_params)) {
                                $sort_by = $sort_params['field'];
                                $sort_dir = $sort_params['dir'];
                            }
                        @endphp
                        <tr>
                            <td>Область</td>
                            <td>Название, описание</td>
                            <td>Статистика использования</td>
                            <td>Статус</td>

                            <td style="text-align: center;">
                                @if ($usrrights['create'])
                                    <a href="{{ route($thisSysObjCode.'.create')}}"
                                       class="btn btn-warning btn-sm"
                                       title="Добавить запись">
                                        <i class="fa fa-plus"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>

                        <tr style="text-align: center;">
                            <td></td>
                            <td>
                                <input type="text" class="form-control text-center"
                                       name="s_name"
                                       value="{{$search_params['s_name'] ?? ''}}"
                                       placeholder=""
                                />
                            </td>
                            <td></td>
                            <td></td>
                            <td>
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                            formaction="{{ route($thisSysObjCode.'.index') }}"
                                            formmethod="post" title="Поиск">
                                        <i class="fa fa-search" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $curOwnOrgID = -1;
                        ?>
                        @if (count($recs)>0)
                            @foreach($recs as $rec)
                                <?php
                                $stage_css = ""
                                ?>
                                @if($rec->ownorgid<>$curOwnOrgID)
                                    <tr>
                                        <td colspan="10" class="font-italic font-weight-bold"
                                            style="background-color: #fdffd1">
                                            {{$rec->ownorgname}}
                                        </td>
                                    </tr>
                                    <?php
                                    $curOwnOrgID = $rec->ownorgid;
                                    ?>
                                @endif
                                <tr style="{{$rec->OrdStyle}}">
                                    <td></td>
                                    <td scope="row" class="text-left">
                                        <a href="{{route($thisSysObjCode.'.edit',$rec->id)}}" target="_self">
                                            <b>{{$rec->name}}</b>
                                        </a>
                                        <div class="ml-3 mt-1 font-italic small">
                                            {{$rec->descript}}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class=""> {{$rec->use_cnt}}</div>
                                        @if(isset($rec->lastuse_dt))
                                            <div class="small"> {{date_create($rec->lastuse_dt)->format('d.m.Y H:i')}}
                                                <br>{{$rec->lastuse_username}}</div>
                                        @endif
                                    </td>
                                    <td style="{{$status_css[$rec->active??0]}}">
                                        {{$statuses[$rec->active??0]}}
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="{{ route($thisSysObjCode.'.edit',$rec->id)}}"
                                           class="btn btn-sm btn-primary"
                                           title="Просмотреть/Изменить запись">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <?php
                            $msg = "Данные не найдены. \nПопробуйте изменить критерий поиска.";
                            ?>
                            <tr>
                                <td colspan="6" class="c">{{$msg}}</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                    <div>
                        {{$recs->links()}}
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

