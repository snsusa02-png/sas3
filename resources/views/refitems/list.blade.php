@extends('layouts.list_layout')

@section('content')
    <script src="{{ asset('js/handleListRefItems.js') }}" defer></script>
    @guest
        <?php
        redirect(route('login'));
        ?>
    @else

        <style>
            .list {
                background-color: #FFFFFF;
                cursor: pointer;
            }

            .list:hover {
                background-color: #FFFF99;
            }
        </style>
        <link rel="stylesheet" href="/css/itmtypes_tree.css">

        <form name="forIndex" id="forIndex" method="post" action="{{ route('refitems.list') }}">
            @csrf
            <input type="hidden" id="orgid" name="orgid" value={{$orgid}} />

            <div class="container-fluid">
                <div><br></div>
                <div class="row justify-content-center">
                    <div class="col-md-12">

                        <table class="table table-condensed  tbl_list" style="background-color: white;">
                            <thead>
                            <tr>
                                <td colspan="5">
                                    <b>Выбор товара</b>
                                </td>
                            </tr>
                            <tr class="small">
                                <th scope="col">#</th>
                                <th scope="col">Категория</th>
                                <th scope="col">Название, ЕИ</th>
                                <th scope="col" class="text-right">Цена, руб</th>
                            </tr>
                            <tr style="text-align: center;">
                                <td colspan="2">
                                    {{--                                    <div class="input-group">--}}
                                    {{--                                        {!! Form::select('s_itmtypeid',--}}
                                    {{--                                         $itmtypes,--}}
                                    {{--                                         $s_itmtypeid ?? '',--}}
                                    {{--                                        ['class' => 'form-control small',--}}
                                    {{--                                        'placeholder'=>'-все-',--}}
                                    {{--                                        'onchange'=>'form.submit();'--}}
                                    {{--                                        ]) !!}--}}
                                    {{--                                    </div>--}}

                                    <div class="form-group text-left">
                                        <label for="itmtypeid">Категория:</label>
                                        @if(1==0)
                                            {!! Form::select('s_itmtypeid', $itmtypes_tree??[],
                                            $s_itmtypeid,
                                            [
                                                'class' => 'form-control',
                                            'placeholder'=>'-']) !!}
                                        @endif

                                        <div class="input-group">
                                            <input type="hidden" name="s_itmtypeid" id="itmtypeid"
                                                   value="{{ $s_itmtypeid}}">
                                            <input type="text" id="itmtypename" readonly
                                                   value="{{$data->s_itmtype_name??''}}"
                                                   class="form-control font-weight-bold"
                                                   style="background-color: snow"
                                                   data-toggle="collapse"
                                                   data-target="#categories"
                                            >
                                            <button type="button" class="btn btn-info" id="btn_itmtype_tree"
                                                    data-toggle="collapse"
                                                    data-target="#categories"><i class="fa fa-caret-down"
                                                                                 aria-hidden="true"></i>
                                            </button>
                                        </div>
                                        <ul class="collapse border" style="cursor: pointer" id="categories">
                                            @foreach($data->categories as $category)
                                                <li>
                                                        <span class="itmtypeid "
                                                              data-id="{{$category->id}}">{{$category->name}}</span>

                                                    @if($category->sub->count())
                                                        <input type="checkbox" id="chk{{$category->id}}"><label
                                                            for="chk{{$category->id}}"></label>

                                                        @include('itmtypes.sub_tree', ['categories' => $category->sub])
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </td>
                                <th>
                                    <div class="form-group">
                                        <input type="text" class="form-control c" name="search_name"
                                               value="{{$search_name ?? ''}}"/>
                                    </div>
                                </th>
                                <th class="text-right">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                formaction="{{ route('refitems.list') }}"
                                                formmethod="post" title="Поиск">
                                            <i class="fa fa-search" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </th>
                            </tr>
                            </thead>

                            <tbody>
                            @if (count($items)>0)
                                <?php
                                $curItmTypeId = -1;
                                ?>
                                @foreach($items as $item)
                                    @if($item->itmtypeid <> $curItmTypeId)
                                        <tr>
                                            <td colspan="4" style="background-color: #e3fff4">
                                                <b>{{$item->itmtypename}}</b>
                                                <div class="small">{{$item->itmsubtypename}}</div>
                                            </td>
                                        </tr>
                                        <?php
                                        $curItmTypeId = $item->itmtypeid;
                                        ?>
                                    @endif
                                    <tr onclick="hLRI({{$item->id}})" class="list">
                                        <td scope="row" class="small text-right">{{$loop->index+1}}</td>
                                        <td colspan="2">{{ $item->name }}, <b>{{$item->unit}}</b>
                                            <div class="small"> {{$item->partnumber}}</div>
                                            <div class="small"> {{$item->descript}}</div>
                                        </td>
                                        <td class="text-right">{{ number_format($item->price, 2) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <?php
                                if (Route::currentRouteName() == "testmodal.search") {
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
                        <div>
                            {{$items->appends(['orgid'=>$orgid
                                                              , 'search_name'=>$search_name
                                                              ,'s_itmtypeid'=>$s_itmtypeid
                                                              ,'search_brand'=>$search_brand
                                                              ])->links()}}
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <script src="{{ asset('js/refitems_list.js') }}" defer></script>
    @endguest
@endsection

