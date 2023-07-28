@if ($rec->id != -1 and $usrrights['stf_payrolltypes.read']??false and isset($rec->stf_payrolltypes) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-calculator text-info" aria-hidden="true"></i>
                    Способ расчета ЗП

                    <span class="float-right">
                        @if(count($rec->stf_payrolltypes)>0)
                            <button data-toggle="collapse" data-target="#_salaries"
                                    class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->stf_payrolltypes)}}</span>
                        </button>
                        @endif
                        @if($usrrights['save']??true)
                            <a href="{{ route('stf_payrolltypes.create',['staffid'=>$rec->id])}}"
                               class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->stf_payrolltypes)>0)
                    <div class="card-body collapse" id="_salaries">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td class="text-center">Период</td>
                                <td class="text-center">Схема</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rec->stf_payrolltypes as $itm)
                                <?php
                                $period = date_create($itm->begdate)->format('d.m.Y')
                                    . ' - ' . ((isset($itm->enddate)) ? date_create($itm->enddate)->format('d.m.Y') : '...');
                                ?>
                                <tr>
                                    <td class="small text-center">{{$period}}</td>
                                    <td class="text-left">
                                        {{$itm->payrolltype_name}}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('stf_payrolltypes.edit',$itm->id)}}"
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
