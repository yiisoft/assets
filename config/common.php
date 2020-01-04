<?php

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\Log\Logger;

return [
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
