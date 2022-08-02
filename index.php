<?php
require "./vendor/autoload.php";

$address = '127.0.0.1';
$port = '8080';

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, $address, $port);

function GetHeader(string $cache_control): string
{
    return
        "HTTP/1.1 200 OK \r\n" .
        "Cache-Control: $cache_control \r\n" .
        "Date:" . date('m-d-Y h:i:s a', strtotime("now")) . "\r\n" .
        "Content-Type: text/html \r\n\r\n";
}

while (true) {
    socket_listen($socket);
    $client = socket_accept($socket);

    $input = socket_read($client, 1024);
    $incomming = array();
    $incomming = explode('\r\n', $input);
    $fechArray = array();
    $fechArray = explode(" ", $incomming[0]);

    $file = $fechArray[1];

    $file = ($file == "/" ?  "index.html" : __DIR__ . "$file.html");

    $cacheKey = md5($file);
    $redis = new Predis\Client();

    if ($redis->exists($cacheKey)) {
        $cacheContent = $redis->get($cacheKey);

        $cacheOutput = GetHeader('max-age=10800') . $cacheContent;
        socket_write($client, $cacheOutput, strlen($cacheOutput));
        socket_close($client);
    } else {
        $Content = (file_get_contents($file) ? file_get_contents($file) : file_get_contents("notfound.html"));

        $output = $Header . $Content;

        $output = GetHeader('no-cache, no-store, max-age=0') . $Content;
        $redis->setEx($cacheKey, 10, $Content);
        socket_write($client, $output, strlen($output));
        socket_close($client);
    }
}
