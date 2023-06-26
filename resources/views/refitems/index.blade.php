@extends('layouts.app')

@section('content')
    <?php
    $thisTitle = "Номенклатура";
    ?>
    <link rel="stylesheet" href="/css/subnav.css">
    <style>
        .photo {
            /*display: block;*/
            max-width: 80px;
            max-height: 40px;
            /*width: auto;*/
            /*height: auto;*/
        }
    </style>

    <div class="container">

        <?php
        $breadcrumbs = [
            'Сервис' => "/admin",
            'Справочники' => "/admin?tab=nsi-dic",
            $thisTitle => null,
        ];
        ?>
        @includeIf('layouts.breadcrumbs')

        @includeIf('layouts.edit_msgs')

        <form name="forIndex" id="forIndex" method="post" action="{{ route('refitems.index') }}">
            @csrf

            <div class="row justify-content-center">
                <div class="col-md-12">
                    <h3>{{$thisTitle}}</h3>

                    <div class="row mb-2">
                        <div class="col-md-8 ">
                            <div class="subnav shift">
                                <ul>
                                    <li><a href="{{route('itmtypes.index')}}" title="Категории номенклатуры">Категории</a></li>
                                    <li><a href="{{route('ri_sup_prices.index')}}" title="Сводный прайслист от поставщиков">Прайслист</a></li>
                                    <li><a href="{{route('orgs.index')}}" title="Контрагенты">Контрагенты</a></li>
                                    <li><a href="{{route('ri_compounds.index')}}" title="Составы изделий">Изделия</a></li>
                                    <li><a href="{{route('reports.rep33')}}" title="Запас товаров на складах">Запас</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="input-group col-md-4">
                            <label for="s_active">Статус продажи:&nbsp;</label>
                            <?php
                            $s_active_lst = ["" => "-все-", '1' => 'доступно для продажи'
                                , '0' => 'снято с продажи'
                                , '-1' => 'планируется снять с продажи'];
                            ?>
                            {!! Form::select('s_active', $s_active_lst, $s_active ?? '', ['class' => 'form-control']) !!}
                        </div>
                    </div>


                    <table class="table">
                        <caption>{{$thisTitle}}</caption>
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Название</th>
                            <th scope="col">ЕИ</th>
                            <th scope="col" class="text-center">Цена, &#8381;</th>
                            <th scope="col">фото</th>
                            <th scope="col">Категория</th>
                            <td>
                                @if ($usrrights['create'])
                                    <a href="{{ route('refitems.create')}}"
                                       class="btn btn-sm btn-warning"
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
                                    <input type="text" class="form-control c" name="s_name"
                                           value="{{$search_params['s_name'] ?? ''}}"/>
                                </div>
                            </td>
                            <td></td>
                            <td></td>
                            <td>
                                <div class="input-group">
                                    {!! Form::select('s_photostatus', $s_photostatuses, $search_params['s_photostatus'] ?? '', ['class' => 'form-control']) !!}
                                </div>

                            </td>
                            <td>
                                <div class="input-group">
                                    {!! Form::select('s_itmtypeid', $itmtypes, $search_params['s_itmtypeid'] ?? ''
                                        , ['class' => 'form-control', 'placeholder'=>'-']) !!}
                                </div>
                            </td>
                            <td>
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                            {{--											formaction="{{ route('refitems.index') }}"--}}
                                            formmethod="post" title="Поиск">
                                        <i class="fa fa-search" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $bgcols = array('#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC', '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7');
                        //$rec0 = 0;

                        $bShowDescript = true;                        //todo - сделать преференцию

                        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

                        ?>
                        <style>
                            .photo {
                                /*display: block;*/
                                max-width: 80px;
                                max-height: 40px;
                                /*width: auto;*/
                                /*height: auto;*/
                            }
                        </style>

                        @if (count($recs)>0)
                            @php
                                $cur_itmtypeid = -1;
                            @endphp
                            @foreach($recs as $item)

                                @if($item->itmtypeid<>$cur_itmtypeid)
                                    <tr>
                                        <td colspan="7" style="background-color: #d1fff1">
                                            <h4>{{$item->it_name_path??'/- без категории -'}}</h4>
                                        </td>
                                    </tr>

                                    @php($cur_itmtypeid = $item->itmtypeid)
                                @endif

                                <?php
                                $colshift = (1 - $item->active) * 2;
                                $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];
                                ?>
                                <tr style="background-color: {{$tr_bg_col}}">
                                    <td scope="row" class="small text-right">
                                        {{$loop->index + $rec0}}
                                    </td>
                                    <td>
                                        <a name="{{$item->id}}"></a>

                                        <a href="{{route('refitems.edit',$item->id)}}" target="_self">{{$item->name}}</a>
                                        <div class="small ml-3">{{$item->code}}</div>
                                        @if($bShowDescript)
                                            <div class="small">
                                                {{$item->descript}}
                                                {{$item->specinfo}}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{$item->unittypename}}
                                    </td>
                                    <td class="text-right" nowrap="">
                                        {{($item->price)?number_format($item->price,2,'.',' '):'-'}}
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $photo = "";
                                        if ($item->photourl !== null) {
                                            $photo = '<img src="/' . $item->photourl . '" class="photo" style="max-width: 80px; max-height: 50px;">';
                                            echo $photo;
                                        }
                                        ?>
                                    </td>

                                    <td class="c">{{$item->itmtypename}}</td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('refitems.edit',$item->id)}}"
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

                    @includeIf('layouts.paginate_links')

                </div>
            </div>
        </form>

    </div>
    <span class="helptags" data="refitems.index"/>
@endsection

