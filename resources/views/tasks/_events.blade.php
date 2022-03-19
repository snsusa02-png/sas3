@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->events))
	<div class="card mt-3 d-none d-sm-block">
		<div class="card-header">
			<span data-toggle="collapse" data-target="#obj_events"><i class="fa fa-events-o" aria-hidden="true"></i> События</span>

			<div class="float-right">
				@if (count($rec->events)>0)
					<button data-toggle="collapse" data-target="#obj_events"
							class="btn btn-light btn-sm "><i class="fa fa-eye-slash" aria-hidden="true"></i>
						<span class="badge badge-info">{{count($rec->events)}}</span>
					</button>
				@endif

				@if( $usrrights['save'] or $usrrights['events.create']??false)
					@if(1==1)
						<a href="{{ route('events.create',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'retroute'=>route('events.edit',7)]).'?returl=123_456'}}"
						   class="btn btn-warning btn-sm ">
							<i class="fa fa-plus"></i>
						</a>
					@endif
				@endif
			</div>
		</div>

		@if (count($rec->events)>0)

			<div class="card-body collapse" id="obj_events">
				<table class="table-condensed table-striped small" style="width: 100%;">
					<thead>
					<tr>
						<td>#</td>
						<td>Когда</td>
						<td>Название, описание</td>
						<td/>
					</tr>
					</thead>
					<tbody>
					@php($toteventsize=0)
					@foreach($rec->events as $itm)
                        <?php
                        ?>
						<tr class="align-top">
							<td class="small">{{$loop->iteration}}</td>
							<td>
							</td>
							<td class="text-left">
								<div>{{$itm->title}}</div>
								<span class="font-weight-bold">{{$itm->descript}}</span>
							</td>
							<td class="text-right">
								<a href="{{ route('events.edit',$itm->id)}}?returl={{Request::url()}}"
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
			<div class="card-footer">
				<div class="small text-right"> всего: {{count($rec->events)}},
				</div>
			</div>
		@endif
	</div>
@endif
