{{--@dd(isset($rec->boxes), $rec->id)--}}
@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->boxes))

    <div class="card mt-3">
        <div class="card-header" style="background-color: #f2ffca;">
            <i class="fa fa-th-large text-info" aria-hidden="true"></i>
            Отделения склада
            <div class="float-right">
                @if(count($rec->boxes)>0)
                    <button data-toggle="collapse" data-target="#boxes"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->boxes)}}</span>
                    </button>
                @endif
                @if(1==1)
                    <a href="{{ route('wrh_boxes.create',['wrhid'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        @if (count($rec->boxes)>0)
            <div class="card-body collapse1" id="boxes">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-left">Название, описание</td>
                        <td class="text-right"></td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    ?>
                    @foreach($rec->boxes as $itm)
                        <?php
                        $npp++;

                        $linestyle = "";
                        if ($itm->active == 0) {
                            $linestyle = "background-color:lightsalmon;";
                        }
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-left small" style="{{$linestyle}}">
                                {{$itm->name}}
                                <div class="ml-3">{{$itm->descript}}</div>
                            </td>
                            <td class="text-right small" style="{{$linestyle}}">

                            </td>
                            <td class="text-right">
                                <a href="{{ route('wrh_boxes.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   class="btn btn-sm btn-primary"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
