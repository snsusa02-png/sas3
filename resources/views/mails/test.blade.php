Hello <i>{{ $AdditionalProperty->receiver }}</i>,
<p>This is a HTML version demo email for testing purposes!</p>

<p><u>Demo object values:</u></p>

<div>
    <p><b>Demo One:</b>&nbsp;{{ $AdditionalProperty->demo_one }}</p>
    <p><b>Demo Two:</b>&nbsp;{{ $AdditionalProperty->demo_two }}</p>
</div>
<p><u>Order object:</u></p>

<div>
    <p>{{$AdditionalProperty->order}}</p>
</div>

<ul>
    @foreach ($AdditionalProperty->list1 as $itm)
        <li>{{$itm}}</li>
    @endforeach
</ul>
{{--<ul>
    @foreach ($AdditionalProperty->itmtypes as $itm)
        <li>{{$itm->name}} ({{$itm->id}})</li>
    @endforeach
</ul>--}}
<p><u>Values passed by With method:</u></p>

<div>
    <p><b>testVarOne:</b>&nbsp;{{ $testVarOne }}</p>
    <p><b>testVarTwo:</b>&nbsp;{{ $testVarTwo }}</p>
</div>

Thank You,
<br/>
<i>{{ $AdditionalProperty->sender }}</i>