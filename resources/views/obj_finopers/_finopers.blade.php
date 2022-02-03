@if ($rec->id != -1 and isset($rec->finopers) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-money text-info" aria-hidden="true"></i>
                    Финансовые транзакции

                    <span class="float-right">
                        <button data-toggle="collapse" data-target="#_finopers"
                                class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->finopers)}}</span>
                        </button>
                    </span>
                </div>

                @if (count($rec->finopers)>0)
                    <div class="card-body collapse" id="_finopers">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td class="text-right">Источник</td>
                                <td class="text-right">Сумма, &#8381;</td>
                                <td>Получатель</td>
                                <td>Описание</td>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sumtypes = \App\obj_finoper::types();
                            ?>
                            @foreach($rec->finopers as $itm)
                                <tr>
                                    <td style="text-align: right;"
                                        class="small">{{date_create($itm->operdate)->format('d.m.Y')}}</td>

                                    <td class="small text-right">{{$itm->srcorg_name}}
                                        <i class="fa fa-arrow-circle-right text-info" aria-hidden="true"></i>
                                    </td>
                                    <td class="text-right font-weight-bold">{{number_format($itm->opersum,2)}}</td>
                                    <td class="small">
                                        <i class="fa fa-arrow-circle-right text-info" aria-hidden="true"></i>
                                        {{$itm->tgtorg_name}}
                                    </td>
                                    <td class="text-left small">
                                        {{$itm->descript}}
                                        <span class="text-secondary"> {{$sumtypes[$itm->sumtypeid]}}</span>
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
