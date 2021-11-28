@if($rec->id != -1 )
    <?php
    $totWrkHrs = 0;
    ?>

    <div class="card mt-3">
        <div class="card-header" style="background-color: #fdf2b5;">
            <a name="items"></a>

            <span data-toggle="collapse" data-target="#items">
				<i class="fa fa-user text-info" aria-hidden="true"></i> Состав</span>

            <div class="float-right">
                @if (count($rec->items)>0)
                    <button data-toggle="collapse" data-target="#items"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if( $usrrights['create']??false)
                    <a href="{{ route('ocl_items.create',['ocl_id'=>$rec->id])}}"
                       class="btn btn-warning btn-sm ">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>

        </div>
        @if (count($rec->items)>0)
            <div class="card-body  show" id="items">

                <table class="table table-striped table-responsive">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td class="text-left">Подразделение</td>
                        <td class="text-left">Индекс дела</td>
                        <td class="text-left">Заголовок дела</td>
                        <td class="text-left">Кол-во дел</td>
                        <td class="text-center">Срок хранения дела</td>
                        <td class="text-left">Примечание</td>
                        {{--                        <td class="text-center">Вид работ</td>--}}
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $curDepID = -1;
                    ?>
                    @foreach($rec->items as $itm)
                        @if($itm->orgdepid<>$curDepID)
                            <tr>
                                <td colspan="7" class="font-weight-bold font-italic" style="background-color: #fffcc9">{{$itm->orgdep_name}}</td>
                            </tr>
                            <?php
                            $curDepID = $itm->orgdepid;
                            ?>
                        @endif
                        <?php
                        $npp++;
                        $linestyle = "";
                        //$linestyle = "background-color:lightsalmon;";

                        ?>
                        <tr class="align-top ">
                            <td></td>
                            <td class="small text-right font-weight-bold"><a id="item_{{$itm->id}}"></a>{{$itm->code}}</td>
                            <td class="text-left  " style="{{$linestyle}}">
                                <a href="{{ route('ocl_items.edit',['id'=>$itm->id])}}?returl={{Request::url()}}">{{$itm->name}}</a>
                            </td>
                            <td class="text-center small" style="{{$linestyle}}">
                                {{$itm->case_qty}}
                            </td>
                            <td class="text-left small" style="{{$linestyle}}">
                                {{$itm->shelflife_reason}}
                            </td>
                            <td class="text-left small" style="{{$linestyle}}">
                                {{$itm->notes}}
                            </td>
                            <td class="text-right">
                                @if(1==1)
                                    <a href="{{ route('ocl_items.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                       class="btn btn-sm btn-info"
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

        @if (count($rec->items)>0)
            <div class="card-footer">
                <div class="small text-right">
                    всего записей: {{$npp}}

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
