@if ($rec->id != -1 and in_array($rec->kindid,[1,2])  and $usrrights['org_acnts.read']??false)
    <div class="card mt-3">
        <div class="card-header">
            Р/счета организации

            <div class="float-right">
                @if (isset($rec->acnts) and count($rec->acnts)>0)
                    <button data-toggle="collapse" data-target="#orgacnts"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if($usrrights['org_acnts.create']??false)
                    <a href="{{ route('org_acnts.create',$rec->id)}}"
                       class="btn btn-sm btn-warning"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body collapse" id="orgacnts">

            @if(count($rec->acnts)>0)
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>Банк</td>
                        <td>Р/счет</td>
                        <td style="text-align: center;">
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $placetype = [1 => 'офис', 2 => 'склад'];
                    ?>
                    @foreach($rec->acnts as $itm)
                        <?php
                        $tr_itm_class = "itm_not_active";
                        if ($itm->active == 1) {
                            $tr_itm_class = "itm_active";
                        }
                        ?>
                        <tr class="{{$tr_itm_class}}">
                            <td class="small" style="text-align: right'">
                                {{--$local->count--}}
                            </td>
                            <td>{{$itm->bankname}}
                                <div class="small ml-2">БИК: {{$itm->bic}}
                                <br>к/сч: {{$itm->cs_num}}</div>
                            </td>
                            <td class="small">
                                {{$itm->rs_num}}
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('org_acnts.edit',$itm->id)}}"
                                   class="btn btn-sm btn-primary"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endif

