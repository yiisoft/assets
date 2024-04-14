# Asset bundles

Asset bundle represents a collection of asset files, such as CSS, JavaScript, images along with publishing options.

Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application.
The name is the [fully qualified class name](https://php.net/manual/en/language.namespaces.rules.php) of the class
representing it.

An asset bundle can depend on other asset bundles. When registering an asset bundle to an asset manager,
all its dependent asset bundles will be recursively registered.

There are three types of asset bundles:

- When the asset folder is not accessible from the `$baseUrl` we use the `$sourcePath` option
- When we have the public directory accessible folder we use the `$basePath` option
- When we only want to use CDN we set `$cdn` option of our AssetBundle to `true`.

Combinations of these types could be used as well.

## Defining an asset

In order to define your own asset, create a class that extends from `Asset` and define any of the options below
as public properties:

  Name              | Type       | Default | Description
------------------- |-------------|---------|------------
`$basePath`         |`string\|null`| `null`  | The web public directory that contains the asset files in this bundle.
`$baseUrl`          |`string\|null`| `null`  | The base URL for the relative asset files listed in `$js` and `$css`.
`$cdn`              |`bool`       | `false` | Indicates if we are going to use CDN exclusively.
`$css`              |`array`      | `[]`    | List of CSS files that this bundle contains.
`$cssOptions`       |`array`      | `[]`    | The options that will be passed to `\Yiisoft\View\WebView::setCssFiles()`.
`$cssStrings`       |`array`      | `[]`    | List of CSS blocks.
`$cssPosition`      |`int\|null`   | `null`  | Specifies where the `<style>` tag should be inserted in a page.
`$converterOptions` |`array`      | `[]`    | The command line options for converter.
`$depends`          |`array`      | `[]`    | List of bundle class names that this bundle depends on.
`$js`               |`array`      | `[]`    | List of JavaScript files that this bundle contains.
`$jsStrings`        |`array`      | `[]`    | List of JavaScript blocks.
`$jsOptions`        |`array`      | `[]`    | The options that will be passed to `\Yiisoft\View\WebView::setJsFiles()`.
`$jsPosition`       |`int\|null`   | `null`  | Specifies where the `<style>` tag should be inserted in a page.
`$jsVars`           |`array`      | `[]`    | JavaScript variables.
`$publishOptions`   |`array`      | `[]`    | The options to be passed to `\Yiisoft\Assets\AssetPublisher::publish()` when the asset bundle is being published.
`$export`           |`array`      | `[]`    | List of file paths to export into a format readable by third party tools such as [Webpack](https://webpack.js.org/). If the array is empty, the file paths from the `$css` and `$js` will be exported.
`$sourcePath`       |`string\|null`| `null`  | The directory that contains the source asset files for this asset bundle.

### JS/CSS positions for [`yiisoft/view`](https://github.com/yiisoft/view)

When this package is used with `yiisoft/view`, the possible values of `$jsPosition` are:

- `\Yiisoft\View\WebView::POSITION_HEAD` - in the head section. This is the default value
  for JavaScript variables.
- `\Yiisoft\View\WebView::POSITION_BEGIN` - at the beginning of the body section.
- `\Yiisoft\View\WebView::POSITION_END` - at the end of the body section. This is the default value
  for JavaScript files and blocks.
- `\Yiisoft\View\WebView::POSITION_READY` - at the end of the body section (only for JavaScript strings and
  variables). This means the JavaScript code block will be executed when HTML document composition is ready.
- `\Yiisoft\View\WebView::POSITION_LOAD` - at the end of the body section (only for JavaScript strings and
   variables). This means the JavaScript code block will be executed when HTML page is completely loaded.

The possible values of `$cssPosition` are:

- `\Yiisoft\View\WebView::POSITION_HEAD` - in the head section. This is the default value.
- `\Yiisoft\View\WebView::POSITION_BEGIN` - at the beginning of the body section.
- `\Yiisoft\View\WebView::POSITION_END` - at the end of the body section.

## Defining asset bundle for local assets

A common use case is to define bundles for local files. As an example, we will create a bundle with `sourcePath` pointing
to [Bootstrap 4](https://getbootstrap.com/).

We'll use [foxy](https://github.com/fxpio/foxy). It calls npm, so we'll need [NodeJS](https://nodejs.org/en/) installed.
After it is done, create `package.json`:

```json
{
    "license": "BSD-3-Clause",
    "dependencies": {
        "bootstrap": "^4.1.3",
        "jquery": "^3.3.1",
        "popper.js": "^1.14.5"
    }
}
```

The npm installs three packages into `node_modules` directory of our application. These are bootstrap4, and its two
dependencies: jQuery and popper.

Add alias to the application config at `config/params.php`:

```php
return [
    'yiisoft/aliases' => [
        'aliases' => [
            '@root' => dirname(__DIR__),
            '@public' => '@root/public',
            '@assets' => '@public/assets',
            '@assetsUrl' => '/assets',
            '@npm' => '@root/node_modules' // <-- this
        ],
    ],
];
```

Now create asset bundle classes. First, `BoostrapAsset.php`:

```php
namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap css/js files.
 *
 * BootstrapAsset.
 *
 * @package Bootstrap4
 */
class BootstrapAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/bootstrap/dist';

    public array $css = [
        'css/bootstrap.css',
    ];

    public array $js = [
        'js/bootstrap.js',
    ];

    public array $depends = [
        \App\Assets\JqueryAsset::class,
        \App\Assets\PopperAsset::class,
    ];

    public array $publishOptions = [
        'only' => [
            'css/bootstrap.css',
            'js/bootstrap.js',
        ]
    ];
}
```

`JqueryAsset.php`:

```php
namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap.
 *
 * JqueryAsset.
 *
 * @package Bootstrap4
 */
class JqueryAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/jquery';

    public array $js = [
        'dist/jquery.js',
    ];

    public array $publishOptions = [
        'only' => [
            'dist/jquery.js',
        ]
    ];
}
```

`PopperAsset.php`:

```php
namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap.
 *
 * PopperAsset.
 *
 * @package Bootstrap4
 */
class PopperAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/popper.js/dist';

    public array $js = [
        'umd/popper.js',
    ];

    public array $publishOptions = [
        'only' => [
            'umd/popper.js',
        ],
    ];
}
```

Note how dependencies are specified.

Now configure the asset manager to have it available in all views. That is typically done via `ViewFactory.php`:

```php
namespace App\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\Theme;
use Yiisoft\View\WebView;

class ViewFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $aliases = $container->get(Aliases::class);
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $logger = $container->get(LoggerInterface::class);
        $theme = $container->get(Theme::class);

        $webView = new WebView($aliases->get('@views'), $theme, $eventDispatcher, $logger);

        /**
         * Passes {@see UrlGeneratorInterface} and {@see AssetManager} to view files.
         * It will be available as $urlGenerator and $assetManager in all views or layout.
         */
        return $webView->withDefaultParameters([
            'aliases' => $aliases,
            'assetManager' => $container->get(AssetManager::class), // <-- here
            'urlGenerator' => $container->get(UrlGeneratorInterface::class),
        ]);
    }
}
```

If we want bootstrap 4 in all our views, we simply register it to asset manager in the layout:

```php
// register an asset
$assetManager->register(\App\Assets\BootstrapAsset::class);

// resolve files and pass them to view
$this->setCssFiles($assetManager->getCssFiles());
$this->setJsFiles($assetManager->getJsFiles());
```

> Note: If you need to register an asset in a single view, registering asset is done in that view instead while
resolving files stays in the layout.

### Override file paths for export

By default, all CSS and JavaScript file paths are exported from the asset bundle, but
you can specify the list of exported file paths explicitly in the `$export` property.

```php
namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

class AppAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@resources/assets';
    
    public array $css = [
        'css/style.css',
    ];

    public array $js = [
        'js/script.js',
    ];

    public array $export = [
        'img/image.png',
        'js/script.js',
    ];
}
```

In this example, the paths to the `img/image.png` and `js/script.js` files will be exported,
but the path to the `css/style.css` file will not be exported.
