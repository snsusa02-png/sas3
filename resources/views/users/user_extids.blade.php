@if($usrrights['objextids.read'])
	<div class="card mt-3">
		<div class="card-header">
			Идентификаторы пользователя во внешних системах
			@if($usrrights['objextids.create']??false)
				<a href="{{ route('objextids.create',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,])}}"
				   class="btn btn-warning btn-sm" style="float: right">
					<i class="fa fa-plus"></i>
				</a>
			@endif
		</div>

		@if(isset($rec->extids) and $rec->extids->count()>0)
			<table class="table-striped small p-2" style="width: 100%;" cellpadding="2">
				<thead>
				<tr>
					<td>#</td>
					<td>Система</td>
					<td>ID</td>
					<td/>
				</tr>
				</thead>
				<tbody>
				@foreach($rec->extids as $itm)
					<tr>
						<td style="text-align: right;"
							class="small">{{$loop->iteration}}</td>
						<td>{{$itm->extsysname}}</td>
						<td>"<b>{{$itm->extid}}</b>"</td>
						<td style="text-align: right;">
							<a href="{{ route('objextids.edit',$itm->id)}}"
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
@endif
