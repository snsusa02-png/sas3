@if (1==1 and isset($rec->userorgs) and $usrrights['userorgs.read'])
	<div class="card mt-3">
		<div class="card-header">
			Представляет организации

			@if($usrrights['userorgs.create'])
				<a href="{{ route('userorgs.create',$rec->id)}}"
				   class="btn btn-warning btn-sm"
				   style="margin-left:16px;float: right;">
					<i class="fa fa-plus"></i>
				</a>
			@endif

		</div>
		@if ($rec->userorgs->count()>0)
			<div class="card-body">
				<table class="table-striped w-100">
					<thead>
					<tr class="small">
						<td>#</td>
						<td>Организация(клиент)</td>
						<td>Полномочия</td>
						<td class="text-center">Период</td>
						<td/>
					</tr>
					</thead>
					<tbody>
                    <?php
                    $bgcols = array('#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC', '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7');
                    ?>
					@foreach($rec->userorgs as $itm)
                        <?php
                        $rec0 = 0;
                        $colshift = (1 - $itm->active) * 2;
                        $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];
                        ?>
						<tr style="background-color: {{$tr_bg_col}}">
							<td style="text-align: right;"
								class="small">{{$loop->iteration}}</td>

							<td class="text-left">&nbsp;{{$itm->orgname}}</td>
							<td class="text-center small">{{$itm->postname}} {{($itm->curator==1)?'куратор':''}}</td>
							<td class="text-center small">{{$itm->begdt}} - {{$itm->enddt??'...'}}</td>
							<td style="text-align: right;">
								<a href="{{ route('userorgs.edit',$itm->id)}}"
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
