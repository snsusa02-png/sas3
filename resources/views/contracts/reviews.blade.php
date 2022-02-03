@if($rec->id != -1 and isset($rec->contract_reviews) )

    <div class="card mt-3">
        <div class="card-header" style="background-color: #f5e476;">

            <a name="reviews"></a>

            <span data-toggle="collapse" data-target="#childs">
				<i class="fa fa-comments" aria-hidden="true"></i> Согласования</span>

            <div class="float-right">
                @if (count($rec->contract_reviews)>0)
                    <button data-toggle="collapse" data-target="#reviews"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if( $usrrights['contract_reviews.create']??false)
                    <a href="{{ route('contract_reviews.create',['contractid'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-warning btn-sm ">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>

        </div>
        @if (count($rec->contract_reviews)>0)
            <div class="card-body collapse show" id="reviews">

                <table class="table-striped table-bordered0 p-1" style="width: 100%;">
                    <thead>
                    <tr class="text-center small" valign="top">
                        <td>#</td>
                        <td class="text-center">Начало</td>
                        <td class="text-center">Завершение</td>
                        <td class="text-left">Цель</td>
                        <td class="text-center">Статус</td>
                        <td style="width:32px">
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $statuses = \App\contract_review::statuses();
                    ?>
                    @foreach($rec->contract_reviews as $itm)

                        <tr class="align-top ">
                            <td class="small text-right">{{$npp++}}</td>
                            <td class="text-center small" style="">
                                @if(isset($itm->plnbegdt))
                                    {{date_create($itm->plnbegdt)->format('d.m.Y H:i')}}
                                @endif
                            </td>
                            <td class="text-center " style="">
                                @if(isset($itm->plnenddt))
                                    {{date_create($itm->plnenddt)->format('d.m.Y H:i')}}
                                @endif
                            </td>
                            <td class="text-left small" style="">
                                {{$itm->descript}}
                            </td>
                            <td class="text-center small" style="">
                                {{$statuses[$itm->statusid]??''}}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('contract_reviews.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   class="btn btn-sm btn-light"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (count($rec->contract_reviews)>0)
            <div class="card-footer">
                <span>
                    <a class="btn btn-close btn-info ml-3 btn-sm "
                       href="{{ route('contract_reviews.print_1', $rec->id) }}"
                       target="_blank"
                       title="Напечатать">
                        <i class="fa fa-print" aria-hidden="true"></i>
                    </a>
                </span>
            </div>
        @endif

    </div>
@endif
