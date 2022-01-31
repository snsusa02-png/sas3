{{ $AdditionalProperty->greeting . $AdditionalProperty->userinfo->greeting  }}

{{ $AdditionalProperty->reason }}

Заказ номер {{$AdditionalProperty->order->id}}

Наименование|Кол-во запрошено|Кол-во утверждено|Цена|Стоимость
@foreach ($AdditionalProperty->order->items as $itm)
    {{$itm->refitem->name}}|{{$itm->rqstqty}}|{{$itm->qty ?? 0}}|{{$itm->price}}|{$itm->totalsum ?? $itm->rqstsum}}
@endforeach

Заказ доступен по ссылке:
http://lk.dalioil.com/orders/{{$AdditionalProperty->order->id}}

С уважением, {{ $AdditionalProperty->Signer }}