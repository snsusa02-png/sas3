@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->contract_prices))

	<div class="card mt-3">
		<div class="card-header" style="background-color: #f2ffca;">
			<i class="fa fa-rub text-success" aria-hidden="true"></i> Расценки
			<div class="float-right">
				@if(count($rec->contract_prices)>0)
					<button data-toggle="collapse" data-target="#contract_prices"
							class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i>
						<span class="badge badge-info">{{count($rec->contract_prices)}}</span>
					</button>
				@endif
				@if(1==1)
					<a href="{{ route('contract_prices.create',['sysobjid'=>$rec->id])}}?returl={{Request::url()}}"
					   class="btn btn-sm btn-warning"
					   title="Создать запись">
						<i class="fa fa-plus"></i>
					</a>
				@endif
			</div>
		</div>
		{{--		@if (count($rec->prices)>0)--}}
		@if (count($rec->contract_prices)>0)
			<div class="card-body collapse" id="contract_prices">
				<table class="table-striped " style="width: 100%;">
					<thead>
					<tr class="text-center align-middle small">
						<td>#</td>
						<td class="text-left">Объект</td>
						<td class="text-right">Цена, &#8381;/{{$rec->price_unit}}</td>
						<td/>
					</tr>
					</thead>
					<tbody>
                    <?php
                    $npp = 0;
                    ?>
					{{--					@foreach($rec->prices as $itm)--}}
					@foreach($rec->contract_prices as $itm)
                        <?php
                        $npp++;

                        $linestyle = "";
                        //                        if ($itm->active == 0) {
                        //                            $linestyle = "background-color:lightsalmon;";
                        //                        }
                        ?>
						<tr class="align-top ">
							<td class="small text-right">{{$loop->iteration}}</td>
							<td class="text-left small" style="{{$linestyle}}">
								{{--								{{$itm->sysobjid}}:{{$itm->objid}}--}}
								{{$itm->objname}}
							</td>
							<td class="text-right small" style="{{$linestyle}}">
								{{number_format($itm->price,2)}}
							</td>
							<td class="text-right">
								<a href="{{ route('contract_prices.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
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
