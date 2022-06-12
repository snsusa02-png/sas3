@extends('layouts.app')

@section('content')

    <?php
    $thisTitle = "Документы учета склада";

    $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1
    ?>
    <link rel="stylesheet" href="/css/subnav.css">
    <form name="forIndex" id="forIndex" method="post" action="{{ route('itmtypes.index') }}">
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
                                        {!! Form::select('s_inpout',
                                         $data->s_inpouts??[],
                                         $data->search_params['s_inpout'] ?? '',
                                        ['class' => 'form-control']) !!}

                                        {!! Form::select('s_doctypeid',
                                         $data->s_doctypes??[],
                                         $data->search_params['s_doctypeid'] ?? '',
                                        ['class' => 'form-control']) !!}

                                        <input type="text" class="form-control c" name="s_docnum"
                                               value="{{$data->search_params['s_docnum'] ?? ''}}"/>
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
                                         $search_params['s_statuscode'] ?? '',
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
                                @foreach($recs as $item)
                                    <?php
                                    $trStyle = "";
                                    if ($item->docsigned == 1) {
                                        $trStyle = "background-color: #CCECF9";
                                    }
                                    ?>
                                    <tr style="{{$trStyle}}">
                                        <td scope="row" class="small text-right">{{$loop->index+1+$rec0}}</td>
                                        <td><a href="{{ route('wrhdocs.edit',$item->id)}}"
                                               target="_self">№{{$item->docnum}} от {{$item->docdate}}</a>
                                            <div class="small">{{$item->doctype->name}}</div>
                                            <div class="">{{$item->ownorg_name}}</div>
                                            <div class="">{{$item->org_name}}</div>
                                            <div class="small font-italic"> {{$item->remarks}}</div>
                                        </td>
                                        <td class="c">{{$item->wrh->name}}</td>
                                        <td class="text-center">
                                            {{$item->statusname}}
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
    </form>

@endsection

