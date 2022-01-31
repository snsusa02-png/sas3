@component('mail::message')
mail::message
	{{--The body of your message.--}}
	Новый пользователь:
	<ul>
		<li>ФИО: {{$user->name}}</li>
		<li>email: {{$user->email}}</li>
		<li>телефон: {{$user->phone}}</li>
		<li>компания: {{$user->note}}</li>
	</ul>


	@component('mail::button', ['url' => config('app.url')])
		открыть сайт
	@endcomponent
	<hr>

{{--	Thanks,--}}
{{--	{{ config('app.name') }}--}}
@endcomponent
