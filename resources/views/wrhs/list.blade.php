@extends('list_layout')

@section('content')
    <script src="{{ asset('js/handleListWrhs.js') }}" defer></script>
    @guest
        <?php
        redirect(route('login'));
        ?>
    @else

        <style>
            .list{
                background-color: #FFFFFF;
                cursor:pointer;
            }
            .list:hover {
                background-color: #FFFF99;
            }
        </style>
        <form name="forIndex" id="forIndex"  method="post" action="{{ route('wrhs.list') }}">
        @csrf
        <div class="container">
            <div><br></div>
            <div class="row justify-content-center">
                <div class="col-md-12">

                    <table class="table table-condensed small tbl_list" style="background-color: white;">
                        <thead>
                        <tr>
                            <td colspan="5">
                                <b>Выбор склада</b>
                            </td>
                        </tr>
                        <tr class>
                            <th scope="col">#</th>
                            <th scope="col">Название, описание</th>
                            <th scope="col">Адрес</th>
                        </tr>
                        <tr style="text-align: center;">
                            <th/>
                            <th>
                                <div class="input-group">
                                    <input type="text" class="form-control c" name="s_name"
                                           value="{{$s_name ?? ''}}" />
                                </div>
                            </th>
                            <th>
                                <div class="input-group">
                                    <input type="text" class="form-control c" name="s_address"
                                           value="{{$s_address ?? ''}}" />
                                </div>
                            </th>
                            <th>
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-sm btn-info"
                                            formaction="{{ route('wrhs.list') }}"
                                            formmethod="post" title="Поиск">
                                        <i class="fa fa-search" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </th>
                        </tr>
                        </thead>

                        <tbody>
                        @if (count($items)>0)
                            @foreach($items as $item)
                                <tr onclick="hWRH({{$item->id}})" class="list">
                                    <td scope="row" class="small text-right">{{$loop->index+1}}</td>
                                    <td>{{$item->name}}</a>
                                        <div class="small"> {{$item->descript}}</div>
                                    </td>
                                    <td class="text-center">{{$item->address}}</td>
                                </tr>
                            @endforeach
                        @else
                            <?php
                            if (Route::currentRouteName() == "whrs.list") {
                                $msg="Данные не найдены. \nПопробуйте изменить критерий поиска и повторить.";
                            }
                            else{
                                $msg="Нет записей.";
                            }
                            ?>
                            <tr><td colspan="6" class="c">{{$msg}}</td></tr>
                        @endif
                        </tbody>
                    </table>
                    <div >
                        {{$items->appends(['s_name'=>$s_name
                                                          ,'s_address'=>$s_address
                                                          ])->links()}}
                    </div>
                </div>
            </div>
        </div>
        </form>
    @endguest
@endsection

