@extends('layouts.app')
@section('content')

    <?php
    $sysobjid = 101;
    $sysobjcode = 'itmtypes';

    $thisTitle = "Категории номенклатуры";
    ?>

    <style type="text/css">
        li, ul {
            list-style: none;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        li ul {
            margin-left: 16px;
            height: 0px;
        }

        li input {
            display: none;
        }

        li input:checked ~ ul {
            height: auto;
        }

        label {
            margin-bottom: 0;
        }

        /*Это квадратик с плюсиком +, когда нижележащий список свёрнут*/
        input + label:before {
            content: "";
            display: inline-block;
            height: 16px;
            width: 16px;
            background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAsAAAALCAIAAAAmzuBxAAAACXBIWXMAAAsSAAALEgHS3X78AAAAkElEQVQYlXWOvRWDQAyDv/DYK2wQSro8OkpGuRFcUjJCRmEE0TldCpsjPy9qzj7Jki62Pgh4vnqbbbEWuN+use/PlArwHccWGg780psENGFY6W4YgxZIAM339WmT3m397YYxxn6aASslFfVotYLTT3NwcuTKlFpNR2sdEak4acdKeafPlE2SZ7sw/1BEtX94AXYTVmyR94mPAAAAAElFTkSuQmCC) no-repeat 0px 5px;
        }

        /* Это минус в квадратике -, когда нижележащий список раскрыт */
        input:checked + label:before {
            background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAsAAAALCAIAAAAmzuBxAAAACXBIWXMAAAsSAAALEgHS3X78AAAAeklEQVQYlX2PsRGDMAxFX3zeK9mAlHRcupSM4hFUUjJCRpI70VHIJr7D8BtJ977+SQ9Zf7isVG16WSQC0/D0OW/FqoBlDFkIVJ2xAhA8sI/NHbcYiFrPfI0fGklKagDx2F4ltdtaM0J9L3dxcVxi+zv62E+MwPs7c60dClRP6iug7wUAAAAASUVORK5CYII=) no-repeat 0px 5px;
        }
    </style>

    <style00000>
        * {font:14px Arial;}
        input {display:none;}
        li, ul{box-sizing:border-box; list-style:none; margin:0; padding:0; overflow:hidden;}
        li {padding-left:16px;}
        li ul{height:0;}
        li label {
            height:20px;
            line-height:20px;
            padding-left:16px;
            cursor:pointer;
            background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAsAAAALCAIAAAAmzuBxAAAACXBIWXMAAAsSAAALEgHS3X78AAAAkElEQVQYlXWOvRWDQAyDv/DYK2wQSro8OkpGuRFcUjJCRmEE0TldCpsjPy9qzj7Jki62Pgh4vnqbbbEWuN+use/PlArwHccWGg780psENGFY6W4YgxZIAM339WmT3m397YYxxn6aASslFfVotYLTT3NwcuTKlFpNR2sdEak4acdKeafPlE2SZ7sw/1BEtX94AXYTVmyR94mPAAAAAElFTkSuQmCC) no-repeat 2px 2px;
        }
        li input:checked ~ ul {height:auto;}
        li input:checked + label{
            background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAsAAAALCAIAAAAmzuBxAAAACXBIWXMAAAsSAAALEgHS3X78AAAAeklEQVQYlX2PsRGDMAxFX3zeK9mAlHRcupSM4hFUUjJCRpI70VHIJr7D8BtJ977+SQ9Zf7isVG16WSQC0/D0OW/FqoBlDFkIVJ2xAhA8sI/NHbcYiFrPfI0fGklKagDx2F4ltdtaM0J9L3dxcVxi+zv62E+MwPs7c60dClRP6iug7wUAAAAASUVORK5CYII=) no-repeat 2px 2px;
        }
    </style00000>
    </head>
    <body>
    <p style="color:#444;">Дерево без javascript. Раскрыть уровень можно не только по знаку плюсика, но и просто щелкнув по тексту. </p>
    <ul>
        <li>
            <input type="checkbox" id="chk1_1"><label for="chk1_1">Малкольм Мерлин</label>
            <ul>
                <li>
                    <input type="checkbox" id="chk1_1_1"><label for="chk1_1_1">Ты подвел этот город</label>
                    <ul>
                        <li>
                            <input type="checkbox" id="chk1_1_1_1"><label for="chk1_1_1_1">И как это я умудрился?</label>
                            <ul>
                                <li>Предприятие.</li>
                                <li>Я его остановлю.</li>
                            </ul>
                        </li>
                    </ul>
                </li>
            </ul>
        </li>
        <li>
            <input type="checkbox" id="chk2_1"><label for="chk2_1">Кто же убил Сару Лэнс?</label>
            <ul>
                <li>
                    <input type="checkbox" id="chk2_1_1"><label for="chk2_1_1">Варианты зрителей</label>
                    <ul>
                        <li>Тея Куин</li>
                        <li>Малкольм Мерлин</li>
                        <li>Слейд Уилсон</li>
                        <li>По приказу А.Р.Г.У.С.А</li>
                    </ul>
                </li>
                <li>
                    <input type="checkbox" id="chk2_2_1"><label for="chk2_2_1">Что нам показали в 9 серии</label>
                    <ul>
                        <li>Тея Куин, под влиянием Малкольма</li>
                        <li>Но по логике очень не сходится</li>
                    </ul>
                </li>
            </ul>
        </li>
    </ul>

    <div class="container">

        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <a class="breadcrumb-item" href="/nsi">Данные</a>
                    <a class="breadcrumb-item" href="/nsi?tab=nsi-catalog">Каталог</a>
                    <span class="breadcrumb-item active">{{$thisTitle}}</span>
                </nav>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <h3>{{$thisTitle}}</h3>

                <input type="text" name="itmtypeid" id="itmtypeid">
                <ul>
                    @foreach($categories as $category)
                        <li>
                            <span class="itmtypeid "
                                  itmtypeid="{{$category->id}}">{{$category->name}}</span>
                            @if($category->sub->count())
                                <input type="checkbox" id="chk{{$category->id}}"><label
                                        for="chk{{$category->id}}"></label>
                                @include('itmtypes.sub_tree', ['categories' => $category->sub])
                            @endif
                            {{--                            <ul>--}}
                            {{--                                @foreach(\App\refitem::where('itmtypeid',$category->id)->orderby('name')->get() as $itm)--}}
                            {{--                                    <li>{{$itm->name}}</li>--}}
                            {{--                                @endforeach--}}
                            {{--                            </ul>--}}
                        </li>
                    @endforeach
                </ul>

            </div>
        </div>
        <script src="{{ asset('js/tst119.js') }}" defer></script>

@endsection

