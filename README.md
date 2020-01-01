<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://github.com/yiisoft.png" height="100px">
    </a>
    <h1 align="center">Yii Assets</h1>
    <br>
</p>

The package impements clietside asset (such as CSS and JavaScript) management for PHP.
It helps resolving dependencies and getting lists of files ready for generating HTML `<script` and `<link` tags.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/assets/v/stable.png)](https://packagist.org/packages/yiisoft/assets)
[![Total Downloads](https://poser.pugx.org/yiisoft/assets/downloads.png)](https://packagist.org/packages/yiisoft/assets)
[![Build Status](https://travis-ci.com/yiisoft/assets.svg?branch=master)](https://travis-ci.com/yiisoft/assets)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/assets/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/assets/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/assets/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/assets/?branch=master)


## Intallation

The package could be installed via composer:

```
composer require yiisoft/assets
```

## Usage

There are three main steps using the package:

1. [Define asset bundles](docs/asset-bundles.md). These are config classes defining where your assets
   are and how they should be used.
2. [Register bundles to asset manager](docs/asset-manager.md) and obtain list of files to include.
3. [Using asset converter with asset bundle](docs/asset-converter.md) for support conversion.
4. Use your favorite method to include files into HTML (out of scope of this package). 

## Tests

The package is tested with PHPUnit. Tests could be run with

```
./vendor/bin/phpunit
```
