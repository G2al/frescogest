<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#064e3b">
    <title>@yield('title') · Il Paradiso della Frutta</title>
    <link rel="icon" type="image/png" href="/assets/images/icona-web.png">
    <link rel="stylesheet" href="/assets/css/employee-portal.css?v={{ filemtime(public_path('assets/css/employee-portal.css')) }}">
</head>
<body>
    @yield('content')
    <script src="/assets/js/employee-portal.js?v={{ filemtime(public_path('assets/js/employee-portal.js')) }}" defer></script>
</body>
</html>
