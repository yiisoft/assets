<?php

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;

return [
    AssetConverterInterface::class => AssetConverter::class,
    AssetPublisherInterface::class => AssetPublisher::class,
    AssetManager::class => static function (ContainerInterface $container) {
        $assetManager = new AssetManager($container->get(LoggerInterface::class));

        $assetManager->setConverter($container->get(AssetConverterInterface::class));
        $assetManager->setPublisher($container->get(AssetPublisherInterface::class));

        return $assetManager;
    },
];
