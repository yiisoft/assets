# Internals

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

## Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
composer run infection
```

## Static analysis

The code is statically analyzed with [PHPStan](https://phpstan.org/). To run static analysis:

```shell
composer run phpstan
```

## Code style

Use [Rector](https://github.com/rectorphp/rector) to make codebase follow some specific rules or
use either newest or any specific version of PHP:

```shell
./vendor/bin/rector
```

## Dependencies

This package uses [composer-require-checker](https://github.com/maglnet/ComposerRequireChecker) to check if
all dependencies are correctly defined in `composer.json`. To run the checker, execute the following command:

```shell
./vendor/bin/composer-require-checker
```
