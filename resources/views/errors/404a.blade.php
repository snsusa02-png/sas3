<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow"/>
    <title>404</title>

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
            height: 190px;
            margin-top: -151px;
            font-size: 16;
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

        <div class="row text-center">
            <div class="col-lg-8 offset-lg-2 col-sm-6 offset-sm-3 col-12 p-3 error-main">
                <div class="row">
                    <div class="col-lg-8 col-12 col-sm-10 offset-lg-2 offset-sm-1 notfound">
                        <h1 class="m-0">404</h1>
                        <h6>Страница не найдена - daloil.com</h6>
                        <p>Возможно, вы перешли по ссылке, в которой была допущена ошибка, или ресурс был удален.
                            Попробуйте перейти на
                            главную страницу или в каталог. </p>
                        <a href="/">Вернуться на главную</a>
                        <a href="/catalog" class="ml-2">Перейти в каталог</a>
                    </div>
                </div>
            </div>
        </div>

    </main>
    <footer class="container-fluid w-100 bg-dark text-light py-3">
        <div id="footer">
            <div class="tableft">
                <p>PART OF US.JOBS UNIVERSE</p>
                <p> The Porrent company of jobs and find jobs.<br> Emplay Media, LLC is the licensed operator<br> of the
                    .jobsTLD on the internet<br><br><br><br></p>
                <p>© 2018 Copyright <a href="/accounts/signup/">US.Jobs</a> . All rights reserver
            </div>
            <div class="tabcenter">
                <p>SOCIAL MEDIA</p>
                <p>
                <table>
                    <tr> ..</tr>
                </table>
                </p>
            </div>
            <div class="tabrigth">
                <p>LINK</p>
                <p>About <br>Post a Job<br>FAQ<br></p>
            </div>
        </div>
    </footer>
</wrapper>
</body>
</html>
