<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$request = Http::get(
    url: 'https://httpbin.org/get',
    query: ['some' => 'value']
);

// Check what we received
render_example('Get Request', $request);
