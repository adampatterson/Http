<?php

use Http\Http;

include "setup.php";

// Now let's make a request!
$response = Http::delete(
    url: 'https://httpbin.org/delete',
    params: ['some' => 'value']
);

render_example('Delete Request', $response);
