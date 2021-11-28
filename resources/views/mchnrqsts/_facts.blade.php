@if($rec->id != -1 )
    <?php
    //        dd($rec->facts);
    $totCnt = (isset($rec->facts)) ? count($rec->facts) : 0;
    $totWrkHrs = 0;
    ?>

    <div class="card mt-3">
        <div class="card-header" style="background-color: darkseagreen;">
            <a name="facts"></a>

            <span data-toggle="collapse" data-target="#items">
				<i class="fa fa-check-circle text-success" aria-hidden="true"></i> Фактические данные</span>

            <div class="float-right">
                @if ($totCnt>0)
                    <button data-toggle="collapse" data-target="#items"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if( $usrrights['create']??false)
                    <a href="{{ route('mchnrqst_facts.create',['rqstid'=>$rec->id])}}"
                       class="btn btn-warning btn-sm ">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>

        </div>
        @if ($totCnt>0)
            <div class="card-body" id="items">

                <table class="table table-striped table-sm">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-left">Техника</td>
                        <td class="text-left">Начало работы</td>
                        <td class="text-left">Окончание</td>
                        <td class="text-center">Продолжительность, час</td>
                        <td class="text-center">Машинист/Водитель</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $curDMY = -1;
                    ?>
                    @foreach($rec->facts as $itm)
                        <?php
                        $linestyle = "";
                        if (!isset($itm->fctenddt)) {
                            $linestyle = "background-color:lightsalmon;";
                        }
                        $fctbegdt = date_create($itm->fctbegdt);
                        ?>
                        @if($fctbegdt->format('Y-m-d')<>$curDMY)
                            <tr>
                                <td colspan="7">{{$fctbegdt->format('d.m.Y')}}</td>
                            </tr>
                            <?php
                            $curDMY = $fctbegdt->format('Y-m-d');
                            ?>
                        @endif

                        <tr class="align-top ">
                            <td class="small text-right"></td>
                            <td class="text-left font-weight-bold" style="{{$linestyle}}">

                            </td>
                            <td class="text-center small" style="{{$linestyle}}">
                                <a href="{{ route('mchnrqst_facts.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                >{{date_create($itm->fctbegdt)->format('H:i')}}</a>
                            </td>
                            <td class="text-center small" style="{{$linestyle}}">
                                @if(isset($itm->fctenddt))
                                    {{date_create($itm->fctenddt)->format('H:i')}}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center small" style="{{$linestyle}}">
                                {{$itm->fcthrs}}
                            </td>
                            <td class="text-left small" style="{{$linestyle}}">
                                <span class="font-weight-bold"> {{$itm->drivername}}</span>
                            </td>
                            <td class="text-right">
                                @if(1==1)
                                    <a href="{{ route('mchnrqst_facts.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                       class="btn btn-sm btn-light"
                                       title="Просмотреть/Изменить запись">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        <?php
                        $totWrkHrs += $itm->fcthrs;
                        ?>
                    @endforeach
                    <tr>
                        <td colspan="4" class="text-right">Всего:</td>
                        <td class="text-center font-weight-bold">{{$totWrkHrs}}</td>
                    </tr>
                    </tbody>
                </table>

            </div>
        @endif

        @if ($totCnt>0)
            <div class="card-footer">
                <div class="small text-right">
                    всего записей: {{$npp}}
                    &nbsp;&nbsp;&nbsp;всего часов: {{$totWrkHrs}}

                    @if (1==1 and $rec->statusid == 1)
                        <a class="btn btn-close btn-warning ml-3 btn-sm"
                           href="#"
                           target="_blank"
                           title="Напечатать протокол">
                            <i class="fa fa-print" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
            </div>
        @endif

    </div>
@endif
