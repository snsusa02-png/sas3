@extends('layouts.app')

@section('content')
    <?php
    $sysobjid = 3;
    ?>
    <style>
        .org_linked {
            background-color: #efc2c2
        }

        .objlog_link {
            font-size: 9px;
        }
    </style>

    <form name="forIndex" id="forIndex" method="post" action="{{ route('users.index') }}">
        @csrf

        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <nav class="breadcrumb">
                        <a class="breadcrumb-item" href="/admin">Настройки</a>
                        <a class="breadcrumb-item" href="/admin?tab=nsi-admin">Admin</a>
                        <span class="breadcrumb-item active">Пользователи</span>
                    </nav>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-12">
                    <h3>Пользователи
                        <span class="objlog_link small float-right">
							<a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>0,'route'=>Route::current()->getName()])}}"
                               title="Журнал общих событий"
                            >журнал</a>
                            </spans>
                    </h3>

                    @includeif('layouts/edit_msgs')

                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Имя</th>
                            <th scope="col">Email</th>
                            <th scope="col">Телефон</th>
                            <th scope="col">Дата регистрации</th>
                            <th scope="col">Представляет</th>
                            <th/>
                        </tr>
                        <tr style="text-align: center;">
                            <td/>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control text-center" name="s_name"
                                           value="{{$search_params['s_name'] ?? ''}}"/>
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control text-center" name="s_email"
                                           value="{{$search_params['s_email'] ?? ''}}"/>
                                </div>
                            </td>
                            <td/>
                            <td>
                                {!! Form::select('s_online', [''=>'все','1'=>'online','0'=>'offline'],
$search_params['s_online'],
['class' => 'form-control small','onChange' => 'this.form.submit()',]) !!}
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control text-center" name="s_orgname"
                                           value="{{$search_params['s_orgname'] ?? ''}}"/>
                                </div>
                            </td>
                            <td>
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                            formaction="{{ route('users.index') }}"
                                            formmethod="post" title="Поиск">
                                        <i class="fa fa-search" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </thead>
                        <tbody>
                        @if (count($recs)>0)
                            @foreach($recs as $rec)
                                @php
                                    $td_orgname_class="";
                                    //if ($rec->curplaceid == ""){
                                    if (!isset($rec->lstUserOrgs)){
                                        $td_orgname_class="org_linked";
                                        }
                                @endphp
                                <tr>
                                    <td scope="row" class="small text-right">{{$loop->index+1}}</td>
                                    <td><a href="{{ route('users.edit',$rec->id)}}"
                                           title="Просмотреть/Изменить запись">
                                            <div style="display: table-cell;">{!! $rec->avatar !!}</div>
                                            <div style="display: table-cell;padding-left: 8px;">{{$rec->name}}</div>
                                        </a>
                                    </td>
                                    <td>{{$rec->email}}</td>
                                    <td>{{$rec->phone}}</td>
                                    <td class="text-center small">{{$rec->regdate}}
                                        @if($rec->isOnline())
                                            <span class="text-success font-size-12">
												<i class="fa fa-user-circle-o" aria-hidden="true"
                                                   title="Пользователь сейчас в системе"></i>
											</span>
                                        @else
                                            <span class="text-secondary font-size-12"><i class="fa fa-circle-thin"
                                                                                         aria-hidden="true"
                                                                                         title="Пользователь не в системе"></i>
											</span>
                                        @endif
                                        @if($rec->active<>1)
                                            <span class="text-danger font-size-14 font-weight-bold">заблокирован</span>
                                        @endif
                                    </td>
                                    <td class="{{$td_orgname_class}}">{{$rec->lstUserOrgs}}</td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('users.edit',$rec->id)}}"
                                           class="btn btn-sm btn-primary"
                                           title="Просмотреть/Изменить запись">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="text-center">Данные не найдены.</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>

                    @includeIf('layouts.paginate_links')

                </div>
            </div>
        </div>
    </form>
@endsection
