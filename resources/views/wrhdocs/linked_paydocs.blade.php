{{--@dd($rec->linked_paydocs)--}}
@if($rec->id != -1 and $usrrights['paydocs.read'] and isset($rec->linked_paydocs))

    <div class="card mt-3">
        <div class="card-header" style="background-color: #ddffff;">
            <i class="fa fa-money text-success" aria-hidden="true"></i> Связанные платежи
            <div class="float-right">
                @if(count($rec->linked_paydocs)>0)
                    <button data-toggle="collapse" data-target="#linked_paydocs"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->linked_paydocs)}}</span>
                    </button>
                @endif
                @if($usrrights['paydocs.create']??true)
                    <a href="{{ route('paydocs.create')."?rsn_so_id=204&rsn_o_id={$rec->id}&returl=".Request::url()}}"
                       class="btn btn-warning btn-sm" target=""
                       style="margin-left:16px;float: right;">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        @if (count($rec->linked_paydocs)>0)
            <div class="card-body collapse" id="linked_paydocs">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>Дата</td>
                        <td>Контрагенты</td>
                        <td class="text-left">Сумма</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $totSum = 0;
                    ?>
                    @foreach($rec->linked_paydocs as $itm)
                        <?php
                        $npp++;
                        $linestyle = "";
                        ?>
                        <tr class="align-top ">
                            <td class="small text-center">{{date_create($itm->paydate)->format('d.m.Y')}}</td>
                            <td class="small text-center">
                                {{$itm->ownorg_name}}
                                @if($itm->paydir<0)
                                    <i class="fa fa-arrow-right text-danger mx-1" aria-hidden="true"></i>
                                @else
                                    <i class="fa fa-arrow-left text-success mx-1" aria-hidden="true"></i>
                                @endif
                                {{$itm->org_name}}
                            </td>
                            <td class="text-right small" style="{{$linestyle}}">
                                <a href="{{ route('paydocs.edit',['id'=>$itm->lnkobjid])}}?returl={{Request::url()}}"
                                   title="Перейти к записи" target="">
                                    {{number_format($itm->paydir*$itm->paysum,2)}}
                                </a>
                            </td>
                        </tr>
                        <?php
                        $totSum += $itm->paydir * $itm->paysum;
                        ?>
                    @endforeach
                    <tr>
                        <td class="text-right" colspan="2">Итого:</td>
                        <td class="text-right font-weight-bold small">{{number_format($totSum,2)}}</td>
                    </tr>
                    <?php
                    $disSum = $rec->docsum - $totSum;
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
