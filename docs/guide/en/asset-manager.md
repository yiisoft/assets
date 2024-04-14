# Asset manager

AssetManager resolves asset bundles registered in it and provides a list of files to include into HTML.
For general usage see [asset bundles](asset-bundles.md). In this guide we'll focus on configuring it.

Configuration could be done in two ways:

- Using DI container such as [yiisoft/di](https://github.com/yiisoft/di)
- Creating a class manually

## Creating using a container

```php
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetLoader;
use Yiisoft\Assets\AssetLoaderInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\Log\Logger;

return [
    Aliases::class => static fn () => new Aliases([
        '@root' => dirname(__DIR__),
        '@public' => '@root/public',
        '@assets' => '@public/assets',
        '@assetsUrl' => '/assets',
        '@npm' => '@root/node_modules',
    ]),

    LoggerInterface::class => Logger::class,
    AssetConverterInterface::class => AssetConverter::class,
    
    AssetLoaderInterface::class => static function (ContainerInterface $container) {
        $loader = new AssetLoader($container->get(Aliases::class));
        
        /**
         * Example settings options AssetLoader:
         *
         * $loader = $loader->withAppendTimestamp(true);
         * $loader = $loader->withAssetMap(['jquery.js' => 'https://code.jquery.com/jquery-3.4.1.js']);        
         * $loader = $loader->withBasePath('@assets');
         * $loader = $loader->withBaseUrl('@assetsUrl');
         * $loader = $loader->withCssDefaultOptions(['media' => 'screen', 'hreflang' => 'en');
         * $loader = $loader->withJsDefaultOptions(['async' => true, 'defer' => true);
         */
         
         return $loader;
    },
    
    AssetPublisherInterface::class => static function (ContainerInterface $container) {
        $publisher = new AssetPublisher($container->get(Aliases::class));

        /**
         * Example settings options AssetPublisher:
         *
         * $publisher = $publisher->withDirMode(0775);
         * $publisher = $publisher->withFileMode(0755);
         * $publisher = $publisher->withForceCopy(true);
         * $publisher = $publisher->withHashCallback(static fn () => 'hash');
         * $publisher = $publisher->withLinkAssets(true);
         */

        return $publisher;
    },

    AssetManager::class => static function (ContainerInterface $container) {
        $assetManager = new AssetManager(
            $container->get(Aliases::class),
            $container->get(AssetLoaderInterface::class),
        );
 
        $assetManager = $assetManager->withConverter($container->get(AssetConverterInterface::class));
        $assetManager = $assetManager->withPublisher($container->get(AssetPublisherInterface::class));

        return $assetManager;
    },
];
```

## Creating a class manually

```php
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetLoader;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;

/** 
 * @var \Yiisoft\Aliases\Aliases $aliases
 * @var \Psr\Log\LoggerInterface $logger
 */

$converter = new AssetConverter($aliases, $logger);
$loader = new AssetLoader($aliases);
$publisher = new AssetPublisher($aliases);


$assetManager = (new AssetManager($aliases, $loader))
    ->withConverter($converter)
    ->withPublisher($publisher)
;
```

## Specifying additional settings

The asset manager accepts two optional parameters `$allowedBundleNames` and `$customizedBundles` in the constructor:

```php
/** 
 * @var string[] $allowedBundleNames
 * @var array $customizedBundles
 * @var \Yiisoft\Aliases\Aliases $aliases
 * @var \Psr\Log\LoggerInterface $logger
 */

$assetManager = new \Yiisoft\Assets\AssetManager(
    $aliases,
    $logger,
    $allowedBundleNames, // Default to empty array.
    $customizedBundles // Default to empty array.
);
```

### Allowed asset bundles

`$allowedBundleNames` - List of names of allowed asset bundles. If the names of allowed asset bundles were specified,
only these asset bundles, or their dependencies can be registered and received. If the array is empty,
then any asset bundles are allowed.

```php
$allowedBundleNames = [
    \App\Assets\BootstrapAsset::class,
    \App\Assets\MainAsset::class,
    \App\Assets\JqueryAsset::class,
];
```

The specified asset bundles and all their dependencies will be allowed, so you can specify the top-level bundles
and not list all the dependencies. For example, if the `MainAsset` depends on the `BootstrapAsset`,
and the `BootstrapAsset` depends on the `JqueryAsset`, then you can specify only the `MainAsset`.

```php
$allowedBundleNames = [
    \App\Assets\MainAsset::class,
];
```

Using allowed asset bundles allows you to publish and export bundles of assets without manually registering them.
It is also convenient if you publish assets using a console command, for example,
for a one-time publication when deploying an application.

### Customization of asset bundles

`$customizedBundles` - The configurations to customize asset bundles. When loading an asset bundles,
if it has a corresponding configuration specified here, the configuration will be applied. The array
keys are the names of asset class bundles, and the values are arrays with modified property values.

```php
$customizedBundles = [
    \App\Assets\JqueryAsset::class => [
        'sourcePath' => null, // No publish asset bundle.
        'js' => [
            [
                'https://code.jquery.com/jquery-3.4.1.js',
                'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
                'crossorigin' => 'anonymous',
            ],
        ],
    ],
];
```

The values of the `sourcePath` and `js` properties will be redefined for the `App\Assets\JqueryAsset` bundle,
and the values of the other properties will remain unchanged.

> If a value is `false`, it means the corresponding asset bundle is disabled and
> all the values of its properties will be empty.

For use in the [Yii framework](https://www.yiiframework.com/),
see the configuration files: [`config/params.php`](../../../config/params.php) and [`config/web.php`](../../../config/di.php).

## Publishing asset bundles

There are two modes available for using the asset manager. With and without a publisher.

Using the publisher, the manager will automatically publish assets and monitor their changes. This is convenient
when your application and assets are located on the same server and PHP is responsible for all manipulations.
This mode is used by default in the [yiisoft/app](https://github.com/yiisoft/app) application template.

```php
/** 
 * @var \Yiisoft\Aliases\Aliases $aliases
 * @var \Yiisoft\Assets\AssetLoaderInterface $loader
 * @var \Yiisoft\Assets\AssetPublisherInterface $publisher
 */

$assetManager = (new \Yiisoft\Assets\AssetManager($aliases, $loader))
    ->withPublisher($publisher)
;

$assetManager->register(\App\Assets\MainAsset::class);
// Or several in one pack:
$assetManager->registerMany([
    \App\Assets\BootstrapAsset::class,
    \App\Assets\MainAsset::class,
]);
```

If you opt out of using the publisher, you should take care of publishing the asset bundles yourself.
This is useful when there are multiple applications and resources are located on a separate server.

This mode is also suitable in the following cases:

- For using asset files with CDN only.
- For using an external module builder, such as [webpack](https://github.com/webpack/webpack).
- For a single publication, such as when deploying an application.

Another way is to pre-publish assets. In order to do it, you need to create a console command and execute it at the time
of application deployment:

```php
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;

class PublishCommand extends Command
{
    protected static $defaultName = 'assets/publish';

    private AssetManager $assetManager;

    public function __construct(AssetManager $assetManager, Aliases $aliases)
    {
        $this->assetManager = $assetManager->withPublisher(new AssetPublisher($aliases));
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {        
        $this->assetManager->registerMany([/* asset bundle names */]);
        // To register all bundles if the allowed asset bundle names are used.
        //$this->assetManager->registerAllAllowed();
        
        $output->writeln('<info>Done</info>');
        return 0;
    }
}
```

## Pre-publishing when using a load balancer

Pre-publishing asset bundles is suitable for both local files and multiple servers behind a load balancer.
In the latter case you need to use a static hash for the name of the directory that is created when publishing.
Just like in the previous example, you need to create a console command and execute it at the time of
application deployment:

```php
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;

class PublishCommand extends Command
{
    protected static $defaultName = 'assets/publish';

    private AssetManager $assetManager;

    public function __construct(AssetManager $assetManager, Aliases $aliases)
    {
        $publisher = (new AssetPublisher($aliases))
            ->withHashCallback(static fn (string $path): string => hash('md4', $path))
        ;
        $this->assetManager = $assetManager->withPublisher($publisher);
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {   
        $this->assetManager->registerMany([/* asset bundle names */]);
        // To register all bundles if the allowed asset bundle names are used.
        //$this->assetManager->registerAllAllowed();
        
        $output->writeln('<info>Done</info>');
        return 0;
    }
}
```

## Exporting asset bundles

Export automates the collection of asset bundle file paths for external module builders.
Two exporters are provided out of the box:

- `Yiisoft\Assets\Exporter\JsonAssetExporter` - exports the file paths of asset bundles into a JSON file.
- `Yiisoft\Assets\Exporter\WebpackAssetExporter` - exports the file paths of asset bundles, converting them to
  `import '/path/to/file';` expressions and placing them in the specified JavaScript file for later loading into
  Webpack. For more information, [see here](https://webpack.js.org/concepts/#entry).

Export is especially useful when using the allowed names of asset bundles:

```php
use Yiisoft\Assets\Exporter\JsonAssetExporter;
use Yiisoft\Assets\Exporter\WebpackAssetExporter;
use Yiisoft\Assets\AssetManager;

/** 
 * @var \Yiisoft\Aliases\Aliases $aliases
 * @var \Yiisoft\Assets\AssetLoaderInterface $loader
 */

$assetManager = new AssetManager($aliases, $loader, [
    \App\Assets\BootstrapAsset::class,
    \App\Assets\MainAsset::class,
]);

$assetManager->export(new JsonAssetExporter('/path/to/file.json'));
$assetManager->export(new WebpackAssetExporter('/path/to/file.js'));
```

If the allowed asset bundle names are not used, they must be registered before exporting:

```php
use Yiisoft\Assets\Exporter\JsonAssetExporter;
use Yiisoft\Assets\Exporter\WebpackAssetExporter;
use Yiisoft\Assets\AssetManager;

/** 
 * @var \Yiisoft\Aliases\Aliases $aliases
 * @var \Yiisoft\Assets\AssetLoaderInterface $loader
 */

$assetManager = new AssetManager($aliases, $loader);

$assetManager->registerMany([
    \App\Assets\BootstrapAsset::class,
    \App\Assets\MainAsset::class,
]);

$assetManager->export(new JsonAssetExporter('/path/to/file.json'));
$assetManager->export(new WebpackAssetExporter('/path/to/file.js'));
```

You can create your own custom exporters for various integrations,
you just need to implement the `Yiisoft\Assets\AssetExporterInterface`.
