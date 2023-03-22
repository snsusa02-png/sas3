<div class="card mt-3">
	<div class="card-header">
		Специальные свойства категории "<b>{{ $itmtype->name }}</b>"

		<a href="{{ route('it_si_link.create',$itmtype->id)}}"
		   class="btn btn-warning btn-sm float-right">
			<i class="fa fa-plus"></i>
		</a>
	</div>
	@if(isset($spectype) and $spectype->count()>0)
		<div class="card-body">
			<table class="table-striped small" style="width: 100%;">
				<thead>
				<tr>
					<td>#</td>
					<td>Наименование</td>
					<td style="text-align: right;">
					<td>
				</tr>
				</thead>
				<tbody>
				@foreach($spectype as $itm)
					<tr>
						<td style="text-align: right;" class="small">{{$loop->iteration}}</td>
						<td>&nbsp;{{$itm->name}}</td>
						<td style="text-align: right;">
							<a href="{{ route('it_si_link.edit',$itm->id)}}"
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
