<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$response = Http::withHeaders([
    'X-Custom' => 'value',
])
    ->post(url: 'https://httpbin.com/post');

// Check what we received
dump([
    "status" => $response->status(),
    "body"   => $response->body(),
]);
