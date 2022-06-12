@extends('layouts.app')

@section('content')
	@guest
        <?php
        redirect(route('login'));
        ?>
	@else

        <?php
        $thisTitle = "Склады предприятия";

        $rec0 = $items->currentPage() * $items->perPage() - $items->perPage() + 1
        ?>

		<form name="forIndex" id="forIndex" method="post" action="{{ route('wrhs.index') }}">
			@csrf

			<div class="container">

				<div class="row">
					<div class="col-md-12">
						<nav class="breadcrumb">
							<a class="breadcrumb-item" href="/nsi">Данные</a>
							<a class="breadcrumb-item" href="/nsi?tab=nsi-stock">Склад</a>
							<span class="breadcrumb-item active">{{$thisTitle}}</span>
						</nav>
					</div>
				</div>

				<div class="row justify-content-center">
					<div class="col-md-9">
						<h4>Склады предприятия</h4>
						<table class="table">
							<thead>
							<tr>
								<th scope="col">#</th>
								<th scope="col">Название, описание</th>
								<th scope="col">Особенности</th>
								<th scope="col">Адрес</th>
								<td>
									@if ($usrrights['create'])
										<a href="{{ route('wrhs.create')}}"
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
											   value="{{$s_name ?? ''}}"/>
									</div>
								</td>
								<td>
								</td>
								<td>
									<div class="input-group">
										<input type="text" class="form-control c" name="s_address"
											   value="{{$s_address ?? ''}}"/>
									</div>
								</td>
								<td>
									<div class="input-group-btn">
										<button type="submit" class="btn btn-sm btn-outline-secondary"
												formaction="{{ route('wrhs.search') }}"
												formmethod="post" title="Поиск">
											<i class="fa fa-search" aria-hidden="true"></i>
										</button>
									</div>
								</td>
							</tr>
							</thead>
							<tbody>
                            <?php
                            ?>
							@if (count($items)>0)
                                <?php
                                $bgcols = array('#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC', '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7');
                                $rec0 = 0;
                                ?>
								@foreach($items as $rec)
                                    <?php
                                    $colshift = (1 - $rec->active) * 2;
                                    $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];

                                    $spec = "";
                                    $spec .= ($rec->forsale) ? ' отпуск товара;' : '';

                                    ?>
									<tr style="background-color: {{$tr_bg_col}}">
										<td scope="row" class="small text-right">{{$loop->index+1}}</td>
										<td><a href="{{route('wrhs.edit',$rec->id)}}" target="_self">{{$rec->name}}</a>
											<div class="small"> {{$rec->descript}}</div>
										</td>
										<td class="text-center">{{$spec}}</td>
										<td class="c">{{$rec->address}}</td>
										<td style="text-align: center;">
											<a href="{{ route('wrhs.edit',$rec->id)}}"
											   class="btn btn-sm btn-primary"
											   title="Просмотреть/Изменить запись">
												<i class="fa fa-pencil"></i>
											</a>
										</td>
									</tr>
								@endforeach
							@else
                                <?php
                                if (Route::currentRouteName() == "refitems.search") {
                                    $msg = "Данные не найдены. \nПопробуйте изменить критерий поиска и повторить.";
                                } else {
                                    $msg = "Нет записей.";
                                }
                                ?>
								<tr>
									<td colspan="6" class="c">{{$msg}}</td>
								</tr>
							@endif
							</tbody>
						</table>
						<div>
							@if (Route::currentRouteName() == "wrhs.search")
								{{$items->appends(['s_name'=>$s_name
										,'s_address'=>$s_address])->links()}}
							@else
								{{$items->links()}}
							@endif
						</div>
					</div>
				</div>
			</div>
		</form>
	@endguest
@endsection

