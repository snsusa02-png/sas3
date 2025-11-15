@if($rec->id <> -1)
    <?php
    $sysobjid = 101;
    ?>
	<div class="card mt-3">
		<div class="card-header">
			Используется в организациях
			<a href="{{ route('org_charges.create',['parid'=>0,])}}?chargetypeid={{$rec->id}}&returl={{Request::url()}}"
			   class="btn btn-warning btn-sm ml-2 float-right">
				<i class="fa fa-plus"></i>
			</a>
		</div>

		@if(isset($rec->charge_orgs) and $rec->charge_orgs->count()>0)
			<div class="card-body">
				<table class="table-striped small p-2" style="width: 100%;" cellpadding="2">
					<thead>
					<tr>
						<td>#</td>
						<td>Организация</td>
						<td/>
					</tr>
					</thead>
					<tbody>
					@foreach($rec->charge_orgs as $itm)
						<tr>
							<td style="text-align: right;"
								class="small">{{$loop->iteration}}</td>
							<td>{{$itm->orgname}}</td>
							<td class="text-right">
								<a href="{{ route('org_charges.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
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
