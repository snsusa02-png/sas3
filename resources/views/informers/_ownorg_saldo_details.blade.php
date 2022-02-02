@if (isset($data->ownorg_saldo_details) and $data->ownorg_saldo_details->count()>0)
    <?php
    $retURL = Request::url();
    ?>
    <div class="col-lg-6 col-md-6 col-sm-6">
        <div class="card mb-1 mt-3">
            <div class="card-header">
                <ul class="nav nav-tabs1 nav-pills" role="tablist">
                    @foreach($data->ownorg_saldo_details as $ownorg)
                        <li class="nav-item {{$ownorg->active}}">
                            <a class="nav-link {{$ownorg->active}}" data-toggle="tab"
                               href="#oo_{{$ownorg->id}}">{{$ownorg->name}}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card-body">
                <!-- Tab panes -->
                <div class="tab-content mn">
                    @php($active="active")
                    @foreach($data->ownorg_saldo_details as $ownorg)
                        <div id="oo_{{$ownorg->id}}" class="container tab-pane {{$ownorg->active}}"><br>
                            {{$ownorg->name}}
                            @if(count($ownorg->recs)>0)
                                <?php
                                $ownorgid = $ownorg->id;
                                $recs = $ownorg->recs;
                                ?>
                                <table class="table table-sm table-striped rep-data mt-0"
                                       style="background-color: snow; font-size:16px; max-width:960px"
                                       align=center>
                                    <thead>
                                    <tr class="text-left small" valign="top">
                                        {{--                            <td class="text-center">Дата</td>--}}
                                        <td class="text-left">Клиент</td>
                                        <td class="text-right" title="Сумма долга или переплаты">Сальдо, руб
                                        </td>
                                        <td class="text-left">Контролирующий менеджер</td>
                                    </tr>

                                    </thead>
                                    <tbody>
                                    <?php
                                    $npp = 0;
                                    $totSum = 0;
                                    ?>

                                    @foreach($recs as $rec)
                                        <?php
                                        $td_class = ($rec->org_saldo < 0) ? 'text-danger' : (($rec->org_saldo > 0) ? 'text-success' : '');
                                        ?>
                                        <tr class="text-left ">
                                            <td class="text-left small">
                                                <a href="{{route('reports.rep48',[$ownorgid,$rec->orgid])}}?returl={{$retURL}}"
                                                   class="text-decoration-none">{{$rec->orgname}}</a>
                                            </td>
                                            <td class="text-right {{$td_class}}">{{number_format($rec->org_saldo,2)}}
                                            <td class="small">{{$rec->org_curators??'-нет-'}}</td>

                                        </tr>
                                        <?php
                                        $totSum += $rec->org_saldo;
                                        ?>
                                    @endforeach

                                    @if(1==1)
                                        <tr>
                                            <td colspan="1" class="text-right">Всего:</td>
                                            <td class="text-right font-weight-bold">{{number_format($totSum,2)}}</td>
                                            <td></td>
                                        </tr>
                                    @endif
                                    </tbody>
                                    <tfoot>
                                </table>
                            @else
                                <div class="text-center text-success">У "{{$ownorg->name}}" нет клиентов с ненулевым
                                    сальдо.
                                </div>
                            @endif

                            @if(count($ownorg->sup_recs)>0)
                                <?php
                                $recs = $ownorg->sup_recs;
                                ?>
                                <table class="table table-sm table-striped rep-data mt-0"
                                       style="background-color: snow; font-size:16px; max-width:960px"
                                       align=center>
                                    <thead>
                                    <tr class="text-left small" valign="top">
                                        {{--                            <td class="text-center">Дата</td>--}}
                                        <td class="text-left">Поставщик</td>
                                        <td class="text-right" title="Сумма долга или переплаты">Сальдо, руб
                                        </td>
                                        <td class="text-left">Контролирующий менеджер</td>
                                    </tr>

                                    </thead>
                                    <tbody>
                                    <?php
                                    $npp = 0;
                                    $totSum = 0;
                                    ?>

                                    @foreach($recs as $rec)
                                        <?php
                                        $td_class = ($rec->org_saldo < 0) ? 'text-danger' : (($rec->org_saldo > 0) ? 'text-success' : '');
                                        ?>
                                        <tr class="text-left ">
                                            <td class="text-left small">
                                                <a href="{{route('reports.rep48',[$ownorgid,$rec->orgid])}}?returl={{$retURL}}"
                                                   class="text-decoration-none">{{$rec->orgname}}</a>
                                            </td>
                                            <td class="text-right {{$td_class}}">{{number_format($rec->org_saldo,2)}}
                                            <td class="small">{{$rec->org_curators??'-нет-'}}</td>

                                        </tr>
                                        <?php
                                        $totSum += $rec->org_saldo;
                                        ?>
                                    @endforeach

                                    @if(1==1)
                                        <tr>
                                            <td colspan="1" class="text-right">Всего:</td>
                                            <td class="text-right font-weight-bold">{{number_format($totSum,2)}}</td>
                                            <td></td>
                                        </tr>
                                    @endif
                                    </tbody>
                                    <tfoot>
                                </table>
                            @else
                                <div class="text-center text-success">У "{{$ownorg->name}}" нет поставщиков с ненулевым
                                    сальдо.
                                </div>
                            @endif

                        </div>
                        @php($active = "")
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif


