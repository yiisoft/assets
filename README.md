<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://github.com/yiisoft.png" height="100px">
    </a>
    <h1 align="center">Yii Assets</h1>
    <br>
</p>

The package impements client-side asset (such as CSS and JavaScript) management for PHP.
It helps resolving dependencies and getting lists of files ready for generating HTML `<script` and `<link` tags.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/assets/v/stable.png)](https://packagist.org/packages/yiisoft/assets)
[![Total Downloads](https://poser.pugx.org/yiisoft/assets/downloads.png)](https://packagist.org/packages/yiisoft/assets)
[![Build Status](https://github.com/yiisoft/assets/workflows/build/badge.svg)](https://github.com/yiisoft/assets/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/assets/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/assets/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/assets/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/assets/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fassets%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/assets/master)
[![static analysis](https://github.com/yiisoft/assets/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/assets/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/assets/coverage.svg)](https://shepherd.dev/github/yiisoft/assets)


## Installation

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

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

## License

The Yii Assets is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
