@if ($rec->id != -1 and $usrrights['stf_prl_periods.read']??false and isset($rec->prl_periods) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-calendar text-success" aria-hidden="true"></i>
                    Периоды начисления/удержания
                    <span class="float-right">
                        @if(count($rec->prl_periods)>0)
                            <button data-toggle="collapse" data-target="#_prl_periods"
                                    class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->prl_periods)}}</span>
                        </button>
                        @endif
                        @if($usrrights['save']??true)
                            <a href="{{ route('stf_prl_periods.create',['staffid'=>$rec->id])}}"
                               class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->prl_periods)>0)
                    <div class="card-body collapse" id="_prl_periods">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td class="small text-center">Начало</td>
                                <td class="small text-center">Окончание</td>
                                <td class="small text-center">Примечание</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rec->prl_periods as $itm)
                                <?php
                                $linestyle = ($itm->active == 0) ? "background-color:#f2dddd;" :'';

//                                if (isset($itm->forenddate)) {
//                                    $period = date_create($itm->forbegdate)->format('d.m.Y')
//                                        . ' - ' . date_create($itm->forenddate)->format('d.m.Y');
//                                } else {
//                                    $period = date_create($itm->forbegdate)->format('d.m.Y') . ' - ...';
//                                    }
                                ?>
                                <tr style="{{$linestyle}}">
                                    {{--                                    <td class="small text-center">{{$period}}</td>--}}
                                    <td class="small text-center">{{date_create($itm->begdate)->format('d.m.Y')}}</td>
                                    <td class="small text-center">{{date_create($itm->enddate)->format('d.m.Y')}}</td>
                                    <td class="small text-left">{{$itm->notes}}</td>
                                    <td class="text-right">
                                        <a href="{{ route('stf_prl_periods.edit',$itm->id)}}"
                                           class="btn btn-sm btn-primary">
                                            <i class="fa fa-pencil">
                                            </i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
