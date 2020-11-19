<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Styles -->
    <link href="{{ asset('css/templates.css?') . time() }}" rel="stylesheet">
</head>
<body>
<div id="app">
    <div class="container">
        {!! $data['html_content'] !!}
    </div>
</div>

</body>
</html>
