<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$request = Http::patch(
    url: 'https://httpbin.org/patch',
    params: ['mydata' => 'something']
);

// Check what we received
dump([
    "status" => $request->status(),
    "collection" => $request->array(),
]);

