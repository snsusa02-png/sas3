@extends('layouts.app')

@section('content')
    @guest
        <?php
        redirect(route('login'));
        ?>
    @else

        <?php
        $thisTitle = "Места/Локации/Адреса";
        $userid = \Auth::user()->id;
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1
        ?>
        <link rel="stylesheet" href="/css/subnav.css">

        <form name="forIndex" id="forIndex" method="post" action="{{ route('places.index') }}">
            @csrf

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

                <div class="row justify-content-center">
                    <div class="col-md-9">
                        <h4>{{$thisTitle}}</h4>

                        <div class="row mb-2">
                            <div class="col-md-12">
                                <div class="subnav shift text-right">
                                    <ul>
                                        @if(1==1 and \Illuminate\Support\Facades\Route::has('orgs.index'))
                                            <li><a href="{{route('orgs.index')}}"
                                                   title="Справочник контрагентов">Контрагенты</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Название, описание</th>
                                    <th scope="col">Особенности</th>
                                    <th scope="col">Адрес</th>
                                    <td>
                                        @if ($usrrights['create'])
                                            <a href="{{ route('places.create')}}"
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
                                            <input type="text" class="form-control c" name="s_name"
                                                   value="{{$s_name ?? ''}}"/>
                                        </div>
                                    </td>
                                    <td>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" class="form-control c" name="s_address"
                                                   value="{{$s_address ?? ''}}"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group-btn">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                    formaction="{{ route('places.index') }}"
                                                    formmethod="post" title="Поиск">
                                                <i class="fa fa-search" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                ?>
                                @if (count($recs)>0)
                                    <?php
                                    $bgcols = array('#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC', '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7');
                                    $rec0 = 0;
                                    ?>
                                    @foreach($recs as $rec)
                                        <?php
                                        $colshift = (1 - $rec->active) * 2;
                                        $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];

                                        $spec = "";
                                        $spec .= ($rec->forsale) ? ' отпуск товара;' : '';

                                        ?>
                                        <tr style="background-color: {{$tr_bg_col}}">
                                            <td scope="row" class="small text-right">{{$loop->index+1}}</td>
                                            <td><a href="{{route('places.edit',$rec->id)}}"
                                                   target="_self">{{$rec->name}}</a>
                                                <div class="small"> {{$rec->descript}}</div>
                                            </td>
                                            <td class="text-center">{{$spec}}</td>
                                            <td class="c">{{$rec->address}}</td>
                                            <td style="text-align: center;">
                                                <a href="{{ route('places.edit',$rec->id)}}"
                                                   class="btn btn-sm btn-primary"
                                                   title="Просмотреть/Изменить запись">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <?php
                                    if (Route::currentRouteName() == "places.search") {
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
                        </div>
                        <div>
                            @if (Route::currentRouteName() == "places.search")
                                {{$recs->appends(['s_name'=>$s_name
                                        ,'s_address'=>$s_address])->links()}}
                            @else
                                {{$recs->links()}}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endguest
@endsection

