@if($rec->id != -1 )
    <?php
    $TotFctpaydocsum = 0;
    ?>

    <div class="card mt-3">
        <div class="card-header" style="background-color: #ddffef;">
            <i class="fa fa-money" aria-hidden="true"></i> Платежи по договору
            <div class="float-right">
                @if(count($rec->paydocs)>0)
                    <button data-toggle="collapse" data-target="#paydocs"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->paydocs)}}</span>
                    </button>
                @endif
            </div>
        </div>
        @if (count($rec->paydocs)>0)
            <div class="card-body collapse" id="paydocs">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-center">Когда</td>
                        <td class="text-center">Основание</td>
                        <td class="text-right">сумма, руб</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    ?>
                    @foreach($rec->paydocs as $itm)
                        <?php
                        $npp++;

                        $linestyle = "";
                        if ($itm->fctpaydocsum == 0) {
                            $linestyle = "background-color:#fdd7c3;";
                        }
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-center small" style="">
                                {{date_create($itm->paydate)->format('d.m.Y')}}
                            </td>
                            <td class="text-right small" style="">
                                <?php
                                $reason = $itm->reason;
                                if (isset($itm->docnum)) {
                                    $reason .= ' №' . $itm->docnum;
                                    if (isset($itm->docdate)) {
                                        $reason .= ' от ' . date_create($itm->docdate)->format('d.m.Y');
                                    }
                                }
                                ?>

                                <a href="{{ route('paydocs.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   target="_blank"
                                   title="Просмотреть/Изменить запись">{{$reason}}</a>
                            </td>
                            <td class="text-right" style="">
                                {{number_format($itm->paydir * $itm->paysum,2)}}
                            </td>
                        </tr>
                        <?php
                        $TotFctpaydocsum += $itm->paydir * $itm->paysum;
                        ?>
                    @endforeach
                    <tr>
                        <td colspan="3" class="text-right">Всего:</td>
                        <td class="text-right font-weight-bold">{{number_format($TotFctpaydocsum,2)}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
