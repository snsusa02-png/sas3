<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow"/>
    <title>blah</title>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        body,
        wrapper {
            min-height: 100vh;
        }

        #canvas {
            min-height: 100%;
        }

        #mainstage {
            overflow: auto;
        }

        #footer {
            left: 0;
            bottom: 0;
            width: 100%;
            clear: both;
            height: 290px;
            margin-top: -151px;
            /*font-size: 16;*/
            text-rendering: auto;
            position: fixed;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .tableft {
            float: left;
            background-color: #00001A;
            width: 40%;
            height: 250px;
            color: white;
            background-repeat: no-repeat;
            background-attachment: fixed;


        }

        .tabrigth {
            float: right;
            background-color: #00001A;
            width: 35%;
            height: 250px;
            color: white;
            background-repeat: no-repeat;
            background-attachment: fixed;

        }

        .tabcenter {
            float: right;
            background-color: #00001A;
            width: 25%;
            height: 250px;
            color: white;
            background-repeat: no-repeat;
            background-attachment: fixed;

        }

        p {
            margin-left: 40px;
            margin-right: 80px;
        }
    </style>

    <style type="text/css">
        body {
            /*margin-top: 150px;*/
            background-color: #C4CCD9;
        }

        .error-main {
            margin-top: 100px;
            background-color: #fff;
            box-shadow: 0px 10px 10px -10px #5D6572;
        }

        .error-main h1 {
            font-weight: bold;
            color: #444444;
            font-size: 100px;
            text-shadow: 2px 4px 5px #6E6E6E;
        }

        .error-main h6 {
            color: #42494F;
        }

        .error-main p {
            color: #9897A0;
            font-size: 14px;
        }

        .notfound a {
            font-family: 'Maven Pro', sans-serif;
            font-size: 14px;
            text-decoration: none;
            text-transform: uppercase;
            background: #21a117;
            display: inline-block;
            margin-top: 4px;
            padding: 8px 30px;
            border: 1px solid transparent;
            border-radius: 4px;
            color: #fff;
            font-weight: 400;
            -webkit-transition: 0.2s all;
            transition: 0.2s all;
        }

        .notfound a:hover {
            background-color: #14a78b;
            border-color: #204d74;
            color: #fff;
        }
    </style>

</head>
<body>
<wrapper class="d-flex flex-column">
    <nav class="navbar navbar-light navbar-expand-sm bg-light">
        <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse"
                data-target="#collapsingNavbar2">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-collapse collapse" id="collapsingNavbar2">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="{%url 'home'%}">Home </a></li>
                <li class="nav-item"><a a class="nav-link" href="{%url 'contato'%}">Contacto </a></li>
                <li class="nav-item"><a a class="nav-link" href="{%url 'detalhes'%}">Novidade e Detalhes </a></li>
                <li class="nav-item"><a a class="nav-link" href="{%url 'add'%}">Cadastrar </a></li>
            </ul>
        </div>
    </nav>
    <main class="container-fluid py-3 flex-fill">

        <div class="center notfound">
            <h1 class="m-0">404</h1>
            <h6>Страница не найдена - daloil.com</h6>
            <p>Возможно, вы перешли по ссылке, в которой была допущена ошибка, или ресурс был удален.
                Попробуйте перейти на
                главную страницу или в каталог. </p>
            <a href="/">Вернуться на главную</a>
            <a href="/catalog">Перейти в каталог</a>
        </div>

    </main>
    <footer class="container-fluid w-100 bg-dark text-light py-3">
        <div id="footer">

            @include('www._footer')

        </div>
    </footer>
</wrapper>
</body>
</html>
