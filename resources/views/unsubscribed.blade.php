<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <title>{{ config('app.name', 'Unsubscribed') }}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="images/favicon.png">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="{{ asset('css/font-awesome-4.css') }}" media="all" rel="stylesheet">
    <link href="{{ asset('css/font-awesome-5.css') }}" media="all" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom_scroll.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/all.css') }}" media="all" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,400i,700,700i,900,900i" rel="stylesheet">
    <style>
        body{
            min-width: 320px;
        }
        @media only screen and (max-width:600px){
            .unsubscribe_holder h1 {
                font-size: 27px;
                margin: 0 0 20px;
            }
            .unsubscribe_holder h2 {
                font-size: 22px;
                margin: 11px 0 15px;
                line-height: 26px;
            }
            .unsubscribe_holder p {
                margin: 0 10px 30px;
            }
            .unsubscribe_holder p.bordered {
                padding: 17px 0 0;
                margin: 30px 20px 20px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
<div id="wrapper">
    <div class="unsubscribe_area">
        <div class="d_table">
            <div class="v_middle">
                <div class="unsubscribe_holder">
                    <h1>{{ $data['company_name'] }}</h1>
                    <img src="/images/mail-box-ico.svg" alt="#" class="mail_icon">
                    <h2>You’ve unsubscribed <br>Successfully</h2>
                    <p>You’ll no longer receive emails <br>about new offers and discounts</p>
                    <!--a href="#" class="btn_confirm">Done</a-->
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/jquery.js') }}"></script>
<script>
    $(".btn_confirm").on('click', function(){
        document.frm.submit();
    });
</script>
<script src="{{ asset('js/custom_scroll.min.js') }}"></script>
<script src="{{ asset('js/canvasjs.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/dataTable.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/custom.js') }}" type="text/javascript"></script>
</body>
</html>