<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$response = Http::asFormParams()
    ->post(
        url: 'https://httpbin.org/post',
        params: ['name' => 'John']
    );

// Check what we received
dump([
    "status" => $response->status(),
    "body"   => $response->body(),
]);
