@if($rec->id != -1 and isset($rec->contract_exes) )
    <?php
    $TotPaySum = 0;
    ?>
    <div class="card mt-3">
        <div class="card-header" style="background-color: #e6dbff;">

            <a name="contract_exes"></a>

            <span data-toggle="collapse" data-target="#childs">
				<i class="fa fa-check-circle-o" aria-hidden="true"></i> Исполнение по документам</span>

            <div class="float-right">
                @if (count($rec->contract_exes)>0)
                    <button data-toggle="collapse" data-target="#contract_exes"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if( $usrrights['contract_exes.create']??false)
                    <a href="{{ route('contract_exes.create',['contractid'=>$rec->id,'buildopertypeid'=>0])}}?returl={{Request::url()}}"
                       class="btn btn-warning btn-sm ">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>

        </div>
        @if (count($rec->contract_exes)>0)
            <div class="card-body collapse show" id="contract_exes">

                <table class="table-striped table-bordered0 p-1" style="width: 100%;">
                    <thead>
                    <tr class="text-center small" valign="top">
                        <td>#</td>
                        <td class="text-center">Дата док-та</td>
                        <td class="text-left">Документ (Основание)</td>
                        <td class="text-right">Сумма исполн., руб</td>
                        <td class="text-right">Сумма поставок, руб</td>
                        <td class="text-right">Сумма давал., руб</td>
                        <td style="width:32px">
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $totDocSum = 0;
                    $totDoc1Sum = 0;
                    $totDoc2Sum = 0;
                    $totDoc3Sum = 0;

                    $botDocSum = 0;
                    $botDoc1Sum = 0;
                    $botDoc2Sum = 0;
                    $botDoc3Sum = 0;

                    $curBuildOperTypeid = -1;
                    $doctypes = \App\contract_exe::doctypes();
                    ?>
                    @foreach($rec->contract_exes as $itm)
                        @if($itm->buildopertypeid<>$curBuildOperTypeid)

                            @if($curBuildOperTypeid<>-1)
                                <tr class="small">
                                    <td colspan="2" class="text-right">Итого по виду работ:</td>
                                    <td class="font-weight-bold text-right">{{number_format($botDocSum,2)}}</td>
                                    <td class="font-weight-bold text-right">{{number_format($botDoc1Sum,2)}}</td>
                                    <td class="font-weight-bold text-right">{{number_format($botDoc3Sum,2)}}</td>
                                    <td class="font-weight-bold text-right">{{number_format($botDoc2Sum,2)}}</td>
                                    <td></td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="7" class="font-weight-bold font-italic">
                                    {{$itm->buildopertypename}}
                                </td>
                            </tr>
                            <?php
                            $curBuildOperTypeid = $itm->buildopertypeid;
                            $botDocSum = 0;
                            $botDoc1Sum = 0;
                            $botDoc2Sum = 0;
                            $botDoc3Sum = 0;
                            ?>
                        @endif

                        <?php
                        $npp++;
                        $botDocSum += $itm->docsum;
                        $totDocSum += $itm->docsum;

                        $doc1Sum = '-';
                        $doc2Sum = '-';
                        $doc3Sum = '-';
                        if ($itm->doctypeid == 1) {
                            $doc1Sum = number_format($itm->docsum, 2);
                            $doc2Sum = number_format($itm->m15_sum, 2);;
                            $botDoc1Sum += $itm->docsum;
                            $totDoc1Sum += $itm->docsum;
                            $botDoc2Sum += $itm->m15_sum;
                            $totDoc2Sum += $itm->m15_sum;
                        } elseif ($itm->doctypeid == 2) {
                            //док-т про давальческий материал
                            $doc2Sum = number_format($itm->docsum, 2);;
                            $botDoc2Sum += $itm->docsum;
                            $totDoc2Sum += $itm->docsum;
                        } elseif ($itm->doctypeid == 3) {
                            $doc3Sum = number_format($itm->docsum, 2);;
                            $botDoc3Sum += $itm->docsum;
                            $totDoc3Sum += $itm->docsum;
                        }
                        ?>

                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-center small" style="">
                                {{date_create($itm->docdate)->format('d.m.Y')}}
                            </td>
                            <td class="text-left " style="">
                                <?php
                                $rsn_url = null;
                                if ($itm->rsn_sysobjid == 873)
                                    $rsn_url = route('equiprqst_expenses.edit', $itm->rsn_objid);
                                elseif ($itm->rsn_sysobjid == 915)
                                    $rsn_url = route('invoices.edit', $itm->rsn_objid);

                                $docinfo = $itm->docinfo;
                                if(isset($rsn_url)){
                                    $docinfo = "<a href='{$rsn_url}' target='_blank'>{$docinfo}</a>";
                                }
                                ?>
                                <span class="small">{{$doctypes[$itm->doctypeid]??'n/a'}} / {!! $docinfo !!}</span>
                            </td>
                            <td class="text-right small" style="">
                                {{$doc1Sum}}
                            </td>
                            <td class="text-right small" style="">
                                {{$doc3Sum}}
                            </td>
                            <td class="text-right small" style="">
                                {{$doc2Sum}}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('contract_exes.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   class="btn btn-sm btn-light"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    @if($curBuildOperTypeid<>-1)
                        <tr class="small">
                            <td colspan="2" class="text-right">Итого по виду работ:</td>
                            <td class="font-weight-bold text-right">{{number_format($botDocSum,2)}}</td>
                            <td class="font-weight-bold text-right">{{number_format($botDoc1Sum,2)}}</td>
                            <td class="font-weight-bold text-right">{{number_format($botDoc3Sum,2)}}</td>
                            <td class="font-weight-bold text-right">{{number_format($botDoc2Sum,2)}}</td>
                            <td></td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="2" class="text-right">Всего:</td>
                        <td class="font-weight-bold text-right ">{{number_format($totDocSum,2)}}</td>
                        <td class="font-weight-bold text-right pl-1">{{number_format($totDoc1Sum,2)}}</td>
                        <td class="font-weight-bold text-right pl-1">{{number_format($totDoc3Sum,2)}}</td>
                        <td class="font-weight-bold text-right pl-1">{{number_format($totDoc2Sum,2)}}</td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        @endif

        @if (count($rec->contract_exes)>0)
            <div class="card-footer">
                <span>
                    <a class="btn btn-close btn-info ml-3 btn-sm "
                       href="{{ route('contract_exes.print_1', $rec->id) }}"
                       target="_blank"
                       title="Напечатать">
                        <i class="fa fa-print" aria-hidden="true"></i>
                    </a>
                    <a class="btn btn-close btn-info ml-1 btn-sm " style="background-color: #a994d0"
                           href="{{ route('reports.rep22', ['contractid'=>$rec->id]) }}"
                           target="_blank"
                           title="Сводный отчет">
                        <i class="fa fa-print" aria-hidden="true"></i>
                    </a>
                </span>
                <div class="small text-right"> всего освоено: {{number_format($totDocSum,2)}}</div>
            </div>
        @endif

    </div>
@endif
