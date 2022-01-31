<i>{{ $AdditionalProperty->greeting . $AdditionalProperty->userinfo->greeting }}</i>!
<p>{{ $AdditionalProperty->reason }}</p>

<p><a href="http://lk.daloil.com/orders/{{$AdditionalProperty->order->id}}">Заказ № {{$AdditionalProperty->order->id}}</a></p>
<table>
	<tr>
		@if ($AdditionalProperty->showoffer)
			<td colspan="5" align="left">Состав заказа:</td>
		@else
			<td colspan="4" align="left">Состав заказа:</td>
		@endif
	</tr>
	<tr>
		<td>#</td>
		<td align="center">Наименование</td>
		<td align="center">Кол-во запрошено</td>
		@if ($AdditionalProperty->showoffer)
			<td align="center">Кол-во утверждено</td>
		@endif

		<td>Цена, руб</td>
		<td>Стоимость, руб</td>
	</tr>
	@php($totsum=0)
	@php($colspan=4)
	@foreach ($AdditionalProperty->order->items as $itm)
		<tr>
			<td align="right" style="font-size: 10px;">{{$loop->iteration}}</td>
			<td align="left">
				{{$itm->refitem->code1s}}: {{$itm->refitem->name}}
				{{$itm->refitem->RI_specinfo('; ')}}
			</td>
			<td align="center">{{number_format($itm->rqstqty,0)}}</td>
			@if ($AdditionalProperty->showoffer)
				<td align="center">{{number_format($itm->qty ?? 0,0)}}</td>
				@php($colspan=5)
			@endif
			<td align="right">{{$itm->price}}</td>
			<td align="right">{{$itm->totalsum ?? $itm->rqstsum}}</td>
		</tr>
		@php($totsum=$totsum + ($itm->totalsum ?? $itm->rqstsum))
	@endforeach
	<tr>
		<td colspan="{{$colspan}}"></td>
		<td align="right"><b>{{number_format($totsum,2)}}</b></td>
	</tr>
</table>
{{--@if (isset($AdditionalProperty->order->name))--}}
{{--@if (!is_null($AdditionalProperty->order->name))--}}
@if ($AdditionalProperty->order->name != "")
	<div>
		<p>Примечания: {{$AdditionalProperty->order->name}}</p>
	</div>
@endif
@if ($AdditionalProperty->order->plndlvryinfo != "")
	<div>
		<p>Информация по доставке: {{$AdditionalProperty->order->plndlvryinfo}}</p>
	</div>
@endif


С уважением,
<br/>
<i>{{ $AdditionalProperty->Signer }}</i>
