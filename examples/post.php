<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$request = Http::post(
    'http://httpbin.org/post',
    ['mydata' => 'something']
);

// Check what we received
dump([
    "status" => $request->status(),
    "collection" => $request->array(),
]);
