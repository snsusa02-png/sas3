@extends('layouts.edit')

@section('content')
    @if (!isset( $obj))
        <?php
        redirect()->route('nsi');
        header("Location:" . route('nsi'));
        die();
        ?>
    @else

        {{--		{{dd(get_defined_vars())}}--}}

        <style>
            /*label {*/
            /*	color: gray;*/
            /*	margin-bottom: 0px;*/
            /*}*/

        </style>
        <div class="container">
            <div class="row ">
                @if ($obj->id != -1)
                    <div class="col-md-12">
                        <div class="card mt-3">
                            <div class="card-header">
                                Журнал событий <b>"{{$obj->sysobjname}}"</b> ({{$obj->id}})
                                {{--								@dd($obj)--}}

                                <a class="btn btn-close btn-info btn-sm"
                                   style="float:right"
                                   href="{{ route($route,$obj->id) }}"
                                   title="вернуться в заказ"
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

                                @if ($recs->count()>0)
                                    <table class="table table-striped table-condensed table-sm small">
                                        <thead>
                                        <tr>
                                            <td>#</td>
                                            <td class="text-center"><i class="fa fa-clock-o" aria-hidden="true"></i>
                                            </td>
                                            <td>Событие</td>
                                            <td>Инициатор</td>
                                            <td>level</td>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $errlvl_css = [
                                            1 => 'color:red;font-weight:bold',
                                            2 => 'color:red;',
                                            3 => 'color:blue;',
                                            4 => 'color:darkorange;',
                                            5 => 'color:gray;',
                                            6 => 'color:silver;',
                                            ];
                                        $curViewDate = null;
                                        ?>
                                        @foreach($recs as $itm)
                                            <?php
                                            $viewDT = new DateTime($itm['write_at']);
                                            $viewDate = $viewDT->format('Y-m-d');
                                            $viewTime = $viewDT->format('H:i:s');
                                            if ($viewDate <> $curViewDate)
                                                $curViewDate = $viewDate;
                                            else
                                                $viewDate = '';
                                            ?>
                                            <tr>
                                                <td class="small"
                                                    style="text-align: right'">{{$loop->index+$obj->rec0}}</td>
                                                <td class="small text-right">{{$viewDate}} {{$viewTime}}</td>
                                                <td class="text-left">{!! $itm->info !!}</td>
                                                <td>{{$itm->username}}</td>
                                                <td style="{{$errlvl_css[$itm->errlvl]??''}}">{{$itm->errlvlname}}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                    {{$recs->links()}}
                                @else
                                    <div class="text-center">
                                        Нет событий
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
@endsection
@endif
