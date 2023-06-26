<div class="row ">
	<div class="col-md-12">
		<div class="card mt-3" style="padding:6px; min-width:660px;">
			<table class="table-striped "
				   style=" width: 100%;">
				<thead>
				<tr>
					<td colspan="6"><h6>&nbsp;Состав</h6></td>
					<td style="text-align: right;">
						@if ($usrrights['doclst.create'])
							<div class="btn-group">
								@if( $usrrights['doclst.create'])
									<a href="{{ route('ri_cmpnd_items.create',$rec->id)}}"
									   class="btn btn-warning btn-sm"
									   title="добавить позицию">
										<i class="fa fa-plus"></i>
									</a>
								@endif
							</div>
					@endif
					<td>
				</tr>
				@if (count($items)>0)
                    <?php
                    $TotQty = 0;
                    $TotDocSum = 0;
                    $TotGrossWeight_min = 0;
                    $TotGrossWeight_max = 0;
                    ?>
					<tr>
						<td rowspan="2">#</td>
						<td rowspan="2">Наименование материала</td>
                        <td rowspan="2">ЕИ</td>
						<td class="text-center" colspan="2" style="background-color: #fffbed">Количество</td>
						<td rowspan="2" class="text-right">Вес, кг</td>
						<td/>
					</tr>
                    <tr>
                        <td class="text-right">Минимум, ЕИ</td>
                        <td class="text-right">Максимум, ЕИ</td>
                    </tr>
				</thead>
				<tbody>

				@foreach($items as $itm)
					<tr class="align-top">
						<td class="small">{{$loop->iteration}}</td>
						<td>
							<a href="{{ route('refitems.edit',$itm->refitmid)}}">
								{{$itm->refitmid}}
								{{$itm->refitem->code}}
							</a>:
							<a href="{{ route('ri_cmpnd_items.edit',$itm->id)}}">
								<b>{{$itm->ri_name}}</b>
								<span class="small">{{$itm->it_name}}/{{$itm->st_name}}</span>
							</a>
						</td>
                        <Td class="text-center">{{$itm->ut_name}}</Td>
						<td class="text-right" nowrap>
							{{number_format($itm->min_qty,$itm->decimal_dgts)}}
						</td>
						<td class="text-right" nowrap>
							{{number_format($itm->max_qty,$itm->decimal_dgts)}}
						</td>
						<td class="text-right">
							{{number_format($itm->min_qty*$itm->refitem->grossweight,1)}}
                            .. {{number_format($itm->max_qty*$itm->refitem->grossweight,1)}}
						</td>
						<td class="text-right">
							@if ($usrrights['doclst.update'])
								<a href="{{ route('ri_cmpnd_items.edit',$itm->id)}}"
								   class="btn btn-sm btn-primary">
									<i class="fa fa-pencil">
									</i>
								</a>
							@endif
						</td>
					</tr>
                    <?php
                    $TotGrossWeight_min = $TotGrossWeight_min + $itm->min_qty * $itm->refitem->grossweight;
                    $TotGrossWeight_max = $TotGrossWeight_max + $itm->max_qty * $itm->refitem->grossweight;
                    ?>
				@endforeach
				<tr>
					<td colspan="5" class="text-right">
						Итого:
					</td>
					<td class="text-right text-bold">
						<b>{{number_format($TotGrossWeight_min,1)}}</b>
                        .. <b>{{number_format($TotGrossWeight_max,1)}}</b>
					</td>
					<td/>
				</tr>
				@endif
				</tbody>
			</table>
		</div>
	</div>
</div>
