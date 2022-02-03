@if($rec->id != -1 )
    <?php
    $TotFctPaySum = 0;
    ?>

    <div class="card mt-3">
        <div class="card-header" style="background-color: #ddffef;">
            <i class="fa fa-money" aria-hidden="true"></i> Платежи контрагенту
            <div class="float-right">
                @if(count($rec->pays)>0)
                    <button data-toggle="collapse" data-target="#pays"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->pays)}}</span>
                    </button>
                @endif
            </div>
        </div>
        @if (count($rec->pays)>0)
            <div class="card-body collapse" id="pays">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-center">Основание</td>
                        <td class="text-right">План. сумма, руб</td>
                        <td class="text-right">Факт. сумма, руб</td>
                        <td class="text-center">Когда</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    ?>
                    @foreach($rec->pays as $itm)
                        <?php
                        $npp++;

                        $linestyle = "";
                        if ($itm->fctpaysum == 0) {
                            $linestyle = "background-color:#fdd7c3;";
                        }
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-right small" style="">
                                <a href="{{ route('orgplnpay_items.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   target="_blank"
                                   title="Просмотреть/Изменить запись">{{$itm->reason}}</a>
                            </td>
                            <td class="text-right" style="">
                                {{number_format($itm->plnpaysum,2)}}
                            </td>
                            <td class="text-right" style="{{$linestyle}}">
                                {{number_format($itm->fctpaysum,2)}}
                            </td>
                            <td class="text-center small" style="{{$linestyle}}">

                                @if(isset($itm->fctpay_at))
                                    {{date_create($itm->fctpay_at)->format('d.m.Y')}}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <?php
                        $TotFctPaySum += $itm->fctpaysum;
                        ?>
                    @endforeach
                    <tr>
                        <td colspan="3" class="text-right">Всего:</td>
                        <td class="text-right font-weight-bold">{{number_format($TotFctPaySum,2)}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
