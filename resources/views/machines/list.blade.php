@extends('list_layout')

@section('content')
	<script src="{{ asset('js/handleListOrgs.js') }}"></script>
	@guest
        <?php
        redirect(route('login'));
        ?>
	@else

		<style>
			.list {
				background-color: #FFFFFF;
				cursor: pointer;
			}

			.list:hover {
				background-color: #FFFF99;
			}
		</style>
		<form name="forIndex" id="forIndex" method="post" action="{{ route('refitems.list') }}">
			@csrf

			<div class="container">
				<div><br></div>
				<div class="row justify-content-center">
					<div class="col-md-12">

						<table class="table table-condensed tbl_list" style="background-color: white;">
							<thead>
							<tr>
								<td colspan="5">
									<b>Выбор организации</b>
								</td>
							</tr>
							<tr class>
								<th scope="col">#</th>
								<th scope="col">Название</th>
								<th scope="col" class="text-center">ИНН</th>
								<th scope="col" class="text-center">КПП</th>
							</tr>
							<tr style="text-align: center;">
								<th/>
								<th>
									<div class="input-group">
										<input type="text" class="form-control c" name="search_name"
											   value="{{$search_name ?? ''}}"/>
									</div>
								</th>
								<th>
									<div class="input-group">
										<input type="text" class="form-control c" name="s_inn"
											   value="{{$s_inn ?? ''}}"/>
									</div>
								</th>
								<th>
									<div class="input-group-btn">
										<button type="submit" class="btn btn-sm btn-outline-secondary"
												formaction="{{ route('orgs.list') }}"
												formmethod="post" title="Поиск">
											<i class="fa fa-search" aria-hidden="true"></i>
										</button>
									</div>
								</th>
							</tr>
							</thead>

							<tbody>
							@if (count($items)>0)
								@foreach($items as $item)
                                    <?php
                                    if ($item->active)
                                        $activenote = "";
                                    else
                                        $activenote = "работа приостановлена";
                                    ?>
									<tr onclick="javascript:hitOrg({{$item->id}});" class="list"
									>
										<td scope="row" class="small text-right">{{$loop->index+1}}</td>
										<td>{{$item->name}}</a>
											<div class="small" style="color:darkred">{{$activenote}}</div>
										</td>
										<td class="text-center small">{{$item->inn}}</td>
										<td class="text-center small">{{$item->kpp}}</td>
									</tr>
								@endforeach
							@else
								<tr>
									<td colspan="6" class="c">
										<div style="background-color:lemonchiffon;">
											Данные не найдены.<br>Попробуйте изменить критерий поиска.
										</div>
									</td>
								</tr>
							@endif
							</tbody>
						</table>
						<div>
							{{$items->appends([ 'search_name'=>$search_name
							,'s_inn'=>$s_inn
						])->links()}}
						</div>
					</div>
				</div>
			</div>
		</form>
	@endguest
@endsection

