@extends('layouts.app')

@section('content')
    <?php
    $request = request();
    $tab = $request->get('tab');
    $tab = is_null($tab) ? '#nsi-dic' : '#' . $tab;
    //    var_dump($tab);
    $tabs = array(    );

    //$tabs[] = ['name' => 'Библиотека', 'id' => 'nsi-lib-tab', 'href' => '#nsi-lib', 'toggle'=>'pill' ];

    //$tabs[] = ['name' => 'Офис', 'id' => 'nsi-office-tab', 'href' => '#nsi-office', 'toggle'=>'pill'];

    //$tabs[] = ['name' => 'Отчеты', 'id' => 'nsi-rep-tab', 'href' => '#nsi-rep'];
    $tabs[] = ['name' => 'Отчеты', 'id' => 'nsi-rep-tab', 'href' => route('reports.pub_index'), 'toggle'=>''];

    $tabs[] = ['name' => 'Справочники', 'id' => 'nsi-dic-tab', 'href' => '#nsi-dic', 'toggle'=>'pill'];

    if (1==0 and \App\usrsysright::isUserHasRightByCode(\Auth::user()->id, 'wrhdocs.read'))
        $tabs[] = ['name' => 'Учет склада', 'id' => 'nsi-stock-tab', 'href' => '#nsi-stock', 'toggle'=>'pill'];

    if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id, 'users.read'))
        $tabs[] = ['name' => 'Admin', 'id' => 'nsi-admin-tab', 'href' => '#nsi-admin', 'toggle'=>'pill'];


    ?>
    <link rel="stylesheet" href="/css/tags.css">
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function () {
            $('#nsi-tabs a[href="{{$tab}}"][class="nav-link"]').tab('show')
        });
    </script>

    <div class="container">
        @include('layouts.edit_msgs')

        <div class="row">
            <div class="col-md-2">

                <div class="nav flex-column nav-pills justify-content-center"
                     id="nsi-tabs"
                     role="tablist"
                     aria-orientation="vertical">
                    @foreach($tabs as $tb)

                        <a class="nav-link" id="{{$tb['id']}}"
                           data-toggle="{{$tb['toggle']}}" href="{{$tb['href']}}"
                           role="tab"
                        >{{$tb['name']}}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="col-md-10">
                <main class="py-4">

                    <div class="tab-content" id="nsi-tabContent">

                    <!-- @includeIf('admin.tab_fsd') -->
                        @includeIf('admin.tab_lib')
                        @includeIf('admin.tab_office')
                        @include('admin.tab_rep')
                        @includeIf('admin.tab_mchnrqsts')
                        @includeIf('admin.tab_dic')
                        @includeIf('admin.tab_stock')
                        @includeIf('admin0.tab_aux')
                        @includeIf('admin.tab_admin')

                    </div>
                </main>
            </div>
        </div>
    </div>

@endsection
