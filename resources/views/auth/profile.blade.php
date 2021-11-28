@extends('layouts.app')

@section('content')
	<div class="container" style="border-bottom:1px solid #dddddd;">
		<h2>Профиль пользователя
			@if($showLink2User )
				<a href="/users/{{auth()->user()->id}}" class="ml-1"
				   title="Открыть карточку пользователя">...</a>
			@endif
		</h2>
		<br>
		<form action="{{ route('profile.update') }}" method="POST" role="form"
			  enctype="multipart/form-data" class="form-horizontal">
		@csrf

		<!-- Nav tabs -->
			<ul class="nav nav-tabs" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" data-toggle="tab" href="#home">Основное</a>
				</li>

				<!-- <li class="nav-item">
					<a class="nav-link" data-toggle="tab" href="#menu1">Предпочтения</a>
				</li> -->
				<li class="nav-item">
					<a class="nav-link" data-toggle="tab" href="#menu2">Журнал</a>
				</li>
				<li>
					<button type="submit" class="btn btn-primary ml-5 text-right">
						<i class="fa fa-floppy-o" aria-hidden="true"></i> Обновить профиль
					</button>
				</li>
			</ul>

			<!-- Tab panes -->
			<div class="tab-content">
				<div id="home" class="container tab-pane active"><br>
					<h3>Основные сведения</h3>
					{{--					<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut--}}
					{{--						labore et dolore magna aliqua.</p>--}}

					<div class="form-group required row">
						<label class="col-sm-3 control-label"
							   for="input-name1">ФИО</label>
						<div class="col-sm-3">
							<input type="text" name="lname"
								   value="{{ old('lname', auth()->user()->lname) }}"
								   placeholder="Фамилия"
								   id="lname" class="form-control">
						</div>
						<div class="col-sm-3">
							<input type="text" name="fname"
								   value="{{ old('fname', auth()->user()->fname) }}"
								   placeholder="Имя"
								   id="fname" class="form-control">
						</div>
						<div class="col-sm-3">
							<input type="text" name="mname"
								   value="{{ old('mname', auth()->user()->mname) }}"
								   placeholder="Отчество"
								   id="input-name1" class="form-control">
						</div>
					</div>

					<div class="form-group">
						<label class="col-sm-3 control-label"
							   for="input-description1">e-mail</label>
						<div class="col-sm-9">
							<input type="text" name="email"
								   value="{{ auth()->user()->email }}"
								   placeholder="email" readonly disabled=""
								   id="email" class="form-control">
						</div>
					</div>
					<div class="form-group ">
						<label class="col-sm-3 control-label" for="input-meta-title1">Дата
							регистрации</label>
						<div class="col-sm-9">
							<input type="text" name="created_at"
								   value="{{ old('created_at', auth()->user()->created_at) }}"
								   readonly disabled=""
								   id="created_at" class="form-control">
						</div>
					</div>
					<div class="form-group ">
						<label class="col-sm-3 control-label"
							   for="input-meta-title1">Аватар</label>
						<div class="col-sm-9">
							@if (auth()->user()->image)
								{{--														<code>{{ auth()->user()->image }}</code>--}}
								<img src="{{auth()->user()->image}}" width="100">
								{{ asset(auth()->user()->image) }}
							@endif
							<input id="profile_image" type="file" class="form-control"
								   name="profile_image" value="///">
						</div>
					</div>


				</div>
				<div id="menu1" class="container tab-pane "><br>
					<h3>Предпочтения пользователя</h3>
					<p>Здесь можно немного настроить интерфейс системы "под себя".</p>
					@foreach($rec->prefs as $pref)
						<div class="form-group">
							<label class="col-sm-8 control-label" for="input-status">
								{{$pref->prefname}}
							</label>
							<div class="col-sm-4">
								@if($pref->valsrctype=='LST')
									{!! Form::select('pref['.$pref->preftypeid.']', $pref->vals, $pref->prefvalue,
									 [
									 'class' => 'form-control',
									 'placeholder' => '-выбор-',
									 ]) !!}
								@else
									<input type="text" name="pref[{{$pref->preftypeid}}]"
										   value="{{ $pref->prefvalue }}"
										   placeholder=""
										   class="form-control">
								@endif
							</div>
						</div>
					@endforeach

				</div>
				<div id="menu2" class="container tab-pane fade0"><br>
					<h3>Журнал недавних действий</h3>
					<p>Действия пользователя в системе.</p>
					<ul>
						@foreach($rec->log as $itm)
							<li>
								{{$itm['write_at']}}: {{$itm['info']}}
							</li>
						@endforeach
					</ul>
				</div>
			</div>
		</form>
	</div>
@endsection
