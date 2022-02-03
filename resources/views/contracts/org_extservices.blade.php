@if($rec->id != -1 )
    <?php
    $TotRestSum = 0;
    ?>

    <div class="card mt-3">
        <div class="card-header" style="background-color: #d0ffcc;">
            <i class="fa fa-shopping-basket" aria-hidden="true"></i> Предоставляемые сервисы
            <div class="float-right">
                @if(count($rec->org_extservices)>0)
                    <button data-toggle="collapse" data-target="#org_extservices"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->org_extservices)}}</span>
                    </button>
                @endif
            </div>
        </div>
        @if (count($rec->org_extservices)>0)
            <div class="card-body collapse" id="org_extservices">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-center">Сервис</td>
                        <td class="text-right">Остаток на субсчете, руб</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    ?>
                    @foreach($rec->org_extservices as $itm)
                        <?php
                        $npp++;

                        $linestyle = "";
                        if ($itm->rest_sum == 0) {
                            $linestyle = "background-color:#fdd7c3;";
                        }
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-right small" style="">
                                <a href="{{ route('org_extservices.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   target="_self"
                                   title="Просмотреть/Изменить запись">{{$itm->name}}</a>
                            </td>
                            <td class="text-right" style="">
                                {{number_format($itm->rest_sum,2)}}
                            </td>
                        </tr>
                        <?php
                        $TotRestSum += $itm->rest_sum;
                        ?>
                    @endforeach
                    <tr>
                        <td colspan="2" class="text-right">Всего:</td>
                        <td class="text-right font-weight-bold">{{number_format($TotRestSum,2)}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
