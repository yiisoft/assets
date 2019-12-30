<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://github.com/yiisoft.png" height="100px">
    </a>
    <h1 align="center">Yii Assets</h1>
    <br>
</p>

The package impements clientside asset management such as CSS and JavaScript.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/assets/v/stable.png)](https://packagist.org/packages/yiisoft/assets)
[![Total Downloads](https://poser.pugx.org/yiisoft/assets/downloads.png)](https://packagist.org/packages/yiisoft/assets)
[![Build Status](https://travis-ci.com/yiisoft/assets.svg?branch=master)](https://travis-ci.com/yiisoft/assets)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/assets/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/assets/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/assets/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/assets/?branch=master)


### How to install:

composer:

~~~
composer require yiisoft/assets
~~~

Or add to composer.json:

~~~
"yiisoft/assets": "^1.0@dev"
~~~

### How to usages:

- [AssetManager](docs/assetmanager.md)
- [AssetBundles](docs/assetbundles.md)

### Run tests PHPUNIT:


Download all composer dependencies root project:
~~~
$ composer update --prefer-dist -vvv
~~~

Run phpunit:
~~~
$ vendor/bin/phpunit
~~~
