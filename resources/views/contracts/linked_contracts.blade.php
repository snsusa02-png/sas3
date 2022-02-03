{{--@dd($rec->linked_contracts)--}}
@if($rec->id != -1 and isset($rec->linked_contracts))
    <?php
    $TotPaySum = 0;
    ?>

    <div class="card mt-3">
        <div class="card-header" style="background-color: #ddffff;">
            <i class="fa fa-link fa-spin0" aria-hidden="true"></i> Связанные договоры
            <div class="float-right">
                @if(count($rec->linked_contracts)>0)
                    <button data-toggle="collapse" data-target="#linked_contracts"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->budgets)}}</span>
                    </button>
                @endif
            </div>
        </div>
        @if (count($rec->linked_contracts)>0)
            <div class="card-body collapse" id="linked_contracts">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-left">Описание</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    ?>
                    @foreach($rec->linked_contracts as $itm)
                        <?php
                        $npp++;

                        $linestyle = "";
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-left " style="{{$linestyle}}">
                                <a href="{{ route('contracts.edit',['id'=>$itm->lnkobjid])}}?returl={{Request::url()}}"
                                   title="Перейти к записи">
                                    {{$itm->name}}
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
