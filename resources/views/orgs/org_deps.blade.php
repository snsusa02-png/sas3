<style>
    label {
        color: gray;
        margin-bottom: 0px;
    }
</style>
@if ($rec->id != -1 and $usrrights['orgdeps.read']??false)
    <div class="card mt-3">
        <div class="card-header">
            <a name="orgdeps"/>Подразделения

            <div class="float-right">
                @if (isset($rec->deps) and count($rec->deps)>0)
                    <button data-toggle="collapse" data-target="#orgdeps"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif

                @if($usrrights['orgdeps.create']??false)
                    <a href="{{ route('orgdeps.create',$rec->id)}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body collapse" id="orgdeps">

            @if(isset($rec->deps) and count($rec->deps)>0)
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>Подразделение</td>
                        <td>Руководитель</td>
                        <td style="text-align: center;">
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rec->deps as $itm)
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
                            <td>{{$itm->name}}</td>
                            <td>{{$itm->mngr_name}}</td>
                            <td style="text-align: center;">
                                <a href="{{ route('orgdeps.edit',$itm->id)}}?returl={{Request::url()}}"
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

