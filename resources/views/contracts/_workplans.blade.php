@if($rec->id != -1 and isset($rec->contract_workplans) )
    <?php
    $TotPaySum = 0;
    ?>
	<div class="card mt-3">
		<div class="card-header" style="background-color: #fff1db;">

			<a name="contract_workplans"></a>

			<span data-toggle="collapse" data-target="#childs">
				<i class="fa fa-gears text-info" aria-hidden="true"></i> Планы производства работ</span>

			<div class="float-right">
				@if (count($rec->contract_workplans)>0)
					<button data-toggle="collapse" data-target="#contract_workplans"
							class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
				@endif
				@if( $usrrights['contract_workplans.create']??false)
					<a href="{{ route('contract_workplans.create',['contractid'=>$rec->id])}}?returl={{Request::url()}}"
					   class="btn btn-warning btn-sm ">
						<i class="fa fa-plus"></i>
					</a>
				@endif
			</div>

		</div>
		@if (count($rec->contract_workplans)>0)
			<div class="card-body collapse show" id="contract_workplans">

				<table class="table-striped table-bordered0 p-1" style="width: 100%;">
					<thead>
					<tr class="text-center align-middle small">
						<td>#</td>
						<td class="text-left">Вид работ</td>
						<td class="text-left">Период работ</td>
						<td style="width:32px">
					</tr>
					</thead>
					<tbody>
                    <?php
                    $npp = 0;
                    ?>
					@foreach($rec->contract_workplans as $itm)
                        <?php
                        $npp++;
                        ?>
						<tr class="align-top ">
							<td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-left " style="">
                                <a href="{{ route('contract_workplans.edit',['id'=>$itm->id])}}?returl={{Request::url()}}">{{$itm->buildopertypename}}</a>
                                <div>{{$itm->name}}</div>
                            </td>
							<td class="text-left small" style="">
								{{date_create($itm->min_begdt)->format('d.m.Y H:i')}}
                                - {{date_create($itm->max_enddt)->format('d.m.Y H:i')}}
							</td>

							<td class="text-right">
								<a href="{{ route('contract_workplans.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
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

		@if (count($rec->contract_workplans)>0)
			<div class="card-footer">
				<div class="small text-right"> период работ: ...</div>
			</div>
		@endif

	</div>
@endif
