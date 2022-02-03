@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->contract_orgs) and count($rec->contract_orgs)>0)

    <div class="card mt-3">
        <div class="card-header" style="background-color: #d1fff1;">
            <i class="fa fa-handshake-o text-primary" aria-hidden="true"></i>
            Участники(стороны) договора
            <div class="float-right">
                <button data-toggle="collapse" data-target="#contract_orgs"
                        class="btn btn-light btn-sm">
                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                    <span class="badge badge-info">{{count($rec->contract_orgs)}}</span>
                </button>
                @if( $usrrights['save']??false )
                    <a href="{{ route('contract_orgs.create',['contractid'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning ml-2"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        @if (count($rec->contract_orgs)>0)
            <div class="card-body collapse" id="contract_orgs">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-right">Роль</td>
                        <td class="text-center">Организация</td>
                        <td class="text-right">Основание</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    ?>
                    @foreach($rec->contract_orgs as $itm)
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
                                {{$itm->rolename}}
                            </td>
                            <td class="text-center" style="{{$linestyle}}">
                                {{$itm->org->name}}
                            </td>
                            <td class="text-left small" style="{{$linestyle}}">
                            </td>
                            <td class="text-right">
                                @if(1==1)
                                    <a href="{{ route('contract_orgs.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
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
