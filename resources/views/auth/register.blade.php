@extends('layouts.app')

@section('content')
	<style>
		body {
			background: url({{env("WELCOME_BG_URI","/images/bgs/bg1.jpg")}}) no-repeat center center fixed;
		}
	</style>
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-8">
				<div class="card">
					<div class="card-header">{{ __('Register') }}</div>

					<div class="card-body">
						<form method="POST" action="{{ route('register') }}">
							@csrf

							{{--                        <div class="form-group row">--}}
							{{--                            <label for="name" class="col-md-4 col-form-label text-md-right">{{ __('Name') }}</label>--}}

							{{--                            <div class="col-md-6">--}}
							{{--                                <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" required autofocus>--}}

							{{--                                @if ($errors->has('name'))--}}
							{{--                                    <span class="invalid-feedback" role="alert">--}}
							{{--                                        <strong>{{ $errors->first('name') }}</strong>--}}
							{{--                                    </span>--}}
							{{--                                @endif--}}
							{{--                            </div>--}}
							{{--                        </div>--}}

							<div class="form-group row">
								<label for="name" class="col-md-4 col-form-label text-md-right">ФИО</label>

								<div class="col-md-6">
									<input id="lname" type="text"
										   class="my-1 form-control{{ $errors->has('lname') ? ' is-invalid' : '' }}"
										   name="lname" value="{{ old('lname') }}" required autofocus
										   placeholder="Фамилия">

									@if ($errors->has('lname'))
										<span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('lname') }}</strong>
                                    </span>
									@endif

									<input id="fname" type="text"
										   class="form-control{{ $errors->has('lname') ? ' is-invalid' : '' }} my-1"
										   name="fname" value="{{ old('fname') }}" required autofocus placeholder="Имя">

									@if ($errors->has('fname'))
										<span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('fname') }}</strong>
                                    </span>
									@endif
									<input id="lname" type="text"
										   class="form-control{{ $errors->has('lname') ? ' is-invalid' : '' }} my-1"
										   name="mname" value="{{ old('mname') }}" autofocus placeholder="Отчество">

									@if ($errors->has('mname'))
										<span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('mname') }}</strong>
                                    </span>
									@endif
								</div>
							</div>

							<div class="form-group row">
								<label for="email"
									   class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Address') }}</label>

								<div class="col-md-6">
									<input id="email" type="email"
										   class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
										   name="email" value="{{ old('email') }}" required>

									@if ($errors->has('email'))
										<span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
									@endif
								</div>
							</div>

							<div class="form-group row">
								<label for="password"
									   class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>

								<div class="col-md-6">
									<input id="password" type="password"
										   class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
										   name="password" required placeholder="минимум 8 символов">

									@if ($errors->has('password'))
										<span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
									@endif
								</div>
							</div>

							<div class="form-group row">
								<label for="password-confirm"
									   class="col-md-4 col-form-label text-md-right">{{ __('Confirm Password') }}</label>

								<div class="col-md-6">
									<input id="password-confirm" type="password" class="form-control"
										   name="password_confirmation" required>
								</div>
							</div>

							<div class="form-group row">
								<label for="note" class="col-md-4 col-form-label text-md-right">Ваша компания</label>

								<div class="col-md-6">

                                <textarea class="form-control rounded-0" name="note" id="note" rows="1"
                                          placeholder=""
                                >{{ old('note') }}</textarea>
<!--
									<input id="note" type="text"
										   class="form-control{{ $errors->has('note') ? ' is-invalid' : '' }}"
										   name="note" value="{{ old('note') }}" required autofocus
										   placeholder="Например, Ваша компания">
-->
									@if ($errors->has('note'))
										<span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('note') }}</strong>
                                    </span>
									@endif
								</div>
							</div>

							<div class="form-group row">
								<label for="phone"
									   class="col-md-4 col-form-label text-md-right">Телефон</label>

								<div class="col-md-6">
									<input id="phone" type="phone"
										   class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }}"
										   name="phone" value="{{ old('phone') }}" required>

									@if ($errors->has('phone'))
										<span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('phone') }}</strong>
                                    </span>
									@endif
								</div>
							</div>

							<div class="form-group row mb-0">
								<div class="col-md-6 offset-md-4">
									<button type="submit" class="btn btn-primary">
										{{ __('Register') }}
									</button>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
