### AssetManager with di-container:

To configure our container, we must define our interfaces and classes, which we can then call from the container using `$container->get()`.

```php
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\Log\Logger;

return [
    // defined injection dependencies AssetManager class.
    LoggerInterface::class => function (ContainerInterface $container) {
        $logger = $container->get(Logger::class);

        return $logger;
    },

    // defined AssetConverterInterface class.
    AssetConverterInterface::class => function (ContainerInterface $container) {
        $assetConverter = $container->get(AssetConverter::class);

        return $assetConverter;
    },

    // defined AssetPublisherInterface class.
    AssetPublisherInterface::class => function (ContainerInterface $container) {
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
