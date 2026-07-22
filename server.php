<?php

$publicPath = getcwd();
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
$storefrontPages = [
    '/cart.html',
    '/catalog.html',
    '/forgot-password.html',
    '/index.html',
    '/login.html',
    '/orders.html',
    '/product.html',
    '/profile.html',
    '/register.html',
    '/reset-password.html',
    '/whatsapp.html',
];

if ($uri !== '/'
    && ! in_array($uri, $storefrontPages, true)
    && file_exists($publicPath.$uri)) {
    return false;
}

$formattedDateTime = date('D M j H:i:s Y');
$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $publicPath.'/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require_once $publicPath.'/index.php';
