<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru-ru" lang="ru-ru" dir="ltr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

    <link href="/templates/flex/css/template.css" rel="stylesheet" type="text/css"/>

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

@include('www._header-top-bar')

<div class="container">
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
                    <a href="/catalog">Перейти в каталог</a>
                </div>
            </div>
        </div>
    </div>
</div>

@include('www._footer')
</body>
</html>
