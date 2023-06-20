@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->raids) )

    <div class="card mt-3">
        <div class="card-header" style="background-color: #d1fff1;">
            <i class="fa fa-truck text-primary" aria-hidden="true"></i>
            Рейсы
            <div class="float-right">
                <button data-toggle="collapse" data-target="#raids"
                        class="btn btn-light btn-sm">
                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                    <span class="badge badge-info">{{count($rec->raids)}}</span>
                </button>
                @if( $usrrights['save']??false )
                    <a href="{{ route('mchn_raids.create',['dw_id'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning ml-2"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        @if (count($rec->raids)>0)
            <div class="card-body collapse" id="raids">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-center">Когда</td>
                        <td class="text-center">Рейсов</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $tot_salarysum = 0;
                    ?>
                    @foreach($rec->raids as $itm)
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
                                {{date_create($itm->wrkdate)->format('d.m.Y')}}
                            </td>
                            <td class="text-center pl-2" style="{{$linestyle}}">
                                {{$itm->raid_qty??0}}
                            </td>
                            <td class="text-right">
                                @if(1==1)
                                    <a href="{{ route('mchn_raids.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                       class="btn btn-sm btn-primary"
                                       title="Просмотреть/Изменить запись">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        <?php
                        $tot_salarysum += $itm->raid_salary * $itm->raid_qty;
                        ?>
                    @endforeach
{{--                    <tr>--}}
{{--                        <td colspan="2" class="text-right">Всего:</td>--}}
{{--                        <td class="text-right">{{$tot_salarysum}}</td>--}}
{{--                    </tr>--}}
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
