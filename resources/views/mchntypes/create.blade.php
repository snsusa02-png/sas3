@extends('itmtypes_layout')

@section('content')
<style>
  .uper {
    margin-top: 40px;
  }
</style>
<div class="container">
    <div class="row justify-content-center">

        <div class="col-md-6">
            <div class="card uper">
              <div class="card-header">
                Добавление категории
              </div>
              <div class="card-body">
                @if ($errors->any())
                  <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                          <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                  </div><br />
                @endif
                  <form method="post" action="{{ route('itmtypes.store') }}">
                      <div class="form-group">
                          @csrf
                          <label for="name">Наименование:</label>
                          <input type="text" class="form-control" name="name"/>
                      </div>
                      <div class="form-group">
                          <label for="descript">Описание:</label>
                          <textarea class="form-control rounded-0" name="descript" id="descript" rows="3"></textarea>
                      </div>
                      <div class="form-group">
                          <label for="photourl">Фотография, ссылка :</label>
                          <input type="text" class="form-control" name="photourl"/>
                      </div>
                      <div class="row">
                          <div class="form-group col-md-6">
                              <label for="name">Порядок вывода в списках:</label>
                              <input type="number" class="form-control text-center" name="ordr"
                                     type="number" min="1" max="255" step="1"
                                     value="{{ old('ordr') }}"/>
                          </div>
                          <div class="form-group col-md-6">
                              <label for="name">"Возраст" новинок, дней:</label>
                              <input type="number" min="1" max="120" step="1" placeholder="-по умолчанию-"
                                     class="form-control text-center" name="NewGoodsMaxDays"
                                     value="{{ old('NewGoodsMaxDays') }}"/>
                          </div>
                      </div>
                      <div class="form-group">
                          <label for="price">Категория доступна для использования:</label>
                          {!! Form::checkbox('active', 1, true) !!}
                      </div>

                    <button type="submit" class="btn btn-success">Добавить</button>

                    <a class="btn btn-close btn-info" href="{{ route('itmtypes.index') }}">Закрыть</a>
          &nbsp;
                    </form>

                </div>

            </div>
        </div>

    </div>
</div>
@endsection
