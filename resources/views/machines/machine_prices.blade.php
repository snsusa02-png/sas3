@if($machine->id != -1 )
    <?php
    $TotPaySum = 0;
    ?>

	<div class="card mt-3">
		<div class="card-header" style="background-color: #dae3ff;">

      <span data-toggle="collapse" data-target="#prices" style="cursor:pointer;">Расценки</span>
			<button data-toggle="collapse" data-target="#prices"
					class="btn btn-light btn-sm float-right"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
		</div>
		@if (count($machine->prices)>0)
			<div class="card-body collapse" id="prices">
				<table class="table-striped " style="width: 100%;">
					<thead>
					<tr class="text-center align-middle small">
						<td>#</td>
						<td class="text-center">Цена, руб</td>
            <td class="text-center">за ЕИ</td>
						<td class="text-left">Основание</td>
						<td/>
					</tr>
					</thead>
					<tbody>
                    <?php
                    $npp = 0;
                    ?>
					@foreach($machine->prices as $itm)
                        <?php
                        $npp++;

                        $lineclass = "";
                        ?>
						<tr class="align-top ">
							<td class="small text-right">{{$loop->iteration}}</td>
              <td class="text-right {{$lineclass}}" nowrap style="">
								{{number_format($itm->price,2)}}
							</td>
              <td class="text-center small {{$lineclass}}">
								{{$itm->unitcode}}
							</td>
							<td class="text-left small {{$lineclass}}">
								<a href="{{ route('contracts.edit',['id'=>$itm->contractid])}}?returl={{Request::url()}}">дог. №{{$itm->docnum}} от {{$itm->docdate}}</a>
                <div>{{$itm->name}}</div>
							</td>
							<td class="text-right">
                @if(1==0)
								<a href="{{ route('contract_prices.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
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
			</div>
		@endif
	</div>
@endif
