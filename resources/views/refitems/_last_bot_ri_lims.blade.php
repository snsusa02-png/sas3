@if($rec->id != -1 and isset($rec->last_bot_ri_lims) and count($rec->last_bot_ri_lims)>0)
    <?php
    $TotPaySum = 0;
    ?>
    <div class="card mt-3">
        <div class="card-header" style="background-color: #6faae7;">

            <a name="bot_ri_lims"></a>

            <span data-toggle="collapse" data-target="#childs">
				 ... в ресурсных ведомостях</span>

            <div class="float-right">
                @if (count($rec->last_bot_ri_lims)>0)
                    <button data-toggle="collapse" data-target="#last_bot_ri_lims"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
            </div>

        </div>
        @if (count($rec->last_bot_ri_lims)>0)
            <div class="card-body collapse show" id="last_bot_ri_lims">

                <table class="table-striped table-bordered0 p-1" style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td class="text-right">Вид работ</td>
                        <td class="text-right">Лимит, {{$rec->unittype->name}}</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $totOrdSum = 0;
                    $totOrdQty = 0;
                    ?>
                    @foreach($rec->last_bot_ri_lims as $itm)
                        <?php
                        $npp++;

                        $plngetdate = $itm->created_at;
                        if (isset($plngetdate))
                            $plngetdate = date_create($plngetdate)->format('d.m.Y');
                        else
                            $plngetdate = '-';
                        ?>
                        <tr class="align-top ">
                            <td class="text-right small" style="">
                                {{$itm->buildopertypename}}
                                <a href="{{ route('buildopertypes.edit',['id'=>$itm->buildopertypeid])}}?returl={{Request::url()}}"
                                   target="_blank">...
                                    </a>
                            </td>
                            <td class="text-right small" style="">
                                <a href="{{ route('bot_ri_lims.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   ><b>{{$itm->lim_qty}}</b>
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
