@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->breaks) )

    <div class="card mt-3">
        <div class="card-header" style="background-color: #fff5d1;">
            <i class="fa fa-hourglass text-danger" aria-hidden="true"></i>
            Простой
            <div class="float-right">
                <button data-toggle="collapse" data-target="#breaks"
                        class="btn btn-light btn-sm">
                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                    <span class="badge badge-info">{{count($rec->breaks)}}</span>
                </button>
                @if( $usrrights['save']??false )
                    <a href="{{ route('dw_breaks.create',['dw_id'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning ml-2"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        @if (count($rec->breaks)>0)
            <div class="card-body collapse" id="breaks">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-right">Когда</td>
                        <td class="text-center">Почему</td>
                        <td class="text-center">Сколько</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $tot_hrs = 0;
                    $cur_breaktypeid = -1;
                    ?>
                    @foreach($rec->breaks as $itm)
                        @if($itm->breaktypeid<>$cur_breaktypeid)
                            <tr>
                                <td colspan="5" class="font-weight-bold font-italic bg-warning pl-2">{{$rec->breaktypes[$itm->breaktypeid]??'-'}}</td>
                            </tr>
                            <?php
                            $cur_breaktypeid = $itm->breaktypeid;
                            ?>
                        @endif

                        <?php
                        $npp++;

                        $linestyle = "";
                        //                        if ($itm->active == 0) {
                        //                            $linestyle = "background-color:lightsalmon;";
                        //                        }
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-right small" style="{{$linestyle}}">
                                {{date_create($itm->breakbegdt)->format('H:i')}}
                                - {{date_create($itm->breakenddt)->format('H:i')}}
                            </td>
                            <td class="text-left pl-2" style="{{$linestyle}}">
                                {{$itm->reason}}
                            </td>
                            <td class="small text-right">{{$itm->breakhrs}}</td>
                            <td class="text-right">
                                @if(1==1)
                                    <a href="{{ route('dw_breaks.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                       class="btn btn-sm btn-primary"
                                       title="Просмотреть/Изменить запись">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        <?php
                        $tot_hrs += $itm->breakhrs;
                        ?>
                    @endforeach
                    <tr>
                        <td colspan="3" class="text-right">Всего, ч:</td>
                        <td class="text-right">{{$tot_hrs}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
