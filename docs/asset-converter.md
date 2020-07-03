# Asset converter

Asset converter purpose is to convert assets from one format to another. Out of the box it supports conversion of
several popular formats into JavaScript and CSS.

## Configuring asset conversion

In order to use asset conversion we have to configure it first. Let's see how it's done. As example
let's convert [SCSS](https://sass-lang.com/) into CSS.  


We'll use [foxy](https://github.com/fxpio/foxy). Since it calls npm we'll need [NodeJS](https://nodejs.org/en/) installed.
After it is done, create `package.json`:

```json
{
    "license": "BSD-3-Clause",
    "dependencies": {
        "sass": "^1.24.0"
    }
}
```

`npm install` brings three packages into `node_modules` directory of our application.

Below we're using bootstrap bundle from "[Asset bundles](asset-bundles.md)" guide:

```php
namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap css files.
 *
 * BootstrapAsset.
 */
final class BootstrapAsset extends AssetBundle
{
    public ?string $basePath = '@assets';
    public ?string $baseUrl = '@assetsUrl';
    public ?string $sourcePath = '@npm/bootstrap/scss';

    public array $css = [
        'bootstrap.scss',
    ];

    public array $publishOptions = [
        'only' => [
            'bootstrap.scss',
        ],
    ];

    public array $converterLoadPath = [
        'scss' => [
            'command' => '-I',
            'path' => '@npm/bootstrap/scss',
        ]
    ];

    public array $converterOptions = [
        'scss' => '--style compressed',
    ];
}
```

Since in `$css` we are pointing to `.scss`, asset manager asks asset converter to check if such file could be converted
to CSS. By default asset converter has command definitions for less, scss, sass, styl, coffee and ts but since all these
are meant to be installed globally and we have it as local depdendency, we need to redefine a command:

```php
$assetManager->getConverter()->setCommand('scss', ['css', '@npm/.bin/sass {options} {from} {to}']);
```  

or, if done via yiisoft/di container:

```php
AssetConverterInterface::class => static function (\Psr\Container\ContainerInterface $container) {
    $aliases = $container->get(\Yiisoft\Aliases\Aliases::class);
    $logger = $container->get(\Psr\Log\LoggerInterface::class);
    $converter = new \Yiisoft\Assets\AssetConverter($aliases, $logger);
    $converter->setCommand('scss', 'css', '@npm/.bin/sass {options} {from} {to}');
    return $converter;
}
```

or, if done via params.php:

```php
'yiisoft/asset' => [
    'assetConverter' => [
        'command' => [
            'from' => 'scss',
            'to' => 'css',
            'command' => '@npm/.bin/sass {options} {from} {to}'
        ],
        'forceConvert' => false
    ],
],
```


Asset bundle's `$converterOptions` define additional options passed to conversion utility. In this case we're telling `sass`
to minify resulting CSS.

Now, registering asset bundle as usual would result in asset conversion taking place:

```php
$assetManager->register([
    \App\Assets\BootstrapAsset::class
]);
```
