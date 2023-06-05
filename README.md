# HTTP PHP
![PHP Composer](https://github.com/adampatterson/Http/workflows/PHP%20Composer/badge.svg?branch=main)

Started off as a little wrapper around Guzzle, now is a clone of ZTTP.

**Changes:**
* withToken
* Removed Macroable
* Uses `"guzzlehttp/guzzle": "7.4.x"` 

This script is still under development.

## Install from [Packagist](https://packagist.org/packages/adampatterson/http)

## Tests

```
$ composer global require phpunit/phpunit
$ export PATH=~/.composer/vendor/bin:$PATH
$ which phpunit
~/.composer/vendor/bin/phpunit
```

`composer run-script test`

## Local Dev

`ln -s ~/Sites/personal/_packages/Http/ ~/Sites/personal/projectName/vendor/adampatterson/http`
