# Example AssetBundle with sourcePath:

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

- [AssetManager config \Yiisoft\View\WebView::setDefaultParameters()](../config/webview-setdefaultparameters.md)


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
[
    '/assets/88c9e467/css/bootstrap.css' => [
        'url' => '/assets/88c9e467/css/bootstrap.css'
        'attributes' => []
    ]
]
```

getJsFiles():
```php
[
    '/assets/37fee1f3/dist/jquery.js' => [
        'url' => '/assets/37fee1f3/dist/jquery.js'
        'attributes' => [
            'position' => 3
        ]
    ]
    '/assets/425f4ec0/umd/popper.js' => [
        'url' => '/assets/425f4ec0/umd/popper.js'
        'attributes' => [
            'position' => 3
        ]
    ]
    '/assets/88c9e467/js/bootstrap.js' => [
        'url' => '/assets/88c9e467/js/bootstrap.js'
        'attributes' => [
            'position' => 3
        ]
    ]
]
```

