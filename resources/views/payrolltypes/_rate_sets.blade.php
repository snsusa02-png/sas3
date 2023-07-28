@if ($rec->id != -1 and $usrrights['read']??false and isset($rec->rate_sets) )

    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-list-ol text-info" aria-hidden="true"></i>
                    Группы ставок по периодам действия

                    <span class="float-right">
                        @if(count($rec->rate_sets)>0)
                            <button data-toggle="collapse" data-target="#_salaries"
                                    class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->rate_sets)}}</span>
                        </button>
                        @endif
                        @if($usrrights['save']??true)
                            <a href="{{ route('salary_rate_sets.create',['payrolltypeid'=>$rec->id])}}"
                               class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (isset($rec->rate_sets) and count($rec->rate_sets)>0)
                    <div class="card-body collapse show" id="_salaries">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td class="text-left">Период действия</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rec->rate_sets as $itm)
                                <?php
                                $tr_style = ($itm->active == 1) ? '' : 'background-color:#ffeeee;';
                                $period = date_create($itm->begdate)->format('d.m.Y') . ' - ';
                                if (isset($itm->enddate))
                                    $period .= date_create($itm->enddate)->format('d.m.Y');
                                else
                                    $period .= '...';
                                ?>
                                <tr style="{{$tr_style}}">
                                    <td class="small text-left">{{$period}}</td>
                                    <td class="text-right">
                                        <a href="{{ route('salary_rate_sets.edit',$itm->id)}}"
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
