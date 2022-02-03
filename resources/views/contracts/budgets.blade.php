@if($rec->id != -1 )
    <?php
    $TotPaySum = 0;
    ?>

	<div class="card mt-3">
		<div class="card-header" style="background-color: #ddffef;">
			<i class="fa fa-money" aria-hidden="true"></i> Бюджет
			<div class="float-right">
				@if(count($rec->budgets)>0)
					<button data-toggle="collapse" data-target="#budgets"
							class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i>
						<span class="badge badge-info">{{count($rec->budgets)}}</span>
					</button>
				@endif
			</div>
		</div>
		@if (count($rec->budgets)>0)
			<div class="card-body collapse" id="budgets">
				<table class="table-striped " style="width: 100%;">
					<thead>
					<tr class="text-center align-middle small">
						<td>#</td>
						<td class="text-center">Название</td>
						<td class="text-center">Сметная стоимость, руб</td>
						<td/>
					</tr>
					</thead>
					<tbody>
                    <?php
                    $npp = 0;
                    ?>
					@foreach($rec->budgets as $itm)
                        <?php
                        $npp++;

                        $linestyle = "";
                        if ($itm->active == 0) {
                            $linestyle = "background-color:lightsalmon;";
                        }
                        ?>
						<tr class="align-top ">
							<td class="small text-right">{{$loop->iteration}}</td>
							<td class="text-right small" style="{{$linestyle}}">
								{{$itm->name}}:
							</td>
							<td class="text-center" style="{{$linestyle}}">
								{{number_format($itm->estdocsum,2)}}
							</td>
							<td class="text-right">
								<a href="{{ route('budgets.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
								   class="btn btn-sm btn-primary"
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
	</div>
@endif
