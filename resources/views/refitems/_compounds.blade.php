@if($rec->id != -1 and isset($rec->compounds) )
    <?php
    $TotPaySum = 0;
    ?>
    <div class="card mt-3">
        <div class="card-header" style="background-color: #b5ffa0;">

            <a name="compounds"></a>

            <span data-toggle="collapse" data-target="#childs">
				<i class="fa fa-cubes" aria-hidden="true" style="color: darkgreen"></i> Составы комплектующих для произодства</span>

            <div class="float-right">
                @if (count($rec->compounds)>0)
                    <button data-toggle="collapse" data-target="#compounds"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
            </div>
            @if($usrrights['create']??true)
                <a href="{{ route('ri_compounds.create') . '?refitmid='. $rec->id }}"
                   class="btn btn-warning btn-sm"
                   style="margin-left:16px;float: right;">
                    <i class="fa fa-plus"></i>
                </a>
            @endif
        </div>
        @if (count($rec->compounds)>0)
            <div class="card-body collapse show" id="compounds">

                <table class="table-striped table-bordered0 p-1" style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-center">Период действия</td>
                        <td class="text-left">Примечание</td>
                        <td style="width:32px">
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $totOrdSum = 0;
                    $totOrdQty = 0;
                    ?>
                    @foreach($rec->compounds as $itm)
                        <?php
                        $npp++;
                        $totOrdSum += 0;
                        $totOrdQty += 0;

                        $enddate = $itm->enddate;
                        if (isset($enddate))
                            $enddate = date_create($enddate)->format('d.m.Y');
                        else
                            $enddate = '...';
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-center small" style="">
                                <a href="{{ route('ri_compounds.edit',['id'=>$itm->id])}}?returl={{Request::url()}}">
                                    {{date_create($itm->begdate)->format('d.m.Y')}} - {{$enddate}}</a>
                            </td>
                    we        <td class="text-left small" style="">
                                {{$itm->notes}}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('ri_compounds.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
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
