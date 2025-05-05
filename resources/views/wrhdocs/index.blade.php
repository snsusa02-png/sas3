@extends('layouts.app')

@section('content')

    <?php
    $thisTitle = "Документы учета склада";
    $thisSysObjCode = 'wrhdocs';

    $userid = \Auth::user()->id;
    $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1
    ?>
    <link rel="stylesheet" href="/css/subnav.css">
    <form name="forIndex" id="forIndex" method="post" action="{{ route('wrhdocs.index') }}">
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
                        <div class="offset-md-4 col-md-8 ">
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

                    <div class="row mb-3">
                        <div class="offset-md-0 col-md-3 ">
                            <div class="form-group">
                                <label for="lname">Владелец:</label>
                                {!! Form::select('s_ownorgid', $data->ownorgs??[], $data->search_params['s_ownorgid']??'',
                                    [
                                    'class' => 'form-control small',
                                    'placeholder' => '-все-',
                                    'id' => 's_ownorgid',
                                    'onchange' => 'form.submit()',
                                    ])
                                !!}
                            </div>
                        </div>

                        <div class="offset-md-0 col-md-3 ">
                            <div class="form-group">
                                <label for="lname">Контрагент:</label>
                                {!! Form::select('s_orgid', $data->orgs??[], $data->search_params['s_orgid']??'',
                                    [
                                    'class' => 'form-control small',
                                    'placeholder' => '-все-',
                                    'id' => 's_orgid',
                                    'onchange' => 'form.submit()',
                                    ])
                                !!}
                            </div>
                        </div>
                        <div class="offset-md-0 col-md-3 ">
                            <div class="form-group">
                                <label for="lname">Номенклатура:</label>
                                {!! Form::text('s_ri_name', $data->search_params['s_ri_name'],
                                                [
                                                'class' => 'form-control',
                                                'placeholder' => '-Название товара-',
                                                ])
                                                !!}
                            </div>
                        </div>
                        <div class="offset-md-0 col-md-3 ">
                            <div class="form-group">
                                <label for="lname">Диспетчер:</label>
                                {!! Form::select('s_disp_staffid', $data->s_disp_staffids??[], $data->search_params['s_disp_staffid']??'',
                                   [
                                   'class' => 'form-control small',
                                   'placeholder' => '-все-',
                                   'id' => 's_disp_staffid',
                                   'onchange' => 'form.submit()',
                                   ])
                               !!}
                            </div>
                        </div>
                    </div>
                    <div>
                        <table data-toggle="table" class="table display table-striped table-condensed">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col" class="th-sm">Документ</th>
                                <th class="th-sm">Склад</th>
                                <th scope="col" class="th-sm">Статус</th>
                                <td class="text-right">
                                    @if($usrrights['create'])
                                        <a href="{{ route('wrhdocs.create')}}"
                                           class="btn btn-warning btn-sm"
                                           title="Добавить запись">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            <tr style="text-align: center;">
                                <td/>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_timestatuscode', $data->timestatuses??[], $data->search_params['s_timestatuscode']??'',
                                            [
                                            'class' => 'form-control small',
                                            'placeholder' => '-дата: любая-',
                                            'id' => 's_timestatuscode',
                                            'onchange' => 'form.submit()',
                                            ])
                                        !!}
                                        <input type="date" class="form-control c" name="s_docdate"
                                               id="s_docdate"
                                               value="{{ $data->search_params['s_docdate'] ?? ''}}"
                                               placeholder="-дата док-та-"
                                               STYLE="display: none;"/>

                                        {!! Form::select('s_inpout', $data->s_inpouts??[], $data->search_params['s_inpout'] ?? '', ['class' => 'form-control']) !!}

                                        {!! Form::select('s_doctypeid',
                                         $data->s_doctypes??[],
                                         $data->search_params['s_doctypeid'] ?? '',
                                        ['class' => 'form-control']) !!}

                                        <input type="text" class="form-control c" name="s_docnum"
                                               value="{{$data->search_params['s_docnum'] ?? ''}}"
                                               placeholder="№ док-та"/>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <div class="input-group">
                                            {!! Form::select('s_wrhid',
                                             $data->s_wrhs??[],
                                             $data->search_params['s_wrhid'] ?? '',
                                            ['class' => 'form-control']) !!}
                                        </div>
                                    </div>
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
                                                formaction="{{ route('wrhdocs.index') }}"
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
                                //$curDate = date_format(date_create(), 'Y-m-d');
                                $cur_wrkdate = -1;
                                ?>
                                @foreach($recs as $item)
                                    @if($item->docdate<>$cur_wrkdate)
                                        <tr style="background-color: #fffcb1">
                                            <td colspan="4"><b>{{date_format(date_create($item->docdate),"d.m.Y")}}</b>
                                            </td>
                                            <td class="text-right">
                                                @if ($usrrights['create'])
                                                    <a href="{{ route($thisSysObjCode.'.create', 0)."&docdate={$item->docdate}"}}"
                                                       class="btn btn-warning btn-sm"
                                                       title="Добавить запись">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                        <?php
                                        $cur_wrkdate = $item->docdate;
                                        $cur_id = -1;
                                        $cur_opertypeid = -1;
                                        $npp = 0;
                                        ?>
                                    @endif
                                    <?php
                                    $trStyle = "";
                                    $status_name_class = "";
                                    if ($item->docsigned == 1) {
                                        $trStyle = "background-color: #CCECF9";
                                        $status_name_class = "font-weight-bold small";
                                    }
                                    ?>
                                    <tr style="{{$trStyle}}">
                                        <td scope="row" class="small text-right">{{$loop->index+1+$rec0}}</td>
                                        <td><a href="{{ route('wrhdocs.edit',$item->id)}}"
                                               target="_self">№{{$item->docnum}} от <span class="small">{{date_format(date_create($item->docdate),'d.m.Y')}}</span></a>
                                            <div class="small">{{$item->doctype->name}}</div>
                                            <div class="">{{$item->ownorg_name}}</div>
                                            <div class="">{{$item->org_name}}</div>
                                            <div class="small font-italic"> {{$item->remarks}}</div>
                                        </td>
                                        <td class="c">{{$item->wrh->name}}</td>
                                        <td class="text-center">
                                            <span class="{{$status_name_class}}"> {{$item->statusname}}</span>

                                            <a href="{{ route($thisSysObjCode.'.clone',$item->id)}}"
                                               class="btn btn-sm btn-warning ml-1"
                                               title="Создать копию записи"
                                               onclick="return confirm('Создать копию записи?')">
                                                <i class="fa fa-files-o" aria-hidden="true"></i>
                                            </a>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('wrhdocs.edit',$item->id)}}"
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

