{{--@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->obj_orgs) and count($rec->obj_orgs)>0)--}}
@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->obj_orgs) )

    <div class="card mt-3">
        <div class="card-header" style="background-color: #d1fff1;">
            <i class="fa fa-handshake-o text-primary" aria-hidden="true"></i>
            Организации-участники (стороны) документа
            <div class="float-right">
                <button data-toggle="collapse" data-target="#obj_orgs"
                        class="btn btn-light btn-sm">
                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                    <span class="badge badge-info">{{count($rec->obj_orgs)}}</span>
                </button>
                @if( $usrrights['save']??false )
                    <a href="{{ route('obj_orgs.create',['sysobjid'=>$rec->sysobjid,'objid'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning ml-2"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        @if (count($rec->obj_orgs)>0)
            <div class="card-body collapse" id="obj_orgs">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-right">Роль</td>
                        <td class="text-center">Организация</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    ?>
                    @foreach($rec->obj_orgs as $itm)
                        <?php
                        $npp++;

                        $linestyle = "";
                        if ($itm->active == 0) {
                            $linestyle = "background-color:lightsalmon;";
                        }
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-right small" style="{{$linestyle}}">
                                {{$itm->roletype_name}}
                            </td>
                            <td class="text-left pl-2" style="{{$linestyle}}">
                                {{$itm->org_name}}
                                <div class="ml-3 small">{{$itm->orgdepname}}</div>
                            </td>
                            <td class="text-right">
                                @if(1==1)
                                    <a href="{{ route('obj_orgs.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                       class="btn btn-sm btn-primary"
                                       title="Просмотреть/Изменить запись">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
