@if( $rec->id<>-1 and isset($rec->linked_docs)  )

    <style>
        .current {
            background-color: #f0f0f0;
            color: gray;
        }
    </style>
    <div class="card mt-3 mb-3">
        <div class="card-header" style="background-color: #93dbf2;">
            Связанные документы

            <span class="float-right">
            </span>
        </div>
        <div class="card-body" id="">

            @if(count($rec->linked_docs)>0)
                <table class="table table-striped table-sm" style="width: 100%;">
                    <tr class="small">
                        <td>#</td>
                        <td>Название</td>
                        <td>Тип</td>
                    </tr>
                    <?php
                    $totDocsSum = 0;
                    $doctypes = [1 => 'счет', 2 => 'УПД'];
                    ?>
                    @foreach($rec->linked_docs as $itm)
                        <tr class="">
                            <td class="small">{{$loop->index+1}}</td>
                            <td>
                                <a href="{{route('invoices.edit',$itm->id)}}" class="button btn-sm btn-light">
                                    {{$itm->name}}
                                </a>
                            </td>
                            <td class="small">{{$itm->linktypename}}</td>
                        </tr>
                    @endforeach
                </table>
            @else
                - нет данных -
            @endif

        </div>
    </div>

@endif

