<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Assets</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/assets/v)](https://packagist.org/packages/yiisoft/assets)
[![Total Downloads](https://poser.pugx.org/yiisoft/assets/downloads)](https://packagist.org/packages/yiisoft/assets)
[![Build status](https://github.com/yiisoft/assets/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/assets/actions/workflows/build.yml)
[![Code Coverage](https://codecov.io/gh/yiisoft/assets/graph/badge.svg?token=U09s7jclX6)](https://codecov.io/gh/yiisoft/assets)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fassets%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/assets/master)
[![Static analysis](https://github.com/yiisoft/assets/actions/workflows/psalm.yml/badge.svg?branch=master)](https://github.com/yiisoft/assets/actions/workflows/psalm.yml?query=branch%3Amaster)

The package implements client-side asset (such as CSS and JavaScript) management for PHP.
It helps resolve dependencies and get lists of files ready for generating HTML `<script>` and `<link>` tags.

## Requirements

- PHP 8.1 or higher.
- `mbstring` PHP extension.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/assets
```

## General usage

There are three main steps using the package:

1. [Define asset bundles](docs/guide/en/asset-bundles.md). These are config classes defining where your assets
   are and how they should be used.
2. [Register bundles to asset manager](docs/guide/en/asset-manager.md) and obtain list of files to include.
3. Optionally [use asset converter with asset bundle](docs/guide/en/asset-converter.md) for asset format conversion
   (such as TypeScript to JavaScript).
4. Use your favorite method to include files into HTML (out of scope of this package).

## Documentation

- Guide: [English](docs/guide/en/README.md), [Português - Brasil](docs/guide/pt-BR/README.md)
- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Assets is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
