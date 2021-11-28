@if (1==0 and isset($ri_altnames) and $usrrights['ri_altnames.read'])
	<div class="row">

		<div class="col-md-12">
			<div class="card mb-3">
				<div class="card-header">
					Альтернативные названия

					@if($usrrights['ri_altnames.create'])
						<a href="{{ route('ri_altnames.create',$refitem->id)}}"
						   class="btn btn-warning btn-sm"
						   style="margin-left:16px;float: right;">
							<i class="fa fa-plus"></i>
						</a>
					@endif

				</div>
				@if ($ri_altnames->count()>0)
					<table class="table-striped ">
						<thead>
						<tr>
							<td>#</td>
							<td>Название</td>
							<td/>
						</tr>
						</thead>
						<tbody>
						@foreach($ri_altnames as $itm)
							<tr>
								<td style="text-align: right;"
									class="small">{{$loop->iteration}}</td>

								<td class="text-left">&nbsp;{{$itm->name}}</td>
								<td style="text-align: right;">
									<a href="{{ route('ri_altnames.edit',$itm->id)}}"
									   class="btn btn-sm btn-primary">
										<i class="fa fa-pencil">
										</i>
									</a>
								<td>
							</tr>
						@endforeach
						</tbody>
					</table>
				@endif
			</div>
		</div>
	</div>
@endif
