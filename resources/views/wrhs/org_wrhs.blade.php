@extends('layouts.edit')

@section('content')
	@if (!isset( $org))
        <?php
        redirect()->route('orgs.index');
        header("Location:" . route('orgs.index'));
        die();
        ?>
	@else

		{{--dd(get_defined_vars())--}}

		<style>
			.uper {
				margin-top: 36px;
			}

			label {
				color: gray;
				margin-bottom: 0px;
			}

			.org-aux {
				width: 100%
			}
		</style>
		<div class="container">
			<div class="row ">
				@if ($org->id != -1)
					<div class="col-md-12">
						<div class="card uper">
							<div class="card-header">
								Склады, обслуживающие клиента "<b>{{$org->name}}</b>" ({{$org->id}})

								<a class="btn btn-close btn-info btn-sm"
								   style="float:right"
								   href="{{ route('orgs.edit',$org->id) }}"
								   title="вернуться в карточку клиента"
								>
									<i class="fa fa-times" aria-hidden="true"></i>
								</a>
							</div>
							<div class="card-body">
								@if(session()->get('success'))
									<div class="alert alert-success">
										{{ session()->get('success') }}
									</div><br/>
								@endif

								<table class="table table-striped">
									<thead>
									<tr>
										<td>#</td>
										<td>
											Склад
										</td>
										<td>Инфо</td>
										<td style="text-align: center;">
											<a href="{{ route('org_wrhs.create',$org->id)}}"
											   class="btn btn-warning btn-sm"
											   title="Добавить запись о новом складе">
												<i class="fa fa-plus"></i>
											</a>
										</td>
									</tr>
									</thead>
									<tbody>
                                    <?php
                                    $bgcols = array('#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC', '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7');
                                    $rec0 = 1; //$curators->currentPage() * $curators->perPage() - $curators->perPage() + 1
                                    ?>
									@foreach($recs as $itm)
                                        <?php
                                        $colshift = (1 - $itm->active) * 2;
                                        $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];
                                        ?>
										<tr style="background-color: {{$tr_bg_col}}">
											<td class="small" style="text-align: right'">
												{{$loop->index + $rec0}}
											</td>
											<td>
												{{$itm->wrh->name}}
											</td>
											<td class="text-left">
												<span class="small">{{$itm->wrh->address}}</span>
											</td>
											<td style="text-align: center;">
												<a href="{{ route('org_wrhs.edit',$itm->id)}}"
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
