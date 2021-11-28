@if( 1==1 and isset($rec) and ($rec->id!=-1) and $rec->kindid==1 )

    <div class="card mt-3">
        <div class="card-header" style="background-color: #ecebef;">
            <a name="orgposts"></a>

            <span data-toggle="collapse" data-target="#orgposts">
                Штатное расписание
            </span>

            <div class="float-right">
                @if (isset($rec->orgposts) and count($rec->orgposts)>0)
                    <button data-toggle="collapse" data-target="#orgposts"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if($usrrights['orgdeps.create']??false)
                    <a href="{{ route('orgdeps.create',$rec->id)}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
                @if(1==0 and $usrrights['create']??false)
                    <a href="{{ route('orgposts.create',['orgid'=>$rec->id])}}"
                       class="btn btn-warning btn-sm ">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>

        @if (isset($rec->orgposts) and count($rec->orgposts)>0)
            <div class="card-body collapse" id="orgposts">

                <table class="table table-striped table-responsive w-100">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-left">Должность</td>
                        {{--                        <td class="text-right">Cтавки</td>--}}
                        <td class="text-right">Вакантно</td>
                        <td class="text-right">Занято</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $curDepID = -1;
                    $totstdunits = 0;
                    $totstdfreeunits = 0;
                    $totstdusedunits = 0;
                    ?>
                    @foreach($rec->orgposts as $itm)

                        @if($itm->depid<>$curDepID)
                            <tr>
                                <td colspan="5" class="small">
                                    <b><a href="{{ route('orgdeps.edit',['id'=>$itm->depid])}}?returl={{Request::url()}}"
                                          style="color: #02216b">{{$itm->depname}}</a</b> <span
                                        class="small ml-2"> {{$itm->code}}</span>
                                </td>
                            </tr>
                            <?php
                            $curDepID = $itm->depid;
                            ?>
                        @endif

                        <?php
                        $linestyle = "";
                        if ($itm->active == 0) {
                            $linestyle = "background-color:lightsalmon;";
                        }

                        ?>
                        @if(isset($itm->id))

                            <tr class="align-top ">
                                <td class="small text-right"><a id="machine_{{$itm->id}}"></a></td>
                                <td class="text-left small" style="{{$linestyle}}">
                                    @if(isset($itm->id))
                                        <a href="{{ route('orgposts.edit',['id'=>$itm->id])}}?returl={{Request::url()}}">{{$itm->name}}</a>
                                    @else
                                        {{$itm->name}}
                                    @endif
                                    <div class="ml-3 small"> {{$itm->descript}}</div>
                                </td>
                                {{--                            <td class="text-right small" style="{{$linestyle}}">--}}
                                {{--                                {{number_format($itm->stdlimunits,2)}}--}}
                                {{--                            </td>--}}
                                <td class="text-right small" style="{{$linestyle}}">
                                    {{number_format($itm->stdlimunits-$itm->stdusedunits,2)}}
                                </td>
                                <td class="text-right small" style="{{$linestyle}}">
                                    {{number_format($itm->stdusedunits,2)}}
                                </td>

                                <td class="text-right">
                                    @if(1==1)
                                        <a href="{{ route('orgposts.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                           class="btn btn-sm btn-info"
                                           title="Просмотреть/Изменить запись">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            <?php
                            $totstdunits += $itm->stdlimunits;
                            $totstdfreeunits += $itm->stdlimunits - $itm->stdusedunits;
                            $totstdusedunits += $itm->stdusedunits;
                            ?>
                        @endif
                    @endforeach
                    <tr>
                        <td colspan="4" class="text-right">
                            Всего: {{$totstdunits}} / работников: {{$totstdusedunits}} / вакансий: {{$totstdfreeunits}}
                        </td>
                    </tr>
                    </tbody>
                </table>

            </div>
        @endif

        @if (isset($rec->orgposts) and count($rec->orgposts)>0)
            <div class="card-footer">
                <div class="small text-right">
                    всего записей: {{count($rec->orgposts)}}

                    @if (1==0 and $rec->statusid == 1)
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
