@if ($rec->id != -1 and in_array($rec->kindid,[1,2]) and $usrrights['org_places.read']??false)
    <div class="card mt-3">
        <div class="card-header">
            Офисы, склады контрагента

            <div class="float-right">
                @if (isset($rec->places) and count($rec->places)>0)
                    <button data-toggle="collapse" data-target="#orgplaces"
                            class="btn btn-light btn-sm">
                        <i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->places)}}</span>
                    </button>
                @endif
                @if($usrrights['org_places.create']??false)
                    <a href="{{ route('org_places.create',$rec->id)}}"
                       class="btn btn-sm btn-warning"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body collapse" id="orgplaces">

            @if(count($rec->places)>0)
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>Тип</td>
                        <td>Название, Адрес</td>
                        <td style="text-align: center;">
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $placetype = [1 => 'офис', 2 => 'склад'];
                    ?>
                    @foreach($rec->places as $itm)
                        <?php
                        $tr_itm_class = "itm_not_active";
                        if ($itm->active == 1) {
                            $tr_itm_class = "itm_active";
                        }
                        ?>
                        <tr class="{{$tr_itm_class}}">
                            <td class="small" style="text-align: right'">
                                {{--$local->count--}}
                            </td>
                            {{--                            <td>                               {{$itm->name}}                            </td>--}}
                            <td class="c">
                                {{$placetype[$itm->placetypeid]??''}}
                            </td>
                            <td class="l">
                                {{$itm->name}}
                                <div class="small ml-1"> {{$itm->address}}</div>
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('org_places.edit',$itm->id)}}"
                                   class="btn btn-sm btn-primary"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endif

