@if($rec->id != -1 and isset($rec->units) )
	<div class="card mt-3">
		<div class="card-header" style="background-color: #a0fff7;">

			<a name="units"></a>

			<span data-toggle="collapse" data-target="#childs">
				<i class="fa fa-random text-info" aria-hidden="true" style="color: darkorange"></i> Альтернативные ЕИ</span>

			<div class="float-right">
				@if (count($rec->units)>0)
					<button data-toggle="collapse" data-target="#units"
							class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
				@endif
                    @if($usrrights['ri_units.create'])
                        <a href="{{ route('ri_units.create',$rec->id)}}"
                           class="btn btn-warning btn-sm">
                            <i class="fa fa-plus"></i>
                        </a>
                    @endif

            </div>

		</div>
		@if (count($rec->units)>0)
			<div class="card-body collapse show" id="units">

				<table class="table-striped table-bordered0 p-1" style="width: 100%;">
					<thead>
					<tr class="text-center align-middle small">
						<td>#</td>
						<td class="text-center">ЕИ</td>
						<td class="text-center">Пересчет в {{$rec->unittype->name}}</td>
						<td style="width:32px">
					</tr>
					</thead>
					<tbody>
                    <?php
                    $npp = 0;
                    ?>
					@foreach($rec->units as $itm)
                        <?php
                        $npp++;
                        ?>
						<tr class="align-top ">
							<td class="small text-right">{{$loop->iteration}}</td>
							<td class="text-center" style="">
								<a href="{{ route('ri_units.edit',['id'=>$itm->id])}}?returl={{Request::url()}}">
									<b>{{$itm->unittypename}}</b>
                                </a>
							</td>
							<td class="text-center small" style="">
                                1 {{$itm->unittypename}} = <b>{{rtrim($itm->k2ref_unit,'0')}}</b> {{$rec->unittype->name}}
							</td>
							<td class="text-right">
								<a href="{{ route('ri_units.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
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

		@if (count($rec->units)>0)
			<div class="card-footer">
			</div>
		@endif

	</div>
@endif
