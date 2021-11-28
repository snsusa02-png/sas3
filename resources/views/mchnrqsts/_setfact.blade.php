{{--@if ( $usrrights['setfact']??false and is_null($rec->fctbegdt) )--}}
@if ( $usrrights['setfact']??false )
    <div class="card mb-3">
        <div class="card-header" style="background-color: darkseagreen;">Регистрация фактических данных
        </div>
        <div class="card-body">

            @if ($rec->statusid==2 and $errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div><br/>
            @endif

            <input type="hidden" name="paytypeid" id="paytypeid" value="{{$rec->paytypeid}}">

            @if($rec->rqsttypeid==3)
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="fctbegdt" class="small">Дата и время подачи под
                            погрузку:</label>
                        <input type="datetime-local" class="form-control text-center"
                               name="fctbegdt"
                               id="fctbegdt"
                               value="{{old('fctbegdt',$rec->fctbegdt)}}"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="fctenddt" class="small">Дата и время убытия после
                            разгрузки:</label>
                        <input type="datetime-local" class="form-control text-center"
                               name="fctenddt"
                               id="fctenddt"
                               value="{{old('fctenddt',$rec->fctenddt)}}"
                        />
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="fctbegdt" class="small">Дата и время начала работ:</label>
                        <input type="datetime-local" class="form-control text-center"
                               name="fctbegdt"
                               value="{{old('fctbegdt',$rec->fctbegdt)}}"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="fcthrs" class="small">Затрачено часов:</label>
                        <input type="number" class="form-control text-right"
                               name="fcthrs"
                               id="fcthrs"
                               value="{{old('fcthrs',$rec->fcthrs)}}"
                               min=0 step="0.1"
                        />
                    </div>
                </div>
            @endif

            @if($rec->isowncarrier)

                @if($rec->paytypeid==1)

                    <div class="form-group asgn_own">
                        <label for="name">Договор с заказчиком:</label>
                        {!! Form::select('contractid', $rec->contracts, $rec->contractid,
                            [
                            'class' => 'form-control',
                            'placeholder' => '-выбор-',
                            ]) !!}
                    </div>

                @elseif($rec->paytypeid==2)
                    <div class="row">
                        <div class="form-group offset-md-6 col-md-3">
                            <label for="fctenddt" class="small">Цена, &#8381;/час:</label>
                            <input type="number" class="form-control text-right"
                                   name="fct_price" min="0" id="fct_price"
                                   value="{{old('fct_price',$rec->fct_price)}}"
                            />
                        </div>
                        <div class="form-group col-md-3">
                            <label for="fctenddt" class="small">Сумма, &#8381;:</label>
                            <input type="number" class="form-control text-right"
                                   name="fct_sum" id="fct_sum" readonly
                                   value="{{old('fct_sum',$rec->fct_sum)}}"
                            />
                        </div>
                    </div>
                @endif

                <div class="row">
                    <div class="form-group asgn_own col-md-6 ">
                        <label for="name">суб-перевозчик:</label>
                        {!! Form::select('driver_orgid', $rec->drivers, $rec->driver_orgid,
                            [
                            'id' => 'driver_orgid',
                            'class' => 'form-control',
                            'placeholder' => '-выбор-',
                            ]) !!}
                    </div>
                    <div class="form-group asgn_own col-md-6">
                        <label for="name">Водитель:</label>

                        <input type="text" list="driverorgstaff" name="drivername"
                               id="drivername"
                               class="form-control text-left"
                               value="{{old('drivername',$rec->drivername)}}"/>

                        <datalist id="driverorgstaff">
                            <option value="-ФИО водителя-">
                        </datalist>
                    </div>
                </div>


            @else
                {{--Сторонний перевозчик--}}
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="fctbegdt" class="small">Ед. измерения:</label>
                        {!! Form::select('fct_priceunit', $rec->priceunits, $rec->fct_priceunit,
                                                [
                                                'id' => 'fct_priceunit',
                                                'class' => 'form-control',
                                                'placeholder' => '-выбор-',
                                                ]) !!}
                    </div>
                    <div class="form-group col-md-3">
                        <label for="fctenddt" class="small">Количество, ЕИ:</label>
                        <input type="number" class="form-control text-right"
                               name="fct_qty" min="0" step="0.1"
                               value="{{old('fct_qty',$rec->fct_qty)}}"
                        />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="fctenddt" class="small">Цена, &#8381;/ЕИ:</label>
                        <input type="number" class="form-control text-right"
                               name="fct_price" min="0"
                               value="{{old('fct_price',$rec->fct_price)}}"
                        />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="fctenddt" class="small">Сумма, &#8381;:</label>
                        <input type="number" class="form-control text-right"
                               name="fct_sum" readonly
                               value="{{old('fct_sum',$rec->fct_sum)}}"
                        />
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="fctbegdt" class="small">Наименование использованной техники:</label>
                        <input type="text" class="form-control text-left"
                               name="asgnmachinename"
                               value="{{old('asgnmachinename',$rec->asgnmachinename)}}"/>
                    </div>
                </div>

            @endif


            <hr size=1>
            <div class="d-flex justify-content-center">
                <button type="submit"
                        class="btn btn-warning text-center"
                        style0="margin:0 auto"
                        formaction="{{ route('mchnrqsts.setfact', $rec->id)}}"
                        formmethod="post"
                        onclick="return confirm('Будут сохранены фактические данные и работа по заявке будет завершена. Продолжить?')"
                        title="Сохранить факт"
                >
                    <i class="fa fa-lock" aria-hidden="true"></i>
                    Зафиксировать
                </button>

                <a class="btn btn-close btn-info  ml-2" href="{{ $route_index }}"
                   title="Вернуться в список">
                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                    Закрыть
                </a>

            </div>

        </div>
    </div>
    </div>

@endif
