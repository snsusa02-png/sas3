@if ($rec->id != -1 and isset($rec->srs_hr_items) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-calculator text-info" aria-hidden="true"></i>
                    По-часовые ставки для схемы расчета ЗП

                    <span class="float-right">
                        @if(count($rec->srs_hr_items)>0)
                            <button data-toggle="collapse" data-target="#_salaries"
                                    class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->srs_hr_items)}}</span>
                        </button>
                        @endif
                        @if($usrrights['save']??true)
                            <a href="{{ route('srs_hr_items.create',['srs_id'=>$rec->id])}}"
                               class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->srs_hr_items)>0)
                    <div class="card-body collapse show" id="_salaries">
                        <table class="table table-sm table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td rowspan="2" class="text-center">Вид работ</td>
                                <td rowspan="2" class="text-center">Стаж работника, лет</td>
                                <td class="text-center" colspan="2">Ставка день, руб/час</td>
                                <td rowspan="2"></td>
                            </tr>
                            <tr>
                                <td class="text-center">День</td>
                                <td class="text-center">Ночь</td>
                            </TR>
                            </thead>
                            <tbody>
                            <?php
                            //id, srs_id, wrktypeid, min_wrkexp, max_wrkexp, hr_day_rate, hr_night_rate, created_at, created_by, updated_at, updated_by, wrktype_name
                            $cur_wwrktypeid = -1;
                            ?>
                            @foreach($rec->srs_hr_items as $itm)
                                @if ($itm->wrktypeid <> $cur_wwrktypeid)
                                    <?php
                                        $t_style = ($itm->wrktype_active==1)?'':'background-color:#ffebeb;';
                                    ?>
                                    <tr>
                                        <td colspan="5" style="{{$t_style}}" class="font-weight-bold font-italic">&nbsp;&nbsp;{{$itm->wrktype_name}}
                                            @if($usrrights['save']??true)
                                                &nbsp; <a href="{{ route('srs_hr_items.create',['srs_id'=>$rec->id])}}?wrktypeid={{$itm->wrktypeid}}"
                                                   class="btn btn-warning btn-sm ml-1">
                                                    <i class="fa fa-plus"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                    <?php
                                    $cur_wwrktypeid = $itm->wrktypeid;
                                    ?>
                                @endif
                                <tr>
                                    <td class="small text-center"></td>
                                    <td class="small text-center">
                                       >= {{$itm->min_wrkexp}} .. < {{$itm->max_wrkexp}}
                                    </td>
                                    <td class="text-center"> {{number_format($itm->hr_day_rate,2)}} </td>
                                    <td class="text-center"> {{number_format($itm->hr_night_rate,2)}} </td>
                                    <td class="text-right">
                                        <a href="{{ route('srs_hr_items.edit',$itm->id)}}"
                                           class="btn btn-sm btn-primary">
                                            <i class="fa fa-pencil">
                                            </i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
