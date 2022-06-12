<div class="row ">
	<div class="col-md-12">
		<div class="card mt-3" style="padding:6px; min-width:660px;">
			<table class="table-striped "
				   style=" width: 100%;">
				<thead>
				<tr>
					<td colspan="{{$hdr_span_colno}}"><h6>&nbsp;Состав документа</h6></td>
					<td style="text-align: right;">
						@if ($usrrights['doclst.create'])
							<div class="btn-group">
								@if ($usrrights['load.items.file'])
									<a href="{{ route('wrhdoclst.load.file',$rec->id)}}"
									   class="btn btn-sm"
									   style="background-color: #f8ac59;"
									   title="добавить состав из файла">
										<i class="fa fa-download" aria-hidden="true"></i>
									</a>
								@endif
								@if ($usrrights['load.items.order'])
									<a href="{{ route('wrhdoclst.load.order',['docid'=>$rec->id,'ordid'=>$rec->ordid])}}"
									   class="btn btn-sm btn-info"
									   title="добавить состав с учетом потребностей заказа и возможностей склада"
									   onclick="return confirm('Состав документа будет сформирован с учетом потребностей заказа и возможностей склада. Продолжить?')"
									>
										<i class="fa fa-download" aria-hidden="true"></i>
									</a>
								@endif
								@if( $usrrights['doclst.create'])
									<a href="{{ route('wrhdoclst.create',$rec->id)}}"
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
                    $TotGrossWeight = 0;
                    ?>
					<tr>
						<td>#</td>
						<td>Наименование</td>
						<td class="text-right">Количество</td>
                        <td>ЕИ</td>
						@if ($showPrice)
							<td>Цена, руб</td>
							<td>
								Сумма, руб
							</td>
						@endif
						<td>Вес, кг</td>
						<td/>
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
							{{$itm->ri_partnumber}}.
							<a href="{{ route('wrhdoclst.edit',$itm->id)}}">

								<b>{{$itm->ri_name}}</b>
								<span class="small">{{$itm->it_name}}/{{$itm->st_name}}</span>
							</a>
						</td>
						<td class="text-right" nowrap>
							{{number_format($itm->qty,$itm->decimal_dgts)}}
						</td>
                        <td class="text-left">
                            <?php
                            $unitname = "";
                            if (isset($itm->refitem->unittype->name))
                                $unitname = $itm->refitem->unittype->name;
                            ?>
                            {{$unitname}}
                        </td>
						@if ($showPrice)
							<td class="text-right">
								{{$itm->price}}
							</td>
							<td class="text-right " nowrap>
								{{number_format($itm->qty*$itm->price,2)}}
							</td>
						@endif
						<td class="text-right">
							{{number_format($itm->qty*$itm->refitem->grossweight,1)}}
						</td>
						<td class="text-right">
							@if ($usrrights['doclst.update'])
								<a href="{{ route('wrhdoclst.edit',$itm->id)}}"
								   class="btn btn-sm btn-primary">
									<i class="fa fa-pencil">
									</i>
								</a>
							@endif
						</td>
					</tr>
                    <?php
                    $TotQty = $TotQty + $itm->qty;
                    $TotDocSum = $TotDocSum + $itm->qty * $itm->price;
                    $TotGrossWeight = $TotGrossWeight + $itm->qty * $itm->refitem->grossweight;
                    ?>
				@endforeach
				<tr>
					<td colspan="{{$tot_span_colno}}" class="text-right">
						Итого:
					</td>
					<td class="text-center text-bold">
						<b>{{number_format($TotQty,0)}}</b>
					</td>
					@if($showPrice)
						<td></td>
						<td class="text-right" nowrap>
							<b>{{number_format($TotDocSum,2)}}</b>
						</td>
					@endif
					<td class="text-right text-bold">
						<b>{{number_format($TotGrossWeight,1)}}</b>
					</td>
					<td/>
				</tr>
				@endif
				</tbody>
			</table>
		</div>
	</div>
</div>
