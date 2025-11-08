
<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 56;
$retURL = $data->returl ?? route('paydocs.index');

$report = \App\report::find($thisObjId);

if (!isset($report))
    return redirect($retURL);

$thisTitle = $report->title ?? $report->name;
$action_url = route('reports.rep' . $thisObjId);

$userid = \Auth::user()->id;
$usrrights = [];
$usrrights['link_tasks'] = false; //\App\usrsysright::isUserHasRightByCode_cached($userid, 'tasks.create');

$cols_count = 0;
$first_col_id = null;
?>
@if (isset($recs))
    <style type="text/css">
        .textright { font-weight: bold; mso-number-format:"\@"; text-align: right; float:right; display:block}
    </style>
    <table id="results">
            <thead>
            <tr style="vertical-align:middle;">
                <td class="text-right " width="5">№п/п</td>
                <td>ФИО</td>
                <td>Подразделение, организация</td>

                @foreach($data->cols as $col)
                    <?php
                    if (is_null($first_col_id))
                        $first_col_id = $col->id;

                    $cols_count++;
                    ?>
                    <td id="col_{{$col->id}}">{{$col->name}}</td>
                @endforeach
                <td class="textright">ИТОГО</td>
            </tr>
            </thead>

            <tbody>
            <?php
            $npp = 0;
            $totSum = $totInpSum = $totOutSum = 0;
            $cur_orgid = -1;
            $cur_dep_name = '-1';
            $cur_staffid = -1;

            $line_sum = [];
            $line_notes = [];
            ?>
            @foreach($recs as $rec)
                <?php

                $tr_class = "";
                $td_class = "";
                $tdс_class = "";

                //$tstyle = ($rec->inp_qty + $rec->out_qty > 0) ? 'background-color:#ffff94' : '';
                $tstyle = '';
                ?>

                @if($rec->staffid <> $cur_staffid)

                    @if( $cur_staffid <> -1 )
                        <?php
                        foreach ($data->cols as $tcol) {
                            $sum = (is_null($line_sum[$tcol->id])) ? '' : $line_sum[$tcol->id];
                            //echo('<td x:num width="15">' . $sum . '</td>');

                            echo('<td x:num width="15">' . $sum );
                            if (!is_null($line_notes[$tcol->id])){
                                echo('<br><div class="float-right small text-secondary">' . $line_notes[$tcol->id] . '</div>');
                            }
                            echo( '</td>');
                        }
                        echo('<td x:num width="12">' . $totOutSum . '</td>');
                        echo('</tr>');
                        ?>
                    @endif

                    @if(1==0 and $rec->orgid <> $cur_orgid)
                        <tr>
                            <td colspan={{3+$cols_count}}>{{$rec->org_name}}</td>
                        </tr>
                        <?php
                        $cur_orgid = $rec->orgid;
                        $cur_dep_name = '-1';
                        ?>
                    @endif

                    @if(1==0 and $rec->dep_name <> $cur_dep_name)
                        <tr>
                            <td colspan="{{3+$cols_count}}">
                                Подразделение:
                                <b>{{(trim($rec->dep_name)=='')?'-не указано-':$rec->dep_name}}</b></td>
                        </tr>
                        @php($cur_dep_name = $rec->dep_name)
                    @endif
                    <?php
                    $cur_staffid = $rec->staffid;
                    $pre_chargetypeid = $first_col_id;
                    //echo('<hr>');var_dump('$pre_chargetypeid =', $pre_chargetypeid);
                    ?>
                    <tr class="text-left {{$tr_class}}" style="{{$tstyle}}">
                        <td x:num width="5">
                            {{++$npp}}
                        </td>
                        <td width="30">
                            {{$rec->lname}} {{$rec->fname}} {{$rec->mname}}
                        </td>
                        <td width="30">
                            {{$rec->dep_name}}<br>{{$rec->org_name}}
                        </td>
                    <?php
                    // Заголовки колонок -----------
                    foreach ($data->cols as $tcol) {
                        $line_sum[$tcol->id] = null;
                        $line_notes[$tcol->id] = null;
                    }
                    $totOutSum = 0;
                    ?>
                @endif

                <?php
                $totOutSum += ($rec->dir * $rec->charge_sum);
                $totSum += ($rec->dir * $rec->charge_sum);

                $line_sum[$rec->chargetypeid] = $rec->dir * $rec->charge_sum;
                $line_notes[$rec->chargetypeid] = $rec->notes;
                ?>
            @endforeach

            @if( $cur_staffid <> -1 )
                <?php
                //--Вывод сумм по сотруднику--
                foreach ($data->cols as $tcol) {
                    $sum = (is_null($line_sum[$tcol->id])) ? '' : $line_sum[$tcol->id];
                    //echo('<td x:num width="15">' . $sum . '</td>');

                    echo('<td x:num width="15">' . $sum );
                    if (!is_null($line_notes[$tcol->id])){
                        echo('<br><div class="float-right small text-secondary">' . $line_notes[$tcol->id] . '</div>');
                    }
                    echo( '</td>');
                }
                echo('<td x:num width="12">' . $totOutSum . '</td>');
                echo('</tr>');
                ?>
            @endif

            @if(1==1)
                <tr>
                    <td colspan="{{3+$cols_count}}" class="textright">Всего:</td>
                    <td x:num width="12">{{$totSum}}</td>
                </tr>
            @endif
            </tbody>
            <tfoot>
        </table>
@endif
