@if( 1==1 and isset($refitem) and ($refitem->id!=-1) and isset($saleactions) and $saleactions->count()>0)
	<div class="card mb-3">
		<div class="card-header">
			Участие в акциях

			@if($usrrights['ri_altnames.create'])
				<a href="{{ route('ri_altnames.create',$refitem->id)}}"
				   class="btn btn-warning btn-sm"
				   style="margin-left:16px;float: right;">
					<i class="fa fa-plus"></i>
				</a>
			@endif

		</div>
		@if ($saleactions->count()>0)
			<table class="table-striped ">
				<thead>
				<tr valign="top" style="color:gray;">
					<td>#</td>
					<td>Акция,
						<div class="small">период проведения</div>
					</td>
					<td>Цена, &#x20bd;</td>
					<td>Скидка, %</td>
				</tr>
				</thead>
				<tbody>
				@foreach($saleactions as $itm)
                    <?php
                    $dscnt = "";
                    if ($refitem->price > 0) {
                        $dscnt = round(100 * ($refitem->price - $itm->price) / $refitem->price, 2);
                    }
                    ?>
					<tr valign="top">
						<td style="text-align: right;"
							class="small">{{$loop->iteration}}</td>

						<td class="text-left">
							<a href="{{ route('saleactions.edit', $itm->id)}}">
								{{$itm->name}}
							</a>
							<div class="text-center small">
								{{$itm->begdt}} - {{$itm->enddt}}
							</div>
						</td>
						<td class="text-right">{{$itm->price}}</td>
						<td class="text-right">{{$dscnt}}</td>
					</tr>
				@endforeach
				</tbody>
			</table>
		@endif
	</div>
@endif
