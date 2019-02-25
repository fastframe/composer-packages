# FastFrame Composer Packages Plugin

This plugin provides an easy method of determining the installation path, name, version, and extra attributes of a given Compose package, by name.

Use this to locate vendor package roots. Most of the times is this going to be used for template files, or other
types of assets that you want to get from a package.

Running `composer install` or `composer update` will trigger ths Packages.php to be generated, this contains a registry
of the package installation paths as well as their types.

## Installation

`composer require fastframe/composer-packages`

## Usage

```php
$container = new FastFrame\Composer\Packages\Container();
$container->has('fastframe/composer-packages'); // true
$container->has('non-existent/package'); // false

$pkg = $container->get('fastframe/composer-packages'); // Package object

// Package object
$pkg->name();         // fastframe/composer-packages
$pkg->type();         // composer-plugin
$pkg->version();      // 1.0.0.0
$pkg->path();         // {root_path}/vendor/fastframe/composer-packages
$pkg->extra();        // array('class' => 'FastFrame\\Composer\\Packages\\Plugin')
$pkg->composerJson(); // the json decoded composer.json contents from the package

// finding all packages of a specific type
$composer_plugins = $container->getByType('composer-plugins'); // array('fastframe-composer-packages')

```

## Inspiration

This plugin was inspired by:

* [ocramius/package-versions](https://github.com/Ocramius/PackageVersions)
* [mindplay/composer-locator](https://github.com/mindplay-dk/composer-locator)