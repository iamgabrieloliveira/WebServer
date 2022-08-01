<?php

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, '127.0.0.1', '8080');

$cache = [];

while (true) {
    $date = date('m-d-Y h:i:s a', strtotime("now"));
    $cacheExpires = date("H:i:s", strtotime("now") + 10800);

    socket_listen($socket);
    $client = socket_accept($socket);

    $input = socket_read($client, 1024);
    $incomming = array();
    $incomming = explode('\r\n', $input);
    $fechArray = array();
    $fechArray = explode(" ", $incomming[0]);

    $file = $fechArray[1];

    $file = ($file == "/" ?  "index.html" : __DIR__ . "$file.html");

    if (isset($cache[$file]) && date("H:i:s", strtotime("now")) <= $cache[$file]['expires']) {
        $Header =
            "HTTP/1.1 200 OK \r\n" .
            "Cache-Control: max-age=10800 \r\n" .
            "Date: $date \r\n" .
            "Content-Type: text/html \r\n\r\n";

        $Content = $cache[$file]['content'];
        $output = $Header . $Content;

        socket_write($client, $output, strlen($output));
        socket_close($client);

        continue;
    } else {
        unset($cache[$file]);
    }

    $cache[$file] = [
        'content' => file_get_contents($file),
        'expires' => $cacheExpires,
    ];

    $Header =
        "HTTP/1.1 200 OK \r\n" .
        "Cache-Control: no-cache,no-store, max-age=0 \r\n" .
        "Date: $date \r\n" .
        "Content-Type: text/html \r\n\r\n";

    $Content = file_get_contents($file);

    if (!$Content) {
        $Content = file_get_contents("notfound.html");
    }

    $output = $Header . $Content;

    socket_write($client, $output, strlen($output));
    socket_close($client);
}
