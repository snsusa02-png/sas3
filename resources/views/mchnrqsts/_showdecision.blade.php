{{--//вывод ранее принятого решения--}}
@if (isset($rec->decision))
    <div class="card mb-3">

        <div class="card-header" style="background-color: beige;">
			<span data-toggle="collapse" data-target="#decision_info">
			@if ($rec->decision==1)
                    <span class="text-success font-weight-bold text-center"
                          style="font-size: 1.6em;">Заявка согласована</span>
                @else
                    <span class="text-danger font-weight-bold" style="font-size: 1.6em;">В заявке отказано</span>
                @endif
				</span>

            <button data-toggle="collapse" data-target="#decision_info"
                    class="btn btn-light btn-sm float-right"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
        </div>

        <div class="card-body " id="decision_info">

            <div class="form-group">
                <div>
                    Решение принято: <b>{{$rec->dcsn_user->short_fio()}}</b>, <span
                            class="small">{{date_format(date_create($rec->dcsn_at), "d.m.Y H:i:s")}}</span>

                    @if (isset($rec->dcsn_descript))
                        <div class="ml-3">пояснение: <i>{{ $rec->dcsn_descript }}</i></div>
                    @endif
                </div>
            </div>

            @if ( $rec->decision==1)
                <div class="row">
                    <div class="form-group col-md-8">
                        <label for="descript">Перевозчик:</label>
                        <div nowrap>
                            <b>{{$rec->car_org->name}}</b>
                        </div>
                    </div>
                    @if($usrrights['set_paytype']??false)
                        <div class="form-group col-md-4">
                            <label for="descript">Способ оплаты:</label>
                            <div nowrap>
                                <b>{{$rec->paytypes[$rec->paytypeid]??''}}</b>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="row">
                    @if ( isset($rec->asgnmachineid))
                        <div class="form-group col-md-8">
                            <label for="descript">Назначена техника:</label>
                            <div>
                                <b>{{$rec->asgnmachine->name}}</b>
                                <div class="ml-2"> гос. №: <b>{{$rec->asgnmachine->regnum}}</b></div>
                            </div>
                        </div>
                    @endif
                    @if(isset($rec->drivername))
                        <div class="form-group col-md-4">
                            <label for="descript">Водитель:</label>
                            <div>
                                <b>{{$rec->drivername}}</b>
                            </div>
                        </div>
                    @endif
                </div>

                @if(isset($rec->contractid))
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="descript">Договор:</label>
                            <div>
                                № <b>{{$rec->contract->docnum}}</b> от
                                <b>{{$rec->contract->docdate}}</b>
                                - {{$rec->contract->name}}</b>.
                                Цена: <b>{{number_format($rec->fct_price,2)}}</b>
                                &#8381;/{{$rec->contract->price_unit}}
                            </div>
                        </div>
                    </div>
                @endif
            @endif
            @if ($usrrights['cancel_dcsn']??false)
                <div class="row">
                    <div class="form-group col-md-12 text-center">
                        <button type="submit"
                                class="btn btn-danger btn-sm"
                                style="margin:0 auto"
                                formaction="{{ route('mchnrqsts.cancel_dcsn', $rec->id)}}"
                                formmethod="post"
                                onclick="return confirm('Вы действительно хотите отменить принятое решение?')"
                                title="Отказать в заявке"
                        >
                            <i class="fa fa-ban" aria-hidden="true"></i>
                            Отменить решение
                        </button>

                    </div>
                </div>
            @endif

        </div>
    </div>
@endif
