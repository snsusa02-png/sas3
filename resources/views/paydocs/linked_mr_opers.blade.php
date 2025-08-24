{{--@dd($rec->linked_mr_opers)--}}
@if($rec->id != -1 and $usrrights['paydocs.read']??true and isset($rec->linked_mr_opers))

    <div class="card mt-3">
        <div class="card-header" style="background-color: #ddffff;">
            <i class="fa fa-truck text-success" aria-hidden="true"></i> Связанные операции
            <div class="float-right">
                @if(count($rec->linked_mr_opers)>0)
                    <button data-toggle="collapse" data-target="#linked_mr_opers"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->linked_mr_opers)}}</span>
                    </button>
                @endif
                @if(false and $usrrights['paydocs.create']??false)
                    <a href="{{ route('paydocs.create')."?rsn_so_id=1107&rsn_o_id={$rec->id}&returl=".Request::url()}}"
                       class="btn btn-warning btn-sm" target=""
                       style="margin-left:16px;float: right;">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        @if (count($rec->linked_mr_opers)>0)
            <div class="card-body collapse" id="linked_mr_opers">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>Дата</td>
                        <td>Поставщик, заказчик, товар/услуга</td>
                        <td class="text-left">Сумма</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $totSum = 0;
                    ?>
                    @foreach($rec->linked_mr_opers as $itm)
                        <?php
    //dd($itm);
                        $npp++;
                        $linestyle = "";
                        ?>
                        <tr class="align-top ">
                            <td class="small text-center">{{date_create($itm->wrkdate)->format('d.m.Y')}}</td>
                            <td class="small text-center">
                                {{$itm->suporg_name}}<br>
                                @if($itm->sale_dir>0)
                                    <i class="fa fa-arrow-right text-info mx-1" aria-hidden="true"></i>
                                @else
                                    <i class="fa fa-arrow-left text-info mx-1" aria-hidden="true"></i>
                                @endif
                                {{$itm->org_name}}
                                <div class="float-right small">
                                    {{$itm->ri_name}}: {{$itm->itm_qty}}*{{$itm->itm_price}}
                                </div>
                            </td>
                            <td class="text-right small" style="{{$linestyle}}">
                                <a href="{{ route('mr_opers.edit',['id'=>$itm->lnkobjid])}}?returl={{Request::url()}}"
                                   title="Перейти к записи" target="">
                                    {{number_format($itm->sale_dir*$itm->itm_sum,2)}}
                                </a>
                            </td>
                        </tr>
                        <?php
                        $totSum += $itm->sale_dir*$itm->itm_sum;
                        ?>
                    @endforeach
                    <tr>
                        <td class="text-right" colspan="2">Итого:</td>
                        <td class="text-right font-weight-bold small">{{number_format($totSum,2)}}</td>
                    </tr>
                    <?php
                    $disSum = $rec->paysum - $totSum;
                    if ($disSum < 0) {
                        $lbl_disSum = 'Дисбаланс (переплата)';
                    } elseif ($disSum > 0) {
                        $lbl_disSum = 'Дисбаланс (недоплата)';
                    } else {
                        $lbl_disSum = 'Дисбаланс';
                    }
                    ?>
                    <tr>
                        <td class="text-right" colspan="2">{{$lbl_disSum}}:</td>
                        <td class="text-right font-weight-bold small">{{number_format($disSum,2)}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
