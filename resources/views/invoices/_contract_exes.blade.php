@if($rec->id != -1 and isset($rec->contract_exes) )
    <?php
    $TotPaySum = 0;
    ?>
    <div class="card mt-3">
        <div class="card-header" style="background-color: #e6dbff;">

            <a name="contract_exes"></a>

            <span data-toggle="collapse" data-target="#childs">
				<i class="fa fa-check-circle-o" aria-hidden="true"></i> Связ. исполнение по документам</span>

            <div class="float-right">
                @if (count($rec->contract_exes)>0)
                    <button data-toggle="collapse" data-target="#contract_exes"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if( $usrrights['save']??false or 1==1)
                    <a href="{{ route('invoices.make_contract_exe',['id'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-warning btn-sm ">
                        <i class="fa fa-refresh"></i>
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
                        <td class="text-right">Сумма давал., руб</td>
                        <td style="width:32px">
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $totDocSum = 0;
                    $totBudgetSum = 0;
                    $totDavalSum = 0;
                    $botDocSum = 0;
                    $botBudgetSum = 0;
                    $botDavalSum = 0;
                    $curBuildOperTypeid = -1;
                    $doctypes = \App\contract_exe::doctypes();
                    ?>
                    @foreach($rec->contract_exes as $itm)
                        @if($itm->buildopertypeid<>$curBuildOperTypeid)

                            @if($curBuildOperTypeid<>-1)
                                <tr class="small">
                                    <td colspan="2" class="text-right">Итого по виду работ:</td>
                                    <td class="font-weight-bold text-right">{{number_format($botDocSum,2)}}</td>
                                    <td class="font-weight-bold text-right">{{number_format($botBudgetSum,2)}}</td>
                                    <td class="font-weight-bold text-right">{{number_format($botDavalSum,2)}}</td>
                                    <td></td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="5" class="font-weight-bold font-italic">
                                    {{$itm->buildopertypename}}
                                </td>
                            </tr>
                            <?php
                            $curBuildOperTypeid = $itm->buildopertypeid;
                            $botDocSum = 0;
                            $botBudgetSum = 0;
                            $botDavalSum = 0;
                            ?>
                        @endif

                        <?php
                        $npp++;
                        $botDocSum += $itm->docsum;
                        $totDocSum += $itm->docsum;

                        if ($itm->doctypeid == 2) {
                            $budgetSum = '-';
                            $davalSum = number_format($itm->docsum, 2);
                            $botDavalSum += $itm->docsum;
                            $totDavalSum += $itm->docsum;
                        } else {
                            $budgetSum = number_format($itm->docsum, 2);
                            $davalSum = '-';
                            $botBudgetSum += $itm->docsum;
                            $totBudgetSum += $itm->docsum;
                        }
                        ?>

                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-center small" style="">
                                {{date_create($itm->docdate)->format('d.m.Y')}}
                            </td>
                            <td class="text-left " style="">
                                <span class="small">{{$doctypes[$itm->doctypeid]??'n/a'}}</span> / {{$itm->docinfo}}
                            </td>
                            <td class="text-right" style="">
                                {{$budgetSum}}
                            </td>
                            <td class="text-right small" style="">
                                {{$davalSum}}
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
                            <td class="font-weight-bold text-right">{{number_format($botBudgetSum,2)}}</td>
                            <td class="font-weight-bold text-right">{{number_format($botDavalSum,2)}}</td>
                            <td></td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="2" class="text-right">Всего:</td>
                        <td class="font-weight-bold text-right">{{number_format($totDocSum,2)}}</td>
                        <td class="font-weight-bold text-right">{{number_format($totBudgetSum,2)}}</td>
                        <td class="font-weight-bold text-right">{{number_format($totDavalSum,2)}}</td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        @endif

        @if (count($rec->contract_exes)>0)
            <div class="card-footer">
                <span>
                    @if(1==0)
                        <a class="btn btn-close btn-info ml-3 btn-sm "
                           href="{{ route('contract_exes.print_1', $rec->id) }}"
                           target="_blank"
                           title="Напечатать">
                        <i class="fa fa-print" aria-hidden="true"></i>
                    </a>
                    @endif
                </span>
                <div class="small text-right"> всего освоено: {{number_format($totDocSum,2)}}</div>
            </div>
        @endif

    </div>
@endif
