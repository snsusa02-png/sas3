@if ($rec->id != -1 and $usrrights['stf_chrg_calcs.read']??false and isset($rec->chrg_calcs) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-money text-danger" aria-hidden="true"></i>
                    Фактические начисления/удержания
                    <span class="float-right">
                        @if(count($rec->chrg_calcs)>0)
                            <button data-toggle="collapse" data-target="#_chrg_calcs"
                                    class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->chrg_calcs)}}</span>
                        </button>
                        @endif
                        @if($usrrights['save']??true)
                            <a href="{{ route('stf_chrg_calcs.create',['staffid'=>$rec->id])}}"
                               class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->chrg_calcs)>0)
                    <div class="card-body collapse" id="_chrg_calcs">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td class="small text-center">Дата</td>
                                <td class="small text-center">Вид</td>
                                <td class="small text-right">Сумма, &#8381;</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rec->chrg_calcs as $itm)
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
                                    <td class="small text-center">{{date_create($itm->docdate)->format('d.m.Y')}}</td>
                                    <td class="small text-left">{{$itm->org_charge->chargetype->name}}</td>
                                    <td class="small text-right">
                                        {{number_format($itm->charge_sum,2)}}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('stf_chrg_calcs.edit',$itm->id)}}"
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
