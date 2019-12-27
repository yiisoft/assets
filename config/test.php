<?php

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;

use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Log\Logger;

$tempDir = sys_get_temp_dir();

return [
    ContainerInterface::class => function (ContainerInterface $container) {
        return $container;
    },

    Aliases::class => [
        '@root' => dirname(__DIR__, 1),
        '@public' => '@root/tests/public',
        '@basePath' => '@public/assets',
        '@baseUrl'  => '/baseUrl',
        '@converter' => '@public/assetconverter',
        '@npm' => '@root/node_modules',
        '@view' => '@public/view',
        '@web' => '@baseUrl',
        '@testSourcePath' => '@public/assetsources'
    ],

    AssetConverter::class => [
        '__class' => AssetConverter::class,
        '__construct()' => [
            Reference::to(Aliases::class),
            Reference::to(LoggerInterface::class)
        ]
    ],

    AssetConverterInterface::class => AssetConverter::class,

    AssetManager::class => function (ContainerInterface $container) {
        $assetConverterInterface = $container->get(AssetConverterInterface::class);
        $aliases = $container->get(Aliases::class);
        $logger = $container->get(LoggerInterface::class);
        $publisher = $container->get(AssetPublisher::class);
        $assetManager = new AssetManager($aliases, $logger);
        $assetManager->setConverter($assetConverterInterface);
        $assetManager->setPublisher($publisher);

        return $assetManager;
    },

    LoggerInterface::class => [
        '__class' => Logger::class,
        '__construct()' => [
            'targets' => [],
        ],
    ],
];
