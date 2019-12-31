<?php

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\Log\Logger;

$tempDir = sys_get_temp_dir();

return [
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

    LoggerInterface::class => function (ContainerInterface $container) {
        $logger = $container->get(Logger::class);

        return $logger;
    },

    AssetConverterInterface::class => function (ContainerInterface $container) {
        $assetConverter = $container->get(AssetConverter::class);

        return $assetConverter;
    },

    AssetPublisherInterface::class => function (ContainerInterface $container) {
        $publisher = $container->get(AssetPublisher::class);

        return $publisher;
    },

    AssetManager::class => function (ContainerInterface $container) {
        $assetManager = new AssetManager($container->get(LoggerInterface::class));

        $assetManager->setConverter($container->get(AssetConverterInterface::class));
        $assetManager->setPublisher($container->get(AssetPublisherInterface::class));

        return $assetManager;
    },
];
