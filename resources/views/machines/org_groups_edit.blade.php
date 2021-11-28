@extends('layouts.edit')

@section('content')
	@if (!isset( $org))
        <?php
        redirect()->route('orgs.index');
        header("Location:" . route('orgs.index'));
        die();
        ?>
	@else
        <?php
        $sysobjid = 111;
        $thisTitle = "Группы контрагента";
        ?>
		{{--dd(get_defined_vars())--}}
		<style>
			label {
				color: gray;
				margin-bottom: 0px;
			}
		</style>
		<div class="container">
			@if(session()->get('success'))
				<div class="row">
					<div class="col-md-6 col-sm-6">
						<div class="alert alert-success">
							{{ session()->get('success') }}
						</div>
					</div>
				</div>
			@endif
			@if(session()->get('warning'))
				<div class="row">
					<div class="col-md-6 col-sm-6">
						<div class="alert alert-warning">
							{{ session()->get('warning') }}
						</div>
					</div>
				</div>
			@endif
			@if(session()->get('error'))
				<div class="row">
					<div class="col-md-6 col-sm-6">
						<div class="alert alert-danger">
							{{ session()->get('error') }}
						</div>
					</div>
				</div>
			@endif

			<div class="row ">
				<div class="col-md-8">
					<div class="card p-2 my-2 my-md-3">
						<div class="card-header">
							{{$thisTitle}}
							<a class="btn btn-close btn-info btn-sm"
							   style="float:right;"
							   href="{{ route('orgs.edit',$org->id) }}"
							   title="Вернуться в список клиентов">
								<i class="fa fa-times" aria-hidden="true"></i>
							</a>
						</div>
						<div class="card-body">
							@if ($errors->any())
								<div class="alert alert-danger">
									<ul>
										@foreach ($errors->all() as $error)
											<li>{{ $error }}</li>
										@endforeach
									</ul>
								</div><br/>
							@endif

							<form name="forEdit" id="forEdit" method="post"
								  action="{{ route('org_groups.update', $org->id) }}">
								@method('PUT')
								@csrf

								<div class="form-group">
									<label for="name">Контрагент:</label>
									<b>{{$org->name}}</b>
								</div>

								<div class="row">
									@foreach($org->gt_groups as $gt)
										<div class="form-group col-md-6">
											<label for="active" style="color: rgb(73, 80, 87);">Группа "{{$gt['name']}}
												":</label>
											{{--										{!! Form::select('gt_'.$gt['id'], $gt['groups'], $gt['value'], ['class' => 'form-control','placeholder'=>'']) !!}--}}
											{!! Form::select('gt['.$gt['id'].']', $gt['groups'], $gt['value'], ['class' => 'form-control','placeholder'=>'']) !!}
										</div>
									@endforeach
								</div>


								@if ($usrrights['save'])
									<button type="submit" class="btn btn-success" title="Сохранить изменения">
										<i class="fa fa-floppy-o" aria-hidden="true"></i>
										Сохранить
									</button>
								@endif
								&nbsp;
								<a class="btn btn-close btn-info" href="{{ route('orgs.edit',$org->id) }}"
								   title="Вернуться в карточку контрагента">
									<i class="fa fa-window-close-o" aria-hidden="true"></i>
									Закрыть
								</a>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	@endif
@endsection
