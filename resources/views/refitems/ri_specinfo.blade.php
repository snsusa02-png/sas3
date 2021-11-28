@if( 1==1 and isset($refitem) and ($refitem->id!=-1))
	<div class="card mt-3">

		<div class="card-header">
			Специальные характеристики
			<a href="{{ route('ri_specinfo.create',$refitem->id)}}"
			   class="btn btn-warning btn-sm"
			   style="margin-left:16px;float: right;">
				<i class="fa fa-plus"></i>
			</a>
		</div>

		@if ($specinfos->count()>0)
			<div class="card-body">
				<table class="table-striped small" style="width: 100%;">
					<thead>
					<tr>
						<td>#</td>
						<td>Характеристика</td>
						<td>Значение</td>
						<td/>
					</tr>
					</thead>
					<tbody>
					@foreach($specinfos as $itm)
						<tr>
							<td style="text-align: right;"
								class="small">{{$loop->iteration}}</td>

							<td class="text-right">&nbsp;{{$itm->st_name}}</td>
							<td class="text-center">&nbsp;{{$itm->specinfovalue}}</td>

							<td style="text-align: right;">
								<a href="{{ route('ri_specinfo.edit',$itm->id)}}"
								   class="btn btn-sm btn-primary">
									<i class="fa fa-pencil">
									</i>
								</a>
							<td>
						</tr>
					@endforeach
					</tbody>
				</table>
			</div>
		@endif
	</div>
@endif
