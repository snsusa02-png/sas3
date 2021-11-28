@if (isset($rec->fct_at))
	<div class="card mb-3">
		<div class="card-header" style="background-color: darkseagreen;">Фактические данные
		</div>
		<div class="card-body">
			Зарегистрировано: <b>{{$rec->fct_user->short_fio()}}</b>, <span
					class="small">{{date_format(date_create($rec->fct_at), "d.m.Y H:i:s")}}</span>

			<hr size="1">

			<div class="row">
				<div class="form-group col-md-6">
					<label for="fctbegdt">Поставщик услуг:</label>
					<div class="font-weight-bold">
						{{$rec->car_org->name}}
					</div>
				</div>
				<div class="form-group col-md-3">
					<label for="fctbegdt">субперевозчик:</label>
					<div class="font-weight-bold">
						{{$rec->driver_org->name}}
					</div>
				</div>
				<div class="form-group col-md-3">
					<label for="fctbegdt">Водитель:</label>
					<div class="font-weight-bold">
						{{$rec->drivername}}
					</div>
				</div>
			</div>

			<div class="row">
				<div class="form-group col-md-7">
					<label for="fctbegdt">Период эксплуатации:</label>
					<div class="font-weight-bold">
						{{date_format(date_create($rec->fctbegdt), "d.m.Y H:i")}}
						- {{date_format(date_create($rec->fctenddt), "d.m.Y H:i")}}
					</div>
				</div>
				<div class="form-group col-md-5">
					<label for="fcthrs">Затрачено машино-часов:</label>
					<div class="font-weight-bold" align="center">
						{{$rec->fcthrs}}
					</div>
				</div>
			</div>

			<div class="row">
				<div class="form-group col-md-12">
					<label for="fctbegdt">Расчет стоимости:</label>
					<div class="">
						<b>{{number_format($rec->fct_qty,1)}}</b> {{$rec->fct_priceunit}} *
						<b>{{number_format($rec->fct_price,2)}}</b> &#8381;/{{$rec->fct_priceunit}} =
						<b>{{number_format($rec->fct_qty*$rec->fct_price,2)}}</b> &#8381;
						@if($rec->car_orgid<>$rec->ownorgid)
							(от {{$rec->car_org->name}} для {{$rec->ownorg->name}})
						@else
							(от {{$rec->ownorg->name}} для {{$rec->org->name}})
						@endif
					</div>
					@if($rec->car_orgid<>$rec->ownorgid)
						<div class="">
							<b>{{number_format($rec->fct_qty,1)}}</b> {{$rec->fct_priceunit}} *
							<b>{{number_format($rec->own_price,2)}}</b> &#8381;/{{$rec->fct_priceunit}} =
							<b>{{number_format($rec->fct_qty*$rec->own_price,2)}}</b> &#8381;
							(от {{$rec->ownorg->name}} для {{$rec->org->name}})
						</div>
					@endif
				</div>
			</div>

			<div class="float-right">
				@if($rec->rqsttypeid==3)
					<a class="btn btn-close btn-light ml-3 mt-1 btn-sm"
					   style="border-bottom:1px solid steelblue; border-top:1px solid steelblue;"
					   href="{{ route('mchnrqsts.print_transp_nakl', $rec->id) }}"
					   target="_blank"
					   title="Напечатать транспортную накладную">
						<i class="fa fa-print" aria-hidden="true"></i>
						Транспортная накладная 1
					</a>
					<a class="btn btn-close btn-light ml-3 mt-1 btn-sm"
					   style="border-bottom:1px solid steelblue; border-top:1px solid steelblue;"
					   href="{{ route('mchnrqsts.print_transp_nakl_2', $rec->id) }}"
					   target="_blank"
					   title="Напечатать транспортную накладную">
						<i class="fa fa-print" aria-hidden="true"></i>
						Транспортная накладная 2
					</a>
					<a class="btn btn-close btn-light ml-3 mt-1 btn-sm"
					   style="border-bottom:1px solid steelblue; border-top:1px solid steelblue;"
					   href="{{ route('mchnrqsts.print_transp_nakl_3', $rec->id) }}"
					   target="_blank"
					   title="Напечатать транспортную накладную">
						<i class="fa fa-print" aria-hidden="true"></i>
						Транспортная накладная 3
					</a>
				@endif

				@if($rec->rqsttypeid==2)
					<a class="btn btn-close btn-light ml-3 btn-sm"
						style="border-bottom:1px solid steelblue; border-top:1px solid steelblue;"
						href="{{ route('mchnrqsts.print_esm_7', $rec->id) }}"
					   target="_blank"
					   title="Напечатать справку по форме № ЭСМ-7">
						<i class="fa fa-print" aria-hidden="true"></i>
						ЭСМ-7
					</a>
				@endif
			</div>

		</div>
	</div>
@endif
