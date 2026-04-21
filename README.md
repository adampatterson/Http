# HTTP PHP
![PHP Composer](https://github.com/adampatterson/Http/workflows/PHP%20Composer/badge.svg?branch=main)

Started off as a little wrapper around Guzzle, now is a clone of ZTTP.

**Changes:**
* withToken
* Removed Macroable
* Uses `"guzzlehttp/guzzle": "7.4.x"` 

This script is still under development.

## Install from [Packagist](https://packagist.org/packages/adampatterson/http)

```bash
composer require adampatterson/http
```

## Tests

```bash
composer install
composer run-script test
```

## Local Dev

```bash
ln -s ~/Sites/packages/Http/ ~/Sites/projectName/vendor/adampatterson/http
```
