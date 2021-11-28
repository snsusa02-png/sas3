@if($rec->id != -1 and isset($rec->mot_prices) )
    <style>
        .current {
            background-color: #e9faca !important;
        }
    </style>
    <?php
    $TotPaySum = 0;
    ?>
    <div class="card mt-3">
        <div class="card-header" style="background-color: #ffeaa9;">

            <a name="mot_prices"></a>

            <span data-toggle="collapse" data-target="#childs">
				<i class="fa fa-money text-danger" aria-hidden="true"></i> Расценки</span>

            <div class="float-right">
                @if (count($rec->mot_prices)>0)
                    <button data-toggle="collapse" data-target="#mot_prices"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if( $usrrights['mot_prices.create']??false)
                    <a href="{{ route('mot_prices.create',['mot_id'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-warning btn-sm ">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>

        </div>
        @if (count($rec->mot_prices)>0)
            <div class="card-body collapse show" id="mot_prices">

                <table class="table-striped table-bordered0 p-1" style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-left">Период</td>
                        <td class="text-right">Работа в час, &#8381;</td>
                        <td class="text-right">Топливо в час, &#8381;</td>
                        <td style="width:32px">
                    </tr>
                    </thead>
                    <tbody>
                    @php
                        $npp = 0;
                        $totPlnQty = 0;
                        $totDoneQty = 0;
                        $totRestQty = 0;
                    @endphp
                    @foreach($rec->mot_prices as $itm)
                        @php
                            $npp++;
                            $tr_class = ($itm->cur_price == 1) ? 'current' : '';

                        @endphp
                        <tr class="align-top {{$tr_class}}">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-left small" style="">
                                {{date_create($itm->begdt)->format('d.m.Y')}}
                                -
                                <span
                                    class="small">{{(isset($itm->enddt))?date_create($itm->enddt)->format('d.m.Y'):''}}</span>
                            </td>
                            <td class="text-right " style="">
                                {{$itm->hour_work_cost}}
                            </td>
                            <td class="text-right small" style="">
                                {{$itm->hour_fuel_cost}}
                            </td>

                            <td class="text-right">
                                <a href="{{ route('mot_prices.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   class="btn btn-sm btn-light"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    {{--                    <tr class="text-right">--}}
                    {{--                        <td colspan="2">Всего:</td>--}}
                    {{--                        <td><b>{{number_format($totPlnQty,3)}}</b></td>--}}
                    {{--                        <td class="small"><b>{{number_format($totDoneQty,3)}}</b></td>--}}
                    {{--                        <td class="small"><b>{{number_format($totRestQty,3)}}</b></td>--}}
                    {{--                    </tr>--}}
                    </tbody>
                </table>
            </div>
        @endif

        @if (1==1 and count($rec->mot_prices)>0)
            <div class="card-footer text-right">

            </div>
        @endif

    </div>
@endif
