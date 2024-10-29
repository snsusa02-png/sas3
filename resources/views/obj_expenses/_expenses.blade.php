@if ($rec->id != -1 and isset($rec->expenses) )
    <div class="row">

        <div class="col-md-6">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-money text-danger" aria-hidden="true"></i>
                    Дополнительные затраты

                    <span class="float-right">
                        <button data-toggle="collapse" data-target="#_expenses"
                                class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->expenses)}}</span>
                        </button>
                        @if( $usrrights['obj_expenses.create'])
                            <a href="{{ route('obj_expenses.create',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,])}}?returl={{Request::url()}}"
                               class="btn btn-warning btn-sm"
                               title="добавить позицию">
                                            <i class="fa fa-plus"></i>
                                        </a>
                        @endif
                        @if(isset($sysobjid) and $rec->id<>-1 and $usrrights['expenses_refresh']??false)
                            <a class="btn btn-sm btn-warning"
                               href="{{ route('obj_expenses.refresh_for_obj',['sysobjid'=>$sysobjid,'objid'=>$rec->id]) }}"
                               title="Обновить транзакции">
                                   <i class="fa fa-refresh" aria-hidden="true"></i>
                                </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->expenses)>0)
                    <div class="card-body collapse1" id="_expenses">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr class="">
                                <td>Уч. дата</td>
                                <td class="text-left">Вид, описание затрат
                                    <div class="text-secondary float-right">Вид деятельности</div>
                                </td>
                                <td class="text-right">Сумма, &#8381;</td>
                                <td>
                                </td>
                            </tr>
                            </thead>
                            <tbody>
                            @php($totSum = 0.00)
                            @foreach($rec->expenses as $itm)
                                <?php
                                $sum_style = '';
                                if ($itm->active <> 1)
                                    $sum_style = 'background-color:#fef1db;';
                                ?>
                                <tr>
                                    <td style="text-align: center;"
                                        class="small">{{date_create($itm->operdate)->format('d.m.Y')}}</td>

                                    <td class="small text-left">{{$itm->expensetype_name}}: {{$itm->reason}}
                                        <div class="float-right text-secondary"> {{$itm->opertype_name}}</div>
                                    </td>
                                    <td class="text-right" style="{{$sum_style}}">
                                        <span class="small text-secondary"> {{number_format($itm->qty,3)}} * {{number_format($itm->price,2)}} = </span>
                                        {{number_format($itm->expense_sum,2)}}</td>
                                    <td>@if ($usrrights['obj_expenses.update'])
                                            <a href="{{ route('obj_expenses.edit',$itm->id)}}?returl={{Request::url()}}"
                                               class="btn btn-sm btn-primary">
                                                <i class="fa fa-pencil">
                                                </i>
                                            </a>
                                        @endif</td>
                                </tr>
                                <?php
                                $totSum += $itm->active * $itm->expense_sum;
                                ?>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="2" class="text-right">Всего:</td>
                                <td class="float-right text-right">{{number_format($totSum,2)}}</td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        @if(isset($rec->tot_expense_sum) and $rec->tot_expense_sum <> 0)
            <div class="offset-md-3 col-md-3">
                <div class="card mt-3">
                    <div class="card-header">
                        <i class="fa fa-money text-danger" aria-hidden="true"></i>
                        Всего затрачено
                    </div>
                    <div class="card-body" id="_tot_expense_sum">
                        <div class="text-center h3">{{number_format($rec->tot_expense_sum, 2)}}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
