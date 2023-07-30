@extends('layouts.edit')

@section('content')

    <?php
    if (!$usrrights['save']){
        redirect()->route('users.edit', $role->id);
        header("Location:" . route('users.edit', $role->id));
        die();
    }

    $retURL = \Request::get('returl') ?? $data->retURL ?? (route('users.index') . "?page=" . session('users' . '_pageno') . '#' . $role->id);
    ?>
    <script>
        function to_top0() {
            $('html, body').animate({
                scrollTop: 0
            }, 400);
            return false;
        }

        function to_top() {
            window.scroll({
                top: 0,
                left: 0,
                behavior: 'smooth'
            });
        }

        function to_bottom() {
            window.scroll({
                top: 999990,
                left: 0,
                behavior: 'smooth'
            });
        }
    </script>

    <style>
        #container {
            position: relative;
        }

        .sysobjgroup {
            color: snow;
            background-color: #0e84b5;
            margin-top: 16px;
            margin-bottom: 12px;
        }

        #actions {
            position: absolute;
            bottom: 2rem;
        }

        .whowhn {
            font-size: 11px;
        }

        .scroll {
            color: white;
            cursor: pointer;
            position: fixed;
            top: 50%;
            right: 1%;
            font-size: 32px;
            z-index: 1000;
            opacity: 0.6;
            display: none;
        }

        .scroll:hover {
            opacity: 1;
        }

        .scrollup {
            top: 10px;
        }

        .scrolldown {
            top: 90%;
        }
    </style>

    <div class="container card my-2 col-md-6">
        <h4 class="pt-2">Редактирование прав для роли доступа
            <span class="float-right">
				<a class="btn btn-close btn-light btn-sm"
                   href="{{ $retURL }}">
					<i class="fa fa-times" aria-hidden="true"></i>
				</a>
			</span>
            <div class="text-center">Роль: {{$role->name}}</div>
        </h4>


        <form action="{{ route('acl_roles.update_rights', ['id'=>$role->id]) }}">
            {{ Form::hidden('retURL', $retURL) }}

            <div class="accordion" id="accordionExample">

                <?php $prevObjID = ""; ?>
                @foreach($sysfunclst as $itm)
                    @if ($prevObjID != $itm->objid)
                        @if ($prevObjID != "")
            </div>
    </div></div>
    @endif

    <div class="card">
        <div class="card-header" id="heading{{$itm->objid}}">
            <h2 class="mb-0">
                <button type="button" class="btn btn-link sysobjgroup0" data-toggle="collapse"
                        data-target="#collapse{{$itm->objid}}">
                    {{$itm->objname}} ({{$itm->objcode}})
                </button>
            </h2>
        </div>
        <div id="collapse{{$itm->objid}}" class="collapse" aria-labelledby="heading{{$itm->objid}}"
             data-parent="#accordionExample">
            <div class="card-body">

                <?php $prevObjID = $itm->objid; ?>
                @endif
                <?php
                if (!isset($itm->adminrightid)) {
                    $readonly = " disabled";
                } else {
                    $readonly = "";
                }

                if (isset($subj_rights[$itm->id])) {
                    $checked = "checked";
                    $roleset = $subj_rights[$itm->id][0]->userset;
                    $created_at = $subj_rights[$itm->id][0]->created_at;
                    $whowhn = "(" . $created_at . ", " . $roleset . ")";
                } else {
                    $checked = "";
                    $whowhn = "";
                }
                ?>


                <div class="row">
                    <div class="offset-md-1 col-md-11">
                        <input type="checkbox" {{$checked}} id="cb{{$itm->id}}"
                               name="rightid[]"
                               value="{{$itm->id}}"
                            {{$readonly}}
                        />
                        <label for="cb{{$itm->id}}">
                            {{$itm->funcname}} ({{$itm->id}})

                        </label>
                        <span class='whowhn'>
							{{$whowhn}}
						</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>


    <div class="row" id="actions0">
        <div class="col-md-12 text-center my-3">
            <button type="submit" class="btn btn-success">
                <i class="fa fa-floppy-o" aria-hidden="true"></i>
                Сохранить
            </button>

            <a class="btn btn-close btn-info" href="{{ route('acl_roles.edit', $role->id) }}">
                <i class="fa fa-window-close-o" aria-hidden="true"></i>
                Закрыть
            </a>
        </div>
    </div>

    </form>

    </div>
    <div id="finish"></div>

    <div id="btn_up" class="scroll scrollup" title="Наверх" onclick="to_top();">
        <i class="fa fa-caret-square-o-up" aria-hidden="true"></i>
        {{--        <span class="screen-reader-text">Перейти наверх</span>--}}
        {{--        <svg width="32" height="32" viewBox="0 0 100 100">--}}
        {{--            <path fill="white"--}}
        {{--                  d="m50 0c-13.262 0-25.98 5.2695-35.355 14.645s-14.645 22.094-14.645 35.355 5.2695 25.98 14.645 35.355 22.094 14.645 35.355 14.645 25.98-5.2695 35.355-14.645 14.645-22.094 14.645-35.355-5.2695-25.98-14.645-35.355-22.094-14.645-35.355-14.645zm20.832 62.5-20.832-22.457-20.625 22.457c-1.207 0.74219-2.7656 0.57812-3.7891-0.39844-1.0273-0.98047-1.2695-2.5273-0.58594-3.7695l22.918-25c0.60156-0.61328 1.4297-0.96094 2.2891-0.96094 0.86328 0 1.6914 0.34766 2.293 0.96094l22.918 25c0.88672 1.2891 0.6875 3.0352-0.47266 4.0898-1.1562 1.0508-2.9141 1.0859-4.1133 0.078125z"></path>--}}
        {{--        </svg>--}}
    </div>
    <div id="btn_down" class="scroll scrolldown" title="Вниз" onclick="to_bottom();">
        <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
        {{--        <span class="screen-reader-text">Перейти наверх</span>--}}
        {{--        <svg width="32" height="32" viewBox="0 0 100 100">--}}
        {{--            <path fill="red"--}}
        {{--                  d="m50 0c-13.262 0-25.98 5.2695-35.355 14.645s-14.645 22.094-14.645 35.355 5.2695 25.98 14.645 35.355 22.094 14.645 35.355 14.645 25.98-5.2695 35.355-14.645 14.645-22.094 14.645-35.355-5.2695-25.98-14.645-35.355-22.094-14.645-35.355-14.645zm20.832 62.5-20.832-22.457-20.625 22.457c-1.207 0.74219-2.7656 0.57812-3.7891-0.39844-1.0273-0.98047-1.2695-2.5273-0.58594-3.7695l22.918-25c0.60156-0.61328 1.4297-0.96094 2.2891-0.96094 0.86328 0 1.6914 0.34766 2.293 0.96094l22.918 25c0.88672 1.2891 0.6875 3.0352-0.47266 4.0898-1.1562 1.0508-2.9141 1.0859-4.1133 0.078125z"></path>--}}
        {{--        </svg>--}}
    </div>

    <script src="{{ asset('js/usersysrights_edit.js') }}" defer></script>

@endsection
