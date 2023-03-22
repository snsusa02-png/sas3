@if ($rec->id != -1 and $usrrights['stf_charges.read']??false and isset($rec->charges) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-calculator text-info" aria-hidden="true"></i>
                    Применяемые начисления/удержания

                    <span class="float-right">
                        @if(count($rec->charges)>0)
                            <button data-toggle="collapse" data-target="#_charges"
                                    class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->charges)}}</span>
                        </button>
                        @endif
                        @if($usrrights['save']??true)
                            <a href="{{ route('stf_charges.create',['staffid'=>$rec->id])}}"
                               class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->charges)>0)
                    <div class="card-body collapse" id="_charges">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td class="small text-center">Вид</td>
                                <td class="small text-right">Сумма, &#8381;</tdc>
                                <td class="small text-center">Период</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rec->charges as $itm)
                                <?php
                                $linestyle = ($itm->active == 0) ? "background-color:#f2dddd;" :'';

                                if (isset($itm->enddate)) {
                                    $period = date_create($itm->begdate)->format('d.m.Y')
                                        . ' - ' . date_create($itm->enddate)->format('d.m.Y');
                                } else {
                                    $period = date_create($itm->begdate)->format('d.m.Y') . ' - ...';
                                    }
                                ?>
                                <tr style="{{$linestyle}}">
                                    <td class="small text-left">{{$itm->org_charge->chargetype->name}}</td>
                                    <td class="small text-right">
                                        {{number_format($itm->charge_sum,2)}}
                                    </td>
                                    <td class="small text-center">{{$period}}</td>
                                    <td class="text-right">
                                        <a href="{{ route('stf_charges.edit',$itm->id)}}"
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
