@if( $rec->id<>-1 and isset($rec->child_docs) and $rec->doctypeid==1 )

    <style>
        .current {
            background-color: #f0f0f0;
            color: gray;
        }
    </style>
    <div class="card mt-3 mb-3">
        <div class="card-header" style="background-color: #c2f2f0;">
            Связанные УПД/СФ

            <span class="float-right">
            @if (1==1 or $usrrights['create'])
                    <a href="{{ route('invoices.create',$rec->id)}}"
                       class="btn btn-warning btn-sm"
                       title="Добавить запись">
                    <i class="fa fa-plus"></i>
                </a>
                @endif
            </span>
        </div>
        <div class="card-body" id="">

            @if(count($rec->child_docs)>0)
                <table class="table table-striped table-sm" style="width: 100%;">
                    <tr class="small">
                        <td>Тип</td>
                        <td>#, дата</td>
                        <td>Сумма, руб</td>
                        <td>
                        </td>
                    </tr>
                    <?php
                    $totUsedSum = 0;
                    $doctypes = [1 => 'счет', 2 => 'УПД'];
                    ?>
                    @foreach($rec->child_docs as $itm)
                        <tr class="">
                            <td class="small">{{$doctypes[$itm->doctypeid]??'?'}}</td>
                            <td>
                                <a href="{{route('invoices.edit',$itm->id)}}" class="button btn-sm btn-light">
                                    {{$itm->docnum}}
                                    <span class="small">от {{date_create($itm->docdate)->format('d.m.Y')}}</span>
                                </a>
                            </td>
                            <td class="text-right">
                                {{number_format($itm->usedsum,2)}}
                                <div class="small mt-1">{{number_format($itm->docsum,2)}}</div>
                            </td>
                            <td>
                                <a href="{{route('invoices.edit',$itm->id)}}" class="button btn-sm btn-light">
                                    <i class="fa fa-pencil" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
{{--                        @php($totDocsSum+=$itm->docsum)--}}
                        @php($totUsedSum+=$itm->usedsum)
                    @endforeach
                    <?php
                    $totDocsSum_class = ($totUsedSum == $rec->docsum) ? 'text-success' : 'text-danger';
                    ?>
                    <tr>
                        <td colspan="2"></td>
                        <td class="text-right font-weight-bold {{$totDocsSum_class}}">{{number_format($totUsedSum,2)}}</td>
                        <td></td>
                    </tr>
                </table>
            @else
                - нет данных -
            @endif

        </div>
    </div>

@endif

