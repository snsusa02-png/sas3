@if($rec->id != -1 )

    @if(isset($rec->parent_by))
        <div class="card mt-3">
            <div class="card-header" style="background-color: #ddffef;">
            <span data-toggle="collapse" data-target="#childs">
				<i class="fa fa-level-up text-success"
                   aria-hidden="true"></i> Родительская ЕИ: <a href="{{route('unittypes.edit',$rec->parent_by)}}"><b>{{$rec->parent->name}}</b></a></span>
            </div>
        </div>
    @endif

    <div class="card mt-3">
        <div class="card-header" style="background-color: #ddffef;">
            <a name="childs"></a>
            <span data-toggle="collapse" data-target="#childs">
				<i class="fa fa-level-down text-success" aria-hidden="true"></i> Производные ЕИ</span>

            <div class="float-right">
                @if (count($rec->childs)>0)
                    <button data-toggle="collapse" data-target="#childs"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if( $usrrights['save']??false)
                    <a href="{{ route('unittypes.create',['parid'=>$rec->id])}}"
                       class="btn btn-warning btn-sm ">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>

        </div>
        @if (count($rec->childs)>0)
            <div class="card-body collapse show" id="childs">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-left">Название, описание</td>
                        <td class="text-center">К перевода в род. ЕИ</td>
                        <td class="text-center">Точность</td>
                        <td></td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    ?>
                    @foreach($rec->childs as $itm)
                        <?php
                        $npp++;

                        $linestyle = "";
                        if ($itm->active == 0) {
                            $linestyle = "background-color:#ffebeb;";
                        }
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-center " style="{{$linestyle}}">
                                <a href="{{ route('unittypes.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"><b>{{$itm->name}}</b></a>
                                <span class="small ml-1">{{$itm->descript}}</span>
                            </td>
                            <td class="text-right">{{number_format($itm->k2prnt_unit,4)}}</td>
                            <td class="text-center">{{$itm->decimal_dgts}}</td>
                            <td class="text-right">
                                <a href="{{ route('unittypes.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   class="btn btn-sm btn-light"
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
