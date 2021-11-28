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
<div class="row grow w-100">
    <div class="col-12 bg-primary py-3">
        Header
    </div>

    <div class="main col-12 bg-warning h-100 py-3">
        <h4>Main</h4>
        <p class="mb-5">Sriracha biodiesel taxidermy organic post-ironic, Intelligentsia salvia mustache 90's code
            editing brunch. Butcher polaroid VHS art party, hashtag Brooklyn deep v PBR narwhal sustainable mixtape
            swag wolf squid tote bag. Tote bag cronut semiotics, raw denim deep v taxidermy messenger bag. Tofu YOLO
            Etsy, direct trade
            ethical Odd Future jean shorts paleo. Forage Shoreditch tousled aesthetic irony, street art organic Bushwick
            artisan cliche semiotics ugh
            synth chillwave meditation. Shabby chic lomo plaid vinyl chambray Vice. Vice sustainable cardigan,
            Williamsburg master cleanse hella DIY 90's blog.</p>
    </div>
</div>
<div class="row w-100">
    <div class="col-12 py-3 bg-danger">
        Footer
    </div>
</div>
</body>
</html>


<div class="row text-center">
    <div
        class="col-lg-8 offset-lg-2 col-lg-offset-2 col-sm-6 offset-sm-3 col-sm-offset-3 col-12 p-3 error-main">
        <div class="row">
            <div
                class="col-lg-8 col-12 col-sm-10 offset-lg-2 col-lg-offset-2 offset-sm-1 col-sm-offset-1 notfound">
                <div
                    class="col-lg-8 col-12 col-sm-10 offset-lg-2 col-lg-offset-2 offset-sm-1 col-sm-offset-1 notfound">
                    <h1 class="m-0">404</h1>
                    <h6>Страница не найдена - daloil.com</h6>
                    <p>Возможно, вы перешли по ссылке, в которой была допущена ошибка, или ресурс был удален.
                        Попробуйте перейти на
                        главную страницу или в каталог. </p>
                    <a href="/">Вернуться на главную</a>
                    <a href="/catalog">Перейти в каталог</a>
                    <br><br>
                </div>
            </div>
        </div>
    </div>

</div>
