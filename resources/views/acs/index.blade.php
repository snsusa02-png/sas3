@extends('layouts.app')
@section('content')
    @guest
        <?php
        redirect(route('login'));
        //Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();
        ?>
    @else

        <form name="forIndex" id="forIndex" method="post" action="{{ route('acs.index') }}">
            @csrf
            <div class="container">


                <div class="row justify-content-center">
                    <div class="col-md-12">
                        <h3>Категории информации</h3>
                        <div class="mt-3">
                            @include('layouts.edit_msgs')

                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <td>#</td>
                                    <td>Название</td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('acs.create',0)}}" class="btn btn-warning btn-sm">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr style="text-align: center;">
                                    <td></td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="s_name"
                                                   value="{{$data->search_params['s_name']??''}}"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group-btn">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                    formaction="{{ route('acs.index') }}"
                                                    formmethod="post">
                                                <i class="fa fa-search" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                </thead>
                                <tbody>

                                <?php
                                $retURL = Request::url();
                                ?>
                                @foreach($recs as $rec)
                                    <?php
                                    if ($rec->active == 1) {
                                        $status_css = '';
                                        $status_name = '';
                                    } else {
                                        $status_css = 'color:darkred;';
                                        $status_name = 'архив';
                                    }
                                    ?>
                                    <tr>
                                        <td class="small" style="text-align: right'">
                                            {{$data->rec0++}}
                                        </td>
                                        <td>
                                            {{$rec->name}}
                                        </td>

                                        <td style="text-align: center;">
                                            <a href="{{ route('acs.edit',$rec->id)}}?returl={{$retURL}}"
                                               class="btn btn-sm btn-primary">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            @include('layouts.paginate_links')
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endguest
@endsection

