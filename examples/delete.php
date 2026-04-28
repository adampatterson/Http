<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$response = Http::delete(
    url: 'https://httpbin.org/delete',
    params: ['Accept' => 'application/json']
);

// Check what we received
dump([
    "status" => $response->status(),
    "collection" => $response->array(),
]);
