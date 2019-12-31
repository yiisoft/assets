# Asset manager

AssetManager resolves asset bundles registered in it and provides a list of files to include into HTML.
For general usage see "[asset bundles](asset-bundles.md)". Here we'll focus on configuring it.

Configuration could be done in two ways:

- Using DI container such as [yiisoft/di](https://github.com/yiisoft/di)
- Creating a class manually 

## Registering within yiisoft/di

```php
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Log\Logger;

return [
    LoggerInterface::class => Logger::class,
    AssetConverterInterface::class => AssetConverter::class, 
    
    AssetPublisher::class => function (ContainerInterface $container) {
        $publisher = $container->get(AssetPublisher::class);

        /**
         * Example settings options AssetPublisher:
         *
         * $publisher->setAppendTimestamp(true);
         * $publisher->setAssetMap([
         *     'jquery.js' => 'https://code.jquery.com/jquery-3.4.1.js',
         * ]);        
         * $publisher->setBasePath('@basePath');
         * $publisher->setBaseUrl('@web');
         * $publisher->setDirMode(0775);
         * $publisher->setFileMode(0755);
         * $publisher->setForceCopy(true);
         * $publisher->setHashCallback(function () {
         *     return 'HashCallback';
         * });
         * $publisher->setLinkAssets(true);
         */

        return $publisher;
    },

    AssetManager::class => function (ContainerInterface $container) {
        $assetManager = new AssetManager($container->get(LoggerInterface::class));

        /**
         *  Setting AsssetConverter::class in view/layout use $assetManager->getConverter()
         * 
         *  In view/layout command example:
         * 
         *  $assetManager->getConverter()->setCommand('php', ['txt', 'php {from} > {to}']);
         */ 
        $assetManager->setConverter($container->get(AssetConverterInterface::class));

        /**
         *  Setting AsssetPublisher::class in view/layout use $assetManager->getPublisher()
         * 
         *  In view/layout command example:
         * 
         *  $assetManager->getPublisher()->setForceCopy(true);
         */ 
        $assetManager->setPublisher($container->get(AssetPublisherInterface::class));

        /**
         * Example settings options AssetManager:
         * 
         * $assetManager->setBundles(
         *     [
         *         JqueryAsset::class => [
         *             'sourcePath' => null, //no publish asset bundle
         *             'js' => [
         *             [
         *                 'https://code.jquery.com/jquery-3.4.1.js',
         *                 'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
         *                 'crossorigin' => 'anonymous'
         *             ]
         *         ]
         *     ]
         * ]);
         */

        return $assetManager;
    },
];
```

### Creating a class manually

```php
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;

/**
 * Inyect dependencies in constructor:
 * 
 * \Yisoft\Aliases\Aliases $aliases, \Psr\Log\LoggerInterface $logger
 */
$converter = new AssetConverter($aliases, $logger);

/**
 * Inyect dependencies in constructor:
 * 
 * \Yisoft\Aliases\Aliases $aliases
 */
$publisher = new AssetPublisher($aliases);

/**
 * Inyect dependencies in constructor:
 * 
 * \Yisoft\Aliases\Aliases $aliases, \Psr\Log\LoggerInterface $logger
 */
$assetManager = new AssetManager($aliases, $logger);

$assetManager->setConverter($converter);
$assetManager->setPublisher($publisher);
```
