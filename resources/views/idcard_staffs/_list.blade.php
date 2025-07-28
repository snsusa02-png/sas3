{{--@if ($rec->id != -1 and $usrrights['idcard_staffs.read']??false and isset($rec->idcard_staffs) )--}}
{{--@dd($rec->idcard_staffs)--}}
@if ($rec->id != -1 and isset($rec->idcard_staffs) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-users text-info" aria-hidden="true"></i>
                    Сотрудники - держатели карты
                    <span class="float-right">
                        @if(count($rec->idcard_staffs)>0)
                            <button data-toggle="collapse" data-target="#_salaries"
                                    class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->idcard_staffs)}}</span>
                        </button>
                        @endif
                        @if($usrrights['save']??true)
                            <a href="{{ route('idcard_staffs.create',$rec->id)}}"
                               class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->idcard_staffs)>0)
                    <div class="card-body collapse show" id="_salaries">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td class="text-center">Период</td>
                                <td class="text-center">Сотрудник</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rec->idcard_staffs as $itm)
                                <?php
                                $period = date_create($itm->begdate)->format('d.m.Y')
                                    . ' - ' . date_create($itm->enddate)->format('d.m.Y');
                                ?>
                                <tr>
                                    <td class="small text-center">{{$period}}</td>
                                    <td class="text-left">
                                        {{$itm->stfname}}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('idcard_staffs.edit',['id'=>$itm->id,'cardid'=>$rec->id])}}"
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
