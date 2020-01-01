# Asset converter

AssetConverter supports conversion of several popular script formats into JavaScript or CSS.

# Defining asset converter for SASS

We'll use [foxy](https://github.com/fxpio/foxy). In calls npm so we'll need [NodeJS](https://nodejs.org/en/) installed. After it is done, create `package.json`:

```json
{
    "license": "BSD-3-Clause",
    "dependencies": {
        "sass": "^1.24.0",
    }
}
```

npm installs three packages into `node_modules` directory of our application.

# Using asset converter from asset bundle

For example convert scss to css: Bootstrap.php.

```php
declare(strict_types = 1);

namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap css files.
 *
 * BootstrapAsset.
 */
class BootstrapAsset extends AssetBundle
{
    public ?string $basePath = '@basePath';

    public ?string $baseUrl = '@web/assets';

    public ?string $sourcePath = '@npm/bootstrap';

    public array $css = [
        'scss/bootstrap.scss',
    ];

    public array $converterOptions = [
        'scss'   => '--style = compressed',
    ];

    public array $depends = [
        \App\Assets\JqueryAsset::class,
        \App\Assets\PopperAsset::class,
    ];

    public array $publishOptions = [
        'only' => [
            'scss/*',
            'scss/mixins/*',
            'scss/utilities/*',
            'scss/vendor/*'
        ],
    ];
}
```

# Using in view:

As we can see the options defined in `$converterOptions`, they are applied to the asset manager when it processes the asset bundle. As we can see the definition of the asset bundle, we are running sass with the option to minify the `$css`.

```php
$assetManager->getConverter()->setCommand(['css', '@npm/.bin/sass {options} {from} {to}']);

$assetManager->register([
    \App\Assets\BootstrapAsset::class
]);
```
