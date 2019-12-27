
<p align="center">
    <h1 align="center">Defined Asset Bundles</h1>
</p>

AssetBundle represents a collection of asset files, such as css, js, images.

Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application. The name is the [fully qualified class name](http://php.net/manual/en/language.namespaces.rules.php) of the class representing it.

An asset bundle can depend on other asset bundles. When registering an asset bundle with a view, all its dependent asset bundles will be automatically registered.

## Public Property:

| Name | Type | Description | Value Default |
|:----:|:----:|:-----------:|:-------------:|
|`$basePath`|`string/null`| The web public directory that contains the asset files in this bundle| `null`|
|`$baseUrl`|`string/null`| The base URL for the relative asset files listed in $js and $css.| `null`|
|`$css`|`array`| List of css files that this bundle contains.| `[]`|
|`$cssOptions`|`array`| The options that will be passed to \Yiisoft\View\WebView::setCssFiles().| `[]`|
|`$depends`|`array`| List of bundle class names that this bundle depends on.| `[]`|
|`$js`|`array`| List of javascript files that this bundle contains.| `[]`|
|`$jsOptions`|`array`| The options that will be passed to \Yiisoft\View\WebView::setJsFiles().| `[]`|
|`$publishOptions`|`array`| The options to be passed to \Yiisoft\Assets\AssetPublisher::publish() when the asset bundle is being published.|`[]`|
|`$sourcePath`|`string/null`| The directory that contains the source asset files for this assetBundle.| `null`|


## 1.- Example AssetBundle with sourcePath:

We will create an AssetBundle with sourcePath in this case for Bootstrap4.

Yii 3.0 works with foxy, in this case we must create a package.json to install the sources of our packages in the node_modules directory of our application.

package.json:

```
{
    "license": "BSD-3-Clause",
    "dependencies": {
        "bootstrap": "^4.1.3",
        "jquery": "^3.3.1",
        "popper.js": "^1.14.5"
    }
}
```

We will have 3 different packages, bootstrap4, and its two dependencies jquery and popper.

Now we create the AssetBundle BoostrapAsset.

Config alias in our application in config/params.php:

```php
return [
    'aliases' => [
        '@root' => dirname(__DIR__),
        '@public' => '@root/public',
        '@basePath' => '@public/assets',
        '@web' => '/assets',
        '@npm' => '@root/node_modules'
    ],
];
```

BootstrapAsset.php:
```php
declare(strict_types=1);

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
    public ?string $basePath = '@basePath';

    public ?string $baseUrl = '@web';

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

JqueryAsset.php:
```php
declare(strict_types=1);

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
    public ?string $basePath = '@basePath';

    public ?string $baseUrl = '@web';

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

PopperAsset.php:
```php
declare(strict_types=1);

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
    public ?string $basePath = '@basePath';

    public ?string $baseUrl = '@web';

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

Now we must configure the assetManager to have it available in all views, one of them is setting `setDefaultParameters()` in the view with di-container:

ViewFactory.php:
```php
declare(strict_types=1);

namespace App\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\Theme;
use Yiisoft\View\View;
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
        $webView->setDefaultParameters([
            'aliases' => $aliases,
            'assetManager' => $container->get(AssetManager::class),
            'urlGenerator' => $container->get(UrlGeneratorInterface::class),
        ]);


        return $webView;
    }
}
```

If we want bootstrap4 in all our views, we simply define it in our layout with the assetManager and pass it on view and automatically we will have them available globally.

Layout.php:
```php
declare(strict_types=1);

/* generate config array assetbundle */
$assetManager->register([
    \App\Assets\BootstrapAsset::class
]);

// pass array config assets to webview.
$this->setCssFiles($assetManager->getCssFiles());
$this->setJsFiles($assetManager->getJsFiles());
```

Now boostrap4 is in the layout and in all the views of our application.

**Note:** If I wanted only bootstrap4 in a single view it is very simple we configure it as follows:

Layout.php:
```php
declare(strict_types=1);

/* pass array config assets to webview. */
$this->setCssFiles($assetManager->getCssFiles());
$this->setJsFiles($assetManager->getJsFiles());
```

index.php:
```php
declare(strict_types=1);

/* generate config array assetbundle */
$assetManager->register([
    \App\Assets\BootstrapAsset::class
]);
```

Now bootstrap4 is only available for index.php view

The configuration array generated for this example are the following:

getCssFiles():
```php
array (size=2)
  '/assets/88c9e467/css/bootstrap.css' => 
    array (size=2)
      'url' => string '/assets/88c9e467/css/bootstrap.css' (length=34)
      'attributes' => 
        array (size=0)
          empty
```

getJsFiles():
```php
array (size=3)
  '/assets/37fee1f3/dist/jquery.js' => 
    array (size=2)
      'url' => string '/assets/37fee1f3/dist/jquery.js' (length=31)
      'attributes' => 
        array (size=1)
          'position' => int 3
  '/assets/425f4ec0/umd/popper.js' => 
    array (size=2)
      'url' => string '/assets/425f4ec0/umd/popper.js' (length=30)
      'attributes' => 
        array (size=1)
          'position' => int 3
  '/assets/88c9e467/js/bootstrap.js' => 
    array (size=2)
      'url' => string '/assets/88c9e467/js/bootstrap.js' (length=32)
      'attributes' => 
        array (size=1)
          'position' => int 3
```
