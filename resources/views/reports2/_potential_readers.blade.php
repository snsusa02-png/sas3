@if(isset($rec->potential_readers) and count($rec->potential_readers)>0)
    <?php
    //статистика использования отчета пользователями
    ?>
    <div class="card mt-3 mb-3">
        <div class="card-header" style="background-color: #f2f2f2;">Доступен для пользователей</div>
        <div class="card-body" id="order_info">

            <table class="table table-sm table-striped" style="width: 100%;">
                <tr>
                    <td>#</td>
                    <td>Пользователь</td>
                </tr>
                <?php
                $npp = 1;
                ?>
                @foreach ($rec->potential_readers as $itm)
                    <tr class="small">
                        <td class="text-right small">{{$npp}}</td>
                        <td>{{$itm->lname}} {{$itm->fname}} {{$itm->mname}}</td>
                    </tr>
                    <?php
                    $npp++;
                    ?>
                @endforeach
            </table>

        </div>
    </div>

@endif

