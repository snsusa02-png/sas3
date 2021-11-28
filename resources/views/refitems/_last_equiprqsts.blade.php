@if($rec->id != -1 and isset($rec->last_equiprqsts) and count($rec->last_equiprqsts)>0)
    <?php
    $TotPaySum = 0;
    ?>
    <div class="card mt-3">
        <div class="card-header" style="background-color: #a0f7ff;">

            <a name="equiprqsts"></a>

            <span data-toggle="collapse" data-target="#childs">
				 ... в заявках</span>

            <div class="float-right">
                @if (count($rec->last_equiprqsts)>0)
                    <button data-toggle="collapse" data-target="#last_equiprqsts"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
            </div>

        </div>
        @if (count($rec->last_equiprqsts)>0)
            <div class="card-body collapse show" id="last_equiprqsts">

                <table class="table-striped table-bordered0 p-1" style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td class="text-right">Заявка</td>
                        <td class="text-center">Треб. дата получения</td>
                        <td class="text-right">Кол-во, {{$rec->unittype->name}}</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $totOrdSum = 0;
                    $totOrdQty = 0;
                    ?>
                    @foreach($rec->last_equiprqsts as $itm)
                        <?php
                        $npp++;

                        $plngetdate = $itm->maxreqdate;
                        if (isset($plngetdate))
                            $plngetdate = date_create($plngetdate)->format('d.m.Y');
                        else
                            $plngetdate = '-';
                        ?>
                        <tr class="align-top ">
                            <td class="text-right small" style="">
                                <a href="{{ route('equiprqsts.edit',['id'=>$itm->rqstid])}}?returl={{Request::url()}}"
                                >{{$itm->rqstid}}</a>
                            </td>
                            <td class="text-center small" style="">
                                {{$plngetdate}}
                            </td>
                            <td class="text-right small" style="">
                                {{$itm->rqst_qty}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
@endif
