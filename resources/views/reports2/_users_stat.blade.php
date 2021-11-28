@if(isset($rec->users_stat) and count($rec->users_stat)>0)
    <?php
    //статистика использования отчета пользователями
    ?>
    <div class="card mt-3 mb-3">
        <div class="card-header" style="background-color: #f2f2f2;">Статистика использования отчета</div>
        <div class="card-body" id="order_info">

            <table class="table table-sm table-striped" style="width: 100%;">
                <tr>
                    <td>#</td>
                    <td>Пользователь</td>
                    <td class="text-right">Кол-во запросов</td>
                    <td>Период</td>
                </tr>
                <?php
                $npp = 1;
                ?>
                @foreach ($rec->users_stat as $itm)
                    <tr class="small">
                        <td class="text-right small">{{$npp}}</td>
                        <td>{{$itm->user_name}}</td>
                        <td class="text-right">{{number_format($itm->cnt,0)}}</td>
                        <td class="text-center small">{{date_create($itm->min_dt)->format('d.m.Y H:i')}}
                            .. {{date_create($itm->max_dt)->format('d.m.Y H:i')}}</td>
                    </tr>
                    <?php
                    $npp++;
                    ?>
                @endforeach
            </table>

        </div>
    </div>

@endif

