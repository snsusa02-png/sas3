@if($rec->id != -1 and isset($rec->offers) )
    <?php
    $TotPaySum = 0;
    ?>
    <div class="card mt-3">
        <div class="card-header" style="background-color: #ffe0a0;">

            <a name="offers"></a>

            <span data-toggle="collapse" data-target="#childs">
				<i class="fa fa-truck" aria-hidden="true" style="color: darkorange"></i> Поставщики и поставки</span>

            <div class="float-right">
                @if (count($rec->offers)>0)
                    <button data-toggle="collapse" data-target="#offers"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
            </div>

        </div>
        @if (count($rec->offers)>0)
            <div class="card-body collapse show" id="offers">

                <table class="table-striped table-bordered0 p-1" style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-center">План. дата поставки</td>
                        <td class="text-left">Поставщик</td>
                        <td class="text-right">Цена, руб</td>
                        <td style="width:32px">
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $totOrdSum = 0;
                    $totOrdQty = 0;
                    ?>
                    @foreach($rec->offers as $itm)
                        <?php
                        $npp++;
                        $totOrdSum += $itm->ord_sum;
                        $totOrdQty += $itm->ord_qty;

                        $plngetdate = $itm->plngetdate;
                        if (isset($plngetdate))
                            $plngetdate = date_create($plngetdate)->format('d.m.Y');
                        else
                            $plngetdate = '-';
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-center small" style="">
                                <a href="{{ route('eritm_offers.edit',['id'=>$itm->id])}}?returl={{Request::url()}}">
                                    {{$plngetdate}}</a>
                            </td>
                            <td class="text-left small" style="">
                                <a href="{{route('orgs.edit',$itm->suporgid)}}" target="_blank">{{$itm->suporgname}}
                                    <i class="fa fa-external-link small" aria-hidden="true"></i></a>
                                @if(isset($itm->invoiceid))
                                    <div class="small">
                                        <a href="{{route('invoices.edit',$itm->invoiceid)}}"
                                           target="_blank">{{$itm->invoice_info}}
                                            <i class="fa fa-external-link small" aria-hidden="true"></i></a>
                                    </div>
                                @endif

                            </td>
                            {{--							<td class="text-right" style="">--}}
                            {{--								{{number_format($itm->ord_qty,3)}}--}}
                            {{--							</td>--}}
                            {{--							<td class="text-right" style="">--}}
                            {{--								{{number_format($itm->ord_sum,2)}}--}}
                            {{--							</td>--}}
                            <td class="text-right" style="">
                                @if($itm->ord_qty>0)
                                    {{number_format($itm->ord_sum / $itm->ord_qty,2)}}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('eritm_offers.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   class="btn btn-sm btn-light"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    @if($totOrdQty>0)
                        <tr>
                            <td colspan="3" class="text-right">Средняя цена:</td>
                            <td class="font-weight-bold text-right">{{number_format($totOrdSum/$totOrdQty,2)}}</td>
                            <td></td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        @endif

        @if (count($rec->offers)>0)
            <div class="card-footer">
                <div class="small text-right"> всего поставлено: {{number_format($totOrdQty,3)}}{{$rec->unittype->name}}
                    на сумму {{number_format($totOrdSum,2)}}</div>
            </div>
        @endif

    </div>
@endif
