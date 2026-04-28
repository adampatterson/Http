<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$request = Http::put(
    'http://httpbin.org/put',
    ['mydata' => 'something']
);

render_example('Put Request', $request);
