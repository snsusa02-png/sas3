@if($machine->id != -1 )
    <?php
    $TotPaySum = 0;
    ?>

	<div class="card mt-3">
		<div class="card-header" style="background-color: lightyellow;">
			<span data-toggle="collapse" data-target="#control_orgs" style="cursor:pointer;">Под управлением</span>
			<button data-toggle="collapse" data-target="#control_orgs"
					class="btn btn-light btn-sm float-right"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
		</div>
		@if (count($machine->controrgs)>0)
			<div class="card-body collapse" id="control_orgs">
				<table class="table-striped " style="width: 100%;">
					<thead>
					<tr class="text-center align-middle small">
						<td>#</td>
						<td class="text-center">Период</td>
            <td class="text-center">Арендатор</td>
						<td class="text-left">Основание</td>
						<td/>
					</tr>
					</thead>
					<tbody>
                    <?php
                    $npp = 0;
                    ?>
					@foreach($machine->controrgs as $itm)
                        <?php
                        $npp++;

                        $lineclass = "";
                        ?>
						<tr class="align-top ">
							<td class="small text-right">{{$loop->iteration}}</td>
              <td class="text-left small{{$lineclass}}" nowrap style="">
                {{date_format(date_create($itm->begdate),'d.m.Y')}} - {{isset($itm->enddate)?date_format(date_create($itm->enddate),'d.m.Y'):'...'}}

							</td>
              <td class="text-center  {{$lineclass}}">
								{{$itm->orgname}}
							</td>
							<td class="text-left small {{$lineclass}}">
                @if(isset($itm->contractid))
								        <a href="{{ route('contracts.edit',['id'=>$itm->contractid])}}?returl={{Request::url()}}">дог. №{{$itm->docnum}} от {{$itm->docdate}}</a>
                @endif
                <div>{{$itm->descript}}</div>
							</td>
							<td class="text-right">
                @if(1==1)
								<a href="{{ route('mchncontrorgs.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
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
