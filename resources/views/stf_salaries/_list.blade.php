@if ($rec->id != -1 and $usrrights['stf_salaries.read']??false and isset($rec->salaries) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-money text-danger" aria-hidden="true"></i>
                    Заработная плата (оклад)

                    <span class="float-right">
                        @if(count($rec->salaries)>0)
                            <button data-toggle="collapse" data-target="#_salaries"
                                    class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->salaries)}}</span>
                        </button>
                        @endif
                        @if($usrrights['save']??true)
                            <a href="{{ route('stf_salaries.create',['staffid'=>$rec->id])}}"
                               class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->salaries)>0)
                    <div class="card-body collapse" id="_salaries">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td class="text-center">Период</td>
                                <td class="text-center">Сумма, &#8381;</tdc>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rec->salaries as $itm)
                                <?php
                                $period = date_create($itm->wrkbegdate)->format('d')
                                    . ' - ' . date_create($itm->wrkenddate)->format('d.m.Y');
                                ?>
                                <tr>
                                    <td class="small text-center">{{$period}}</td>
                                    <td class="text-right">
                                        {{number_format($itm->salary_sum,2)}}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('stf_salaries.edit',$itm->id)}}"
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
