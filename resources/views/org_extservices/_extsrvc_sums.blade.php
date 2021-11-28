@if($rec->id != -1 and isset($rec->extsrvc_sums) )
    <?php
    $TotPaySum = 0;
    ?>
    <div class="card mt-3">
        <div class="card-header" style="background-color: #fff1db;">

            <a name="extsrvc_sums"></a>

            <span data-toggle="collapse" data-target="#childs">
				<i class="fa fa-history text-info" aria-hidden="true"></i> История изменений</span>

            <div class="float-right">
                @if (count($rec->extsrvc_sums)>0)
                    <button data-toggle="collapse" data-target="#extsrvc_sums"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if( $usrrights['extsrvc_sums.create']??false)
                    <a href="{{ route('extsrvc_sums.create',['srvcid'=>$rec->id])}}&returl={{Request::url()}}"
                       class="btn btn-warning btn-sm ">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>

        </div>
        @if (count($rec->extsrvc_sums)>0)
            <div class="card-body collapse show" id="extsrvc_sums">

                <table class="table-striped table-bordered0 p-1" style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-left">Дата</td>
                        <td class="text-right">Остаток, руб</td>
                        <td class="text-right">Изменение, руб</td>
                        <td class="text-right">За день, руб</td>
                        <td style="width:32px">
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    ?>
                    @foreach($rec->extsrvc_sums as $itm)
                        <?php
                        $npp++;
                        $deltasum = 0;
                        $sumdif_class = ($itm->sum_diff >= 0) ? 'text-success' : 'text-danger';
                        if (isset($itm->pre_sum))
                            $deltasum = $itm->restsum - $itm->pre_sum;

                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-left small" style="">
                                {{date_create($itm->ondate)->format('d.m.Y')}}
                            </td>
                            <td class="text-right " style="">
                                {{$itm->restsum}}
                            </td>
                            <td class="text-right small {{$sumdif_class}}" style="">
                                {{$itm->sum_diff}}
                            </td>
                            <td class="text-right small" style="">
                                {{$itm->day_sum_diff}}
                            </td>

                            <td class="text-right">
                                <a href="{{ route('extsrvc_sums.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   class="btn btn-sm btn-light"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (1==1 and count($rec->extsrvc_sums)>0)
            <div class="card-footer text-right">
                Остаток до блокировки, руб: <b>{{number_format($rec->rest_sum - $rec->lock_limsum??0,0)}}</b>
                @if(isset($rec->avg_sum_dif))
                    <br>Средний расход за рабочий день, руб: <b>{{number_format($rec->avg_sum_dif,0)}}</b>
                    <br>Оценка до блокировки, дней:
                    <b>{{number_format(($rec->rest_sum - $rec->lock_limsum)/-$rec->avg_sum_dif,1)}}</b>
                @endif
            </div>
            <a href="{{ route('org_extservices.upddaysums',['srvcid'=>$rec->id])}}?returl={{Request::url()}}"
               class="btn btn-warning btn-sm ">
                <i class="fa fa-calculator"></i>
            </a>
        @endif

    </div>
@endif
