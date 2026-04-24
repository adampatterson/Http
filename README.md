# HTTP PHP

![PHP Composer](https://github.com/adampatterson/Http/workflows/PHP%20Composer/badge.svg?branch=main)

Started off as a little wrapper around Guzzle, now is a clone of ZTTP.

> [!NOTE]
> This script is still under development.

## Install from [Packagist](https://packagist.org/packages/adampatterson/http)

```shell
composer require adampatterson/http
```

## Tests

```shell
composer install
composer test
```

## Local Dev

Without needing to modify the composer.json file. Run from the theme root, this will symlink the package into the theme's vendor directory.

```shell
ln -s ~/Sites/packages/Http/ ./vendor/adampatterson/http
```

Otherwise, you can add the local package to your `composer.json` file.

```shell
"repositories": [
    {
        "type": "path",
        "url": "/Sites/packages/http"
    }
],
```
