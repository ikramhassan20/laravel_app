<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NewsFeed(s)</title>
    {{--<link rel="shortcut icon" type="image/png" href="./src/assets/images/favicon.png">--}}
    <link rel="stylesheet" type="text/css" href="{{asset('/css/newsFeed_templates.css')}}">
    <link rel="stylesheet" type="text/css"
          href="https://fonts.googleapis.com/css?family=Lato:300,400,400i,700,700i,900,900i">
</head>
<body>
@if(empty($htmlArr))
    <style>
        .nfc_template_view .content_holder div {
            max-width: none !important;
        }

        .nfc_template_view .content_holder div img {
            max-width: 210px !important;
        }

        @media only screen and (max-width: 585px) {
            .cap_text {
                width: 64% !important;
            }
        }
    </style>
    <div class="col-lg-10 col-lg-offset-1 nfc_template_view">
        <h1>No notifications yet!</h1>
    </div>
@else
    {{$htmlArr['template']}}
@endif

</body>
</html>