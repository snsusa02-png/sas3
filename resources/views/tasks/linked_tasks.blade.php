@if($rec->id != -1 and isset($rec->linked_tasks))
    <?php
    ?>
    <div class="card mt-3">
        <div class="card-header" style="background-color: #fffddd;">
            <i class="fa fa-tasks fa-spin0 text-danger" aria-hidden="true"></i> Связанные задачи
            <div class="float-right">
                @if(count($rec->linked_tasks)>0)
                    <button data-toggle="collapse" data-target="#linked_tasks"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->linked_tasks)}}</span>
                    </button>
                @endif
                @if( $usrrights['link_tasks']??false and isset($data->sysobjid) )
                    <a href="{{ route('tasks.create')}}?srcsysobjid={{$data->sysobjid}}&srcobjid={{$rec->id}}&returl={{Request::url()}}"
                       class="btn btn-sm btn-warning"
                       title="Создать задачу">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif

            </div>
        </div>
        @if (count($rec->linked_tasks)>0)
            <div class="card-body collapse" id="linked_tasks">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-left">Описание</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    ?>
                    @foreach($rec->linked_tasks as $itm)
                        <?php
                        $npp++;

                        $linestyle = "";
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-left " style="{{$linestyle}}">
                                <a href="{{ route('tasks.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   title="Перейти к записи">
                                    {{$itm->name}}
                                    @if(isset($itm->docnum))
                                        № {{$itm->docnum}}
                                        @if(isset($itm->docdate))
                                            от {{date_create($itm->docdate)->format('d.m.Y')}}
                                        @endif
                                    @endif
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
