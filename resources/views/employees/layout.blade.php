<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#69ccfd">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Paradiso Dipendenti">
    <title>@yield('title') · Il Paradiso della Frutta</title>
    <link rel="icon" type="image/png" href="/assets/images/icona-web.png">
    <link rel="manifest" href="/employee-manifest.webmanifest">
    <link rel="apple-touch-icon" href="/assets/pwa/employees/apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/employee-portal.css?v={{ filemtime(public_path('assets/css/employee-portal.css')) }}">
</head>
<body>
    @yield('content')
    <script src="/assets/js/employee-portal.js?v={{ filemtime(public_path('assets/js/employee-portal.js')) }}" defer></script>
    <script type="module" src="/assets/js/employee-pwa.js?v=20261004.1"></script>
</body>
</html>
