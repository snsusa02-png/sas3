<div class="card mt-3">
	<div class="card-header">
		<a name="importgroups"/>
		Группы импорта данных "<b>{{ $itmtype->name }}</b>"

		<a href="{{ route('importgroups.create',$itmtype->id)}}"
		   class="btn btn-warning btn-sm ml-2 float-right">
			<i class="fa fa-plus"></i>
		</a>
	</div>
	@if(isset($itmtype->importgroups) and $itmtype->importgroups->count()>0)
		<div class="card-body">

			<table class="table-striped small" style="width: 100%;">
				<thead>
				<tr>
					<td>#</td>
					<td>Наименование</td>
					<td>
					<td>
				</tr>
				</thead>
				<tbody>
				@foreach($itmtype->importgroups as $itm)
                    <?php
                    $tr_class = ($itm->active) ? '' : 'not_active';
                    ?>
					<tr class="{{$tr_class}}">
						<td style="text-align: right;" class="small ">{{$loop->iteration}}</td>
						<td>&nbsp;{{$itm->name}}</td>
						<td style="text-align: right;">
							<a href="{{ route('importgroups.edit',$itm->id)}}"
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
