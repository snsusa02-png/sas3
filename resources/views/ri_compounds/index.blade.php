@extends('layouts.app')

@section('content')

    <?php
    $thisTitle = "Составы изделий";
    $thisSysObjCode = 'ri_compounds';

    $userid = \Auth::user()->id;
    $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1
    ?>
    <link rel="stylesheet" href="/css/subnav.css">
    <form name="forIndex" id="forIndex" method="post" action="{{ route('ri_compounds.index') }}">
        @csrf

        <div class="container">
            <?php
            $breadcrumbs = [
                'Данные' => "/rqsts",
                'Производство' => "/rqsts?tab=nsi-prods",
                $thisTitle => null,
            ];
            ?>
            @include('layouts.breadcrumbs')

            <div class="row justify-content-center">
                <div class="col-md-10">

                    @include('layouts.edit_msgs')

                    <h4>{{$thisTitle}}</h4>

                    <div class="row mb-2">
                        <div class="offset-md-6 col-md-6 ">
                            <div class="subnav shift text-right">
                                @if(isset($data->top_right_menu))
                                    <ul>
                                        @foreach( $data->top_right_menu as $itm)
                                            <li><a href="{{$itm->url}}?returl={{Request::url()}}"
                                                   title="{{$itm->title}}">{{$itm->name}}</a></li>
                                        @endforeach
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,$thisSysObjCode.'.set_lockdate'))
                                            <li><a href="{{route('sysobj_lockdates.edit',$data->sysobj->id)}}"
                                                   title="Установка даты блокировки данных"><i class="fa fa-lock "
                                                                                               aria-hidden="true"></i></a>
                                            </li>
                                        @endif
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div>
                        <table data-toggle="table" class="table display table-striped table-condensed">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th class="th-sm">Изготовитель</th>
                                <th scope="col" class="th-sm">Изделие</th>
                                <th scope="col" class="th-sm">Период</th>
                                <th scope="col" class="th-sm">Статус</th>
                                <td class="text-right">
                                    @if($usrrights['create'])
                                        <a href="{{ route('ri_compounds.create')}}"
                                           class="btn btn-warning btn-sm"
                                           title="Добавить запись">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            <tr style="text-align: center;">
                                <td colspan="2">
                                    <div class="input-group">
                                        {!! Form::select('s_ownorg',
                                         $data->s_ownorgs??[],
                                         $data->search_params['s_ownorg'] ?? '',
                                        ['class' => 'form-control']) !!}

                                    </div>
                                </td>
                                <td>

                                    <input type="text" class="form-control c" name="s_ri_name"
                                           id="s_ri_name"
                                           value="{{ $data->search_params['s_ri_name'] ?? ''}}"
                                           placeholder="-название-"
                                           STYLE="display: block;"/>
                                </td>
                                <td>
                                </td>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_statuscode',
                                         $data->s_statuscodes??[],
                                         $data->search_params['s_statuscode'] ?? '',
                                        ['class' => 'form-control']) !!}
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="input-group-btn">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                formaction="{{ route('ri_compounds.index') }}"
                                                formmethod="post" title="Поиск">
                                            <i class="fa fa-search" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </thead>
                            <tbody>

                            @if (count($recs)>0)
                                <?php
                                $cur_ownorgid = -1;
                                ?>
                                @foreach($recs as $item)
                                    @if($item->ownorgid<>$cur_ownorgid)
                                        <tr style="background-color: #fffcb1">
                                            <td colspan="5"><b>{{$item->ownorg_name}}</b>
                                            </td>
                                            <td class="text-right">
                                                @if ($usrrights['create'])
                                                    <a href="{{ route($thisSysObjCode.'.create')."?ownorgid={$item->ownorgid}"}}"
                                                       class="btn btn-warning btn-sm"
                                                       title="Добавить запись">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                        <?php
                                        $cur_ownorgid = $item->ownorgid;
                                        $cur_id = -1;
                                        $cur_opertypeid = -1;
                                        $npp = 0;
                                        ?>
                                    @endif
                                    <?php
                                    $trStyle = "";
                                    if ($item->docsigned == 1) {
                                        $trStyle = "background-color: #CCECF9";
                                    }
                                    ?>
                                    <tr style="{{$trStyle}}">
                                        <td scope="row" class="small text-right">{{$loop->index+0+$rec0}}</td>
                                        <td/>
                                        <td><a href="{{ route('ri_compounds.edit',$item->id)}}"
                                               target="_self" class="font-weight-bold">{{$item->ri_name}}</a>
                                            <div class="small font-italic"> {{$item->notes}}</div>
                                        </td>
                                        <td class="text-center">
                                            {{$item->begdate}} - {{$item->enddate}}
                                        </td>
                                        <td class="text-center">
                                            {{$item->statusname}}
                                            {{--                                            <a href="{{ route($thisSysObjCode.'.clone',$item->id)}}"--}}
                                            {{--                                                 class="btn btn-sm btn-warning ml-1"--}}
                                            {{--                                                 title="Создать копию записи"--}}
                                            {{--                                                 onclick="return confirm('Создать копию записи?')">--}}
                                            {{--                                                <i class="fa fa-files-o" aria-hidden="true"></i>--}}
                                            {{--                                            </a>--}}
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('ri_compounds.edit',$item->id)}}"
                                               class="btn btn-sm btn-primary"
                                               title="Просмотреть/Изменить запись">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <?php
                                if (Route::currentRouteName() == "refitems.search") {
                                    $msg = "Данные не найдены. \nПопробуйте изменить критерий поиска и повторить.";
                                } else {
                                    $msg = "Нет записей.";
                                }
                                ?>
                                <tr>
                                    <td colspan="6" class="c">{{$msg}}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>

                        @include('layouts.paginate_links')

                    </div>
                </div>
            </div>
        </div>
        <script src="{{ asset('js/wrhdocs_index.js') }}" defer></script>

    </form>

@endsection

