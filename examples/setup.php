<?php


file_exists($composer = __DIR__.'/../vendor/autoload.php')
or die("Run <code>composer install</code> from ".__DIR__);

require_once $composer;

/**
 * Check if the 'minimal' flag is passed in the command line arguments.
 */
$minimal = in_array('minimal', $argv);
$passOrFail = !in_array('fail', $argv);

if (!function_exists('render_example')) {
    /**
     * Render the example output based on the 'minimal' flag.
     *
     * @param  string  $name
     * @param  mixed  $request
     */
    function render_example(string $name, mixed $request)
    {
        global $minimal;

        if ($minimal) {
            $data = [$name => $request->status()];
        } else {
            $data = [
                'example' => $name,
                'status'  => $request->status(),
                "data"    => $request->array()
            ];
        }

        dump($data);
    }
}
