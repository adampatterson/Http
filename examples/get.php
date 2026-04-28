<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$request = Http::get(
    url: 'https://httpbin.org/get',
    query: ['some' => 'value']
);

// Check what we received
dump([
    "status" => $request->status(),
    "collection" => $request->array(),
]);
