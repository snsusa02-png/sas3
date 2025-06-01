@extends('layouts.edit')

@section('content')
	@if (!isset( $machine))
        <?php
        redirect()->route('machines.index');
        header("Location:" . route('machines.index'));
        die();
        ?>
	@else
        <?php
        $mayCreate = \App\usrsysright::isUserHasRightByCode(Auth::user()->id, 'objextids.create');
        ?>

		<style>
			label {
				color: gray;
				margin-bottom: 0px;
			}
		</style>

		<div class="container">
			<div class="row ">
				@if ($machine->id != -1)
					<div class="col-md-6">
						<span class="helptags" data="machine_extids"/>

						<div class="card mt-2">

							@if(session()->get('success'))
								<div class="alert alert-success">
									{{ session()->get('success') }}
								</div><br/>
							@endif
							@if(session()->get('warning'))
								<div class="alert alert-warning">
									{{ session()->get('warning') }}
								</div><br/>
							@endif
							@if(session()->get('error'))
								<div class="alert alert-error">
									{{ session()->get('error') }}
								</div><br/>
							@endif

							<div class="card-header">
								Идентификаторы авто/техники "<b>{{$machine->name}}</b>" во внешних системах

								<a class="btn btn-close btn-info btn-sm"
								   style="float:right"
								   href="{{ route('machines.edit',$machine->id) }}"
								   title="вернуться в карточку"
								>
									<i class="fa fa-times" aria-hidden="true"></i>
								</a>
							</div>

							<div class="card-body">

								<table class="table">
									<thead>
									<tr>
										<td>#</td>
										<td>Система</td>
										<td class="text-center">Идентификатор</td>
										<td style="width:36px; text-align: center;">
											@if($mayCreate)
												<a href="{{ route('objextids.create',['sysobjid'=>482,'objid'=>$machine->id])}}?returl={{Request::url()}}"
												   class="btn btn-warning btn-sm"
												   title="Добавить запись">
													<i class="fa fa-plus"></i>
												</a>
											@endif
										</td>
									</tr>

									</thead>
									<tbody>
                                    <?php
                                    $rec0 = 1;
                                    ?>
									@foreach($recs as $itm)
										<tr>
											<td class="small text-right">
												{{$loop->index + $rec0}}
											</td>
											<td>
												{{$itm->extsys->name}}
											</td>
											<td class="text-center">
												{{$itm->extid}}
											</td>
											<td style="text-align: center;">
												<a href="{{ route('objextids.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
												   class="btn btn-sm btn-primary"
												   title="Просмотреть/Изменить запись">
													<i class="fa fa-pencil"></i>
												</a>
											</td>
										</tr>
									@endforeach
									</tbody>
								</table>

							</div>
						</div>
					</div>
				@endif

			</div>
		</div>
	@endif
@endsection
