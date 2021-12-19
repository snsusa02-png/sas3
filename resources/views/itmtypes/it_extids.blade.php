@if($rec->id <> -1)
    <?php
    $sysobjid = 101;
    ?>
	<div class="card mt-3">
		<div class="card-header">
			Идентификаторы во внешних системах
			<a href="{{ route('objextids.create',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,])}}?returl={{Request::url()}}"
			   class="btn btn-warning btn-sm ml-2 float-right">
				<i class="fa fa-plus"></i>
			</a>
		</div>

		@if(isset($rec->extids) and $rec->extids->count()>0)
			<div class="card-body">
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
							<td class="text-right">
								<a href="{{ route('objextids.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
								   class="btn btn-sm btn-primary ">
									<i class="fa fa-pencil"></i>
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
