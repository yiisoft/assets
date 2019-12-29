# Example AssetBundle with basePath and dependencies using cdn:

Another case that we can find when using the AssetManager, is that we have our own CSS and JavaScript, and we use CDN, to make references to their dependencies, without being installed in our application this could be a valid case for when we work in cloud environment and we want to avoid any kind of error.

1. The first thing is to make sure what our public folder is, in our case I have assigned it the alias `'@public'`, to refer to the access directory.
2. In this example within directory public we will create two more directories css and js.

    Directory structure:
    ```
    public/    - contains the entry script and files public for a web server
        css/   - contains CSS files
        js/    - contains JavaScript files
    ```
3. Now we copy our CSS and JavaScript files via ftp to their respective directories `'@public/css'` and `'@public/js'`.
4. Now we will proceed to create our AssetBundle, in this case we do not define the `$sourcePath` property since we have our asset copied in the public directory that is to say our `'@public/css'`, and our url public `'@web'` its `\`.

AppAsset.php

```php
declare(strict_types=1);

namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * AppAsset.
 *
 * Assets web application basic
 **/
class AppAsset extends AssetBundle
{
    public ?string $basePath = '@public';

    public ?string $baseUrl = '@web';

    public array $css = [
        'css/site.css', /* we will get the following url assets '/css/site.css' */
    ];

    public array $depends = [
        \App\Asset\BootstrapAsset::class
    ];
}
```

BootstrapAsset.php

```php
declare(strict_types=1);

namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap files using cdn.
 *
 * BootstrapAsset.
 */
class BootstrapAsset extends AssetBundle
{

    public bool $cdn = true;

    public array $css = [
        'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css',
    ];

    public array $cssOptions = [
        'integrity' => 'sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh',
        'crossorigin' => 'anonymous'
    ];

    public array $js = [
        'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js',
    ];

    public array $jsOptions = [
        'integrity' => 'sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6',
        'crossorigin' => 'anonymous'
    ];

    public array $depends = [
        \App\Assets\JqueryAsset::class,
        \App\Assets\PopperAsset::class
    ];
}
```

JqueryAsset.php:

```php
declare(strict_types=1);

namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap files using cdn.
 *
 * JqueryAsset.
 *
 * @package Bootstrap4
 */
class JqueryAsset extends AssetBundle
{
    public bool $cdn = true;

    public array $js = [
        'https://code.jquery.com/jquery-3.4.1.min.js',
    ];

    public array $jsOptions = [
        'integrity' => 'sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=',
        'crossorigin' => 'anonymous'
    ];
}
```

PopperAsset.php
```php
declare(strict_types=1);

namespace App\Assets;

use Yiisoft\Assets\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap files using cdn.
 *
 * PopperAsset.
 *
 * @package Bootstrap4
 */
class PopperAsset extends AssetBundle
{
    public bool $cdn = true;

    public array $js = [
        'https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js',
    ];

    public array $jsOptions = [
        'integrity' => 'sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo',
        'crossorigin' => 'anonymous'
    ];
}
```

In this way, the AssetManager will not copy any asset files CSS and JavaScript, but simply generates and manages the url, making it ideal for cloud environments.
