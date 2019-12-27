<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://github.com/yiisoft.png" height="100px">
    </a>
    <h1 align="center">Yii Library Assets</h1>
    <br>
</p>

The package impements clientside asset management such as CSS and JavaScript.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/assets/v/stable.png)](https://packagist.org/packages/yiisoft/assets)
[![Total Downloads](https://poser.pugx.org/yiisoft/assets/downloads.png)](https://packagist.org/packages/yiisoft/assets)
[![Build Status](https://travis-ci.com/yiisoft/assets.svg?branch=master)](https://travis-ci.com/yiisoft/assets)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/assets/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/assets/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/assets/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/assets/?branch=master)


### REQUIREMENTS:

- The minimum requirement by this project template that your Web server supports:
    - PHP 7.4 or higher.
    - [Composer Config Plugin](https://github.com/hiqdev/composer-config-plugin)

### HOW TO INSTALL:

<p>
If you do not have <a href="http://getcomposer.org/" title="Composer" target="_blank">Composer</a>, you may install it by following the instructions at <a href="http://getcomposer.org/doc/00-intro.md#installation-nix" title="getcomposer.org" target="_blank">getcomposer.org</a>.
</p>

composer:

~~~
composer require yiisoft/assets
~~~

Or add to composer.json:

~~~
"yiisoft/assets": "^1.0@dev"
~~~

### HOW TO CONFIG ASSETMANAGER: ###

- [Di-Container](docs\di-container-config.md)
- [Without Di-Container](docs\without-di-container-config.md)

### API: ###

- [Api AssetManager](docs\api-assetmanager.md)

### RUN TESTS PHPUNIT:


Download all composer dependencies root project:
~~~
$ composer update --prefer-dist -vvv
~~~

Run phpunit:
~~~
$ vendor/bin/phpunit
~~~
