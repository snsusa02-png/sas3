<!DOCTYPE html>
<html lang="ru-ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow"/>
    <title>500</title>


    <script src="/bootstrap/4.1.3/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="/bootstrap/4.1.3/css/bootstrap.min.css">

    {{--    <link href="/templates/flex/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>--}}

    <link href="/templates/flex/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>

    <link href="/templates/flex/css/template.css" rel="stylesheet" type="text/css"/>

    <style>
        body,
        .main {
            min-height: 70vh;
        }
    </style>


    <style type="text/css">
        body {
            /*margin-top: 150px;*/
            background-color: #C4CCD9;
        }

        .center {
            margin: 0;
            position: absolute;
            top: 45%;
            left: 50%;
            -ms-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
            padding:3px 18px;
        }
        .error-main {
            /*margin-top: 10vh;*/
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

        .notfound{
            background-color: #fff;
            min-height: 200px;
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
<body class="container-fluid d-flex flex-column h-100 align-items-center px-0">
<div class="row grow w-100">
    <div class="col-12 bg-primary p-0">
    </div>

    <div class="main col-12 h-100 ">

        <div class="center notfound text-center error-main pb-4">
            <h1 class="m-0">500</h1>
            <h6>Сервер столкнулся с неожиданной ошибкой - daloil.com</h6>
            <p>Попробуйте повторить запрос позднее. А сейчас можно перейти на
                главную страницу или в каталог. </p>
            <a href="/">Вернуться на главную</a>
            <a href="/catalog">Перейти в каталог</a>
        </div>

    </div>
    <div class="row w-100">
        <div class="col-12 p-0 bg-danger">
        </div>
    </div>
</body>
</html>
