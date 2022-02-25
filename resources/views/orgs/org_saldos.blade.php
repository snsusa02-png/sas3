{{--@if (isset($rec) and $rec->id != -1 and in_array($rec->kindid,[1,2]) and isset($rec->org_saldos))--}}
@if (isset($rec) and $rec->id != -1 and isset($rec->org_saldos))
    <style>
        label {
            color: gray;
            margin-bottom: 0px;
        }

    </style>
    <?php
    $retURL = Request::url();
    ?>

    <div class="card mt-3">
        <div class="card-header">
            <i class="fa fa-balance-scale text-info" aria-hidden="true"></i>
            Сальдо с контрагентами

            <div class="float-right">
                @if (isset($rec->org_saldos) and count($rec->org_saldos)>0)
                    <button data-toggle="collapse" data-target="#org_saldos"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif

                @if($usrrights['org_saldos.create']??false)
                    <a href="{{ route('org_saldos.create',['orgid'=>$rec->id,'ownorgid'=>\Auth::user()->curorgid ?? 0])}}?returl={{$retURL}}"
                       class="btn btn-sm btn-warning"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body collapse show" id="org_saldos">
            @if(count($rec->org_saldos)>0)
                <table class="table table-striped">
                    <thead>
                    <tr class="small">
                        <td>Контрагент</td>
                        <td>Сальдо, &#x20bd;</td>
                        <td>на начало</td>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rec->org_saldos as $itm)
                        <?php
                        $saldo = $itm->saldo;
                        $saldo_style = ($saldo > 0) ? 'color:green' : 'color:red';
                        ?>
                        <tr>
                            <td class="small">
                                {{$itm->ownorg_name}}
                            </td>
                            <td class="text-right" style="{{$saldo_style}}">
                                <b>{{number_format($itm->saldo,2)}}</b>
                                <div class="ml-1">
                                    <a href="{{route('reports.rep48',['ownorgid'=>$itm->ownorgid,'orgid'=>$rec->id])}}?returl={{$retURL}}">
                                        {{number_format($itm->saldo+$itm->opersum,2)}}</a>
                                </div>
                            </td>
                            <td class=" text-center">
                                {{date_create($itm->ondate)->format('d.m.Y')}}
                                <div class="ml-1">
                                {{date_create($itm->max_operdate)->format('d.m.Y')}}
                                </div>
                            </td>
                            <td>
                                @if($usrrights['org_saldos.update']??false)
                                    <a href="{{ route('org_saldos.edit',$itm->id)}}?returl={{$retURL}}"
                                       class="btn btn-sm btn-primary"
                                       title="Просмотреть/Изменить запись">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endif

