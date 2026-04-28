<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$request = Http::post(
    'http://httpbin.org/post',
    ['mydata' => 'something']
);

render_example('Post Request', $request);
