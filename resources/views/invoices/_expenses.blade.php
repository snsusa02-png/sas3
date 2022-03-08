@if( $rec->id<>-1 and $rec->doctypeid==2 )

    <style>
        .current {
            background-color: #f0f0f0;
            color: gray;
        }
    </style>
    <div class="card mt-3 mb-3">
        <div class="card-header" style="background-color: #ebd2f3;">
            <i class="fa fa-money text-danger" aria-hidden="true"></i> Дополнительные затраты

            <span class="float-right">
            @if (1==0 or $usrrights['create'])
                    <a href="{{ route('equiprqst_expenses.create4upd',$rec->id)}}"
                       class="btn btn-warning btn-sm"
                       title="Добавить запись">
                    <i class="fa fa-plus"></i>
                </a>
                @endif
            </span>
        </div>
        <div class="card-body" id="">

            @if(count($rec->expenses)>0)
                <table class="table table-striped table-sm" style="width: 100%;">
                    <tr class="small">
                        <td>Дата опер.</td>
                        <td>Основание</td>
                        <td>Сумма, руб</td>
                        <td>
                        </td>
                    </tr>
                    <?php
                    $totDocsSum = 0;
                    $doctypes = [1 => 'счет', 2 => 'УПД'];
                    ?>
                    @foreach($rec->expenses as $itm)
                        <tr class="">
                            <td>
                                {{date_create($itm->operdate)->format('d.m.Y')}}
                            </td>
                            <td>{{$itm->reason}}
                                <div class="small ml-2">{{$itm->notes}}</div>
                                <a href="{{route('equiprqsts.edit',$itm->rqstid)}}" target="_blank" class="small">заявка № {{$itm->rqstid}}</a>
                            </td>
                            <td class="text-right">
                                {{number_format($itm->expense_sum,2)}}
                            </td>
                            <td>
                                <a href="{{route('equiprqst_expenses.edit4upd',$itm->id)}}" class="button btn-sm btn-light">
                                    <i class="fa fa-pencil" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                        @php($totDocsSum+=$itm->expense_sum)
                    @endforeach
                    <?php
                    ?>
                    <tr>
                        <td colspan="2"></td>
                        <td class="text-right font-weight-bold">{{number_format($totDocsSum,2)}}</td>
                        <td></td>
                    </tr>
                </table>
            @else
                - нет данных -
            @endif

        </div>
    </div>

@endif

