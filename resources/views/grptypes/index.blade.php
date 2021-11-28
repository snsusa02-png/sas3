@extends('layouts.app')
@section('content')

    <?php
    $sysobjid = 821;
    $sysobjcode = 'grptypes';
    $thisTitle = "Типы групп";
    $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1
    ?>

	<style>
		.objlog_link {
			float: right;
		}
	</style>

	<form name="forIndex" id="forIndex" method="post" action="{{ route($sysobjcode.'.index') }}">
		@csrf
		<div class="container">

			<div class="row">
				<div class="col-md-12">
					<nav class="breadcrumb">
						<a class="breadcrumb-item" href="/nsi">Данные</a>
						<a class="breadcrumb-item" href="/nsi?tab=nsi-aux">Разное</a>
						<span class="breadcrumb-item active">{{$thisTitle}}</span>
					</nav>
				</div>
			</div>

			<div class="row justify-content-center">
				<div class="col-md-8">
					<h3>{{$thisTitle}}</h3>
					<span class="objlog_link">
							<a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>0,'route'=>Route::current()->getName()])}}"
							   title="Журнал общих событий"
							>журнал</a>
						</span>
					<div>

						@include('layouts.edit_msgs')

						<table class="table table-striped">
							<thead>
							<tr>
								<td class="small">#</td>
								<td>Название</td>
								<td>Порядок</td>
								<td style="text-align: center;">
									@if ($usrrights['create'])
										<a href="{{ route($sysobjcode.'.create')}}"
										   class="btn btn-warning btn-sm"
										   title="Добавить запись">
											<i class="fa fa-plus"></i>
										</a>
									@endif
								</td>
							</tr>
							<tr style="text-align: center;">
								<td/>
								<td>
									<div class="input-group">
										<input type="text" class="form-control c" name="s_name"
											   value="{{$search_params['s_name'] ?? ''}}"/>
									</div>
								</td>
								<td></td>
								<td>
									<div class="input-group-btn">
										<button type="submit" class="btn btn-sm btn-outline-secondary"
												formaction="{{ route($sysobjcode.'.index') }}"
												formmethod="post" title="Поиск">
											<i class="fa fa-search" aria-hidden="true"></i>
										</button>
									</div>
								</td>
							</tr>
							</thead>
							<tbody>
                            <?php
                            $bgcols = array('#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC', '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7');
                            ?>
							@foreach($recs as $rec)
                                <?php
                                $colshift = (1 - $rec->active) * 2;
                                $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];
                                ?>
								<tr style="background-color: {{$tr_bg_col}}">
									<td class="text-right small">
										{{$loop->index + $rec0}}
									</td>
									<td>
										<a href="{{ route($sysobjcode.'.edit',$rec->id)}}" style="">
											{{$rec->name}}
										</a>
									</td>
									<td class="text-right">
										{{$rec->ordr}}
									</td>
									<td style="text-align: center;">
										<a href="{{ route($sysobjcode.'.edit',$rec->id)}}"
										   class="btn btn-sm btn-primary"
										   title="Просмотреть/Изменить запись">
											<i class="fa fa-pencil"></i>
										</a>
									</td>
								</tr>
							@endforeach
							</tbody>
						</table>

						<div>
							{{$recs->links()}}
						</div>
					</div>
				</div>
	</form>
@endsection

