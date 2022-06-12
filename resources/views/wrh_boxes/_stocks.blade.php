{{--@dd(isset($rec->stocks), $rec->id)--}}
@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->stocks))

    <div class="card mt-3">
        <div class="card-header" style="background-color: #f2ffca;">
            <i class="fa fa-th text-success" aria-hidden="true"></i>
            Товарный запас
            <div class="float-right">
                @if(count($rec->stocks)>0)
                    <button data-toggle="collapse" data-target="#stocks"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->stocks)}}</span>
                    </button>
                @endif
                @if(1==0)
                    <a href="{{ route('wrh_stocks.create',['wrhid'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        @if (count($rec->stocks)>0)
            <div class="card-body collapse1" id="stocks">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-left">Наименование материала/оборудования</td>
                        <td class="text-right">Кол-во</td>
                        <td class="text-center">ЕИ</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    ?>
                    @foreach($rec->stocks as $itm)
                        <?php
                        $npp++;

                        $linestyle = "";
                        if ($itm->active == 0) {
                            //$linestyle = "background-color:lightsalmon;";
                            $linestyle = "";
                        }
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-left small" style="{{$linestyle}}">
                                {{$itm->ri_name}}
                                <div class="small ml-3">{{$itm->ri_descript}}</div>
                            </td>
                            <td class="text-right" style="{{$linestyle}}">
                                {{$itm->qty}}
                            </td>
                            <td class="text-center small">
                                {{$itm->unit}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
