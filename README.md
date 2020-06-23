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
[![Build Status](https://github.com/yiisoft/assets/workflows/build/badge.svg)](https://github.com/yiisoft/assets/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/assets/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/assets/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/assets/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/assets/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fassets%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/assets/master)
[![static analysis](https://github.com/yiisoft/assets/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/assets/actions?query=workflow%3A%22static+analysis%22)


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
3. Optionally [use asset converter with asset bundle](docs/asset-converter.md) for asset format conversion (such as TypeScript to JavaScript).
4. Use your favorite method to include files into HTML (out of scope of this package). 

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```php
./vendor/bin/phpunit
```

## Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```php
./vendor/bin/infection
```

## Static analysis

The code is statically analyzed with [Phan](https://github.com/phan/phan/wiki). To run static analysis:

```php
./vendor/bin/phan
```
