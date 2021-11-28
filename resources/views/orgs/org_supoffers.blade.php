@extends('layouts.edit')

@section('content')
	@guest
        <?php

        redirect()->route('login');
        //Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();


        ?>
	@else
		@if (!isset( $org))
            <?php
            redirect()->route('orgs.index');
            header("Location:" . route('orgs.index'));
            die();
            ?>
		@else
			{{--dd(get_defined_vars())--}}

			<style>
				label {
					color: gray;
					margin-bottom: 0px;
				}
			</style>
			<div class="container">
				<div class="row ">
					@if ($org->id != -1)
						<div class="col-md-12">
							<div class="card mt-3">
								<div class="card-header">
									Предложения/Заказы "<b>{{$org->name}}</b>" ({{$org->id}})

									<a class="btn btn-close btn-info btn-sm"
									   style="float:right"
									   href="{{ route('orgs.edit',$org->id) }}"
									   title="вернуться в карточку контрагента"
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
												Номенклатура
											</td>
											<td>ЕИ</td>
											<td>Кол-во</td>
											<td>Сумма, руб</td>
											<td>Цена, руб</td>
											<td style="text-align: center;">
											</td>
										</tr>
										</thead>
										<tbody>
										@foreach($list as $itm)
                                            <?php
                                            $tr_itm_class = "itm_not_active";
                                            if ($itm->active == 1) {
                                                $tr_itm_class = "itm_active";
                                            }
                                            ?>
											<tr class="{{$tr_itm_class}}">
												<td class="small" style="text-align: right'">
													{{--$local->count--}}
												</td>
												<td>

													<a href="{{route('eritm_offers.edit',$itm->id)}}">
														{{$itm->itm_name}}
													</a>

												</td>
												<td class="text-center">
													{{$itm->unit}}
												</td>
												<td class="text-right">
													{{number_format($itm->ord_qty,3)}}
												</td>
												<td class="text-right">
													{{number_format($itm->ord_sum,2)}}
												</td>
												<td class="text-right">
													{{number_format($itm->ord_price,2)}}
												</td>
												<td style="text-align: center;">
													<a href="{{ route('eritm_offers.edit',$itm->id)}}"
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
@endsection
@endif
@endguest
