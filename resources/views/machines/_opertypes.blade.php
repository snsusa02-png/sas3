@if($machine->id != -1 )
    <?php
    $TotPaySum = 0;
    ?>

    <div class="card mt-3">
        <div class="card-header" style="background-color: #fffea6;">

            <span data-toggle="collapse" data-target="#opertypes" style="cursor:pointer;"><i class="fa fa-cogs" aria-hidden="true"></i> Режимы эксплуатации</span>
            <a name="mchn_opertypes"></a>
            <div class="float-right">
                @if (count($rec->opertypes)>0)
                    <button data-toggle="collapse" data-target="#opertypes"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if( $usrrights['mchn_opertypes.create']??false)
                    <a href="{{ route('mchn_opertypes.create',['machineid'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-warning btn-sm ">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>

        </div>
        @if (count($machine->opertypes)>0)
            <div class="card-body collapse" id="opertypes">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-left">Операция</td>
                        <td class="text-right">Расценки, руб</td>
                        <td class="text-right">Расценки, %</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    ?>
                    @foreach($machine->opertypes as $itm)
                        <?php
                        $npp++;

                        $lineclass = "";
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-center small {{$lineclass}}">
                                {{$itm->name}}
                            </td>
                            <td class="text-right {{$lineclass}}" nowrap style="">
                                {{number_format($itm->hour_work_cost,2)}}
                                /
                                {{number_format($itm->hour_fuel_cost,2)}}
                            </td>
                            <td class="text-right {{$lineclass}}" nowrap style="">
                            {{number_format($itm->driver_fee_pcnt,1)}}
                            </td>
                            <td class="text-right">
                                @if(1==1)
                                    <a href="{{ route('mchn_opertypes.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
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
