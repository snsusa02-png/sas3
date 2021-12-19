<div class="card mt-3">
    <div class="card-header">
        Подкатегории товара "<b>{{ $rec->name }}</b>"

        <a href="{{ route('itmtypes.create',$rec->id)}}"
           class="btn btn-warning btn-sm ml-2 float-right">
            <i class="fa fa-plus"></i>
        </a>
    </div>
    @if(isset($subtype) and $subtype->count()>0)
        <div class="card-body">
            <table class="table-striped small" style="width: 100%;">
                <thead>
                <tr>
                    <td>#</td>
                    <td>Наименование</td>
                    <td class="text-center">Порядок</td>
                    <td/>
                </tr>
                </thead>
                <tbody>
                @foreach($subtype as $itm)
                    <?php
                    $linestyle = ($itm->active == 0) ? "background-color:#ffebeb;" : '';
                    ?>
                    <tr style="{{$linestyle}}">
                        <td style="text-align: right;" class="small">{{$loop->iteration}}</td>
                        <td class="font-weight-bold">&nbsp;<a
                                href="{{ route('itmtypes.edit',$itm->id)}}">{{$itm->name}}</a></td>
                        <td class="text-center">{{$itm->ordr}}</td>
                        <td style="text-align: right;">
                            <a href="{{ route('itmtypes.edit',$itm->id)}}"
                               class="btn btn-sm btn-primary">
                                <i class="fa fa-pencil">
                                </i>
                            </a>
                        <td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
