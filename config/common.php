<?php

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Log\Logger;

return [
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
        $assetManager = new AssetManager($aliases, $logger);
        $assetManager->setConverter($assetConverterInterface);

        return $assetManager;
    },

    LoggerInterface::class => [
        '__class' => Logger::class,
        '__construct()' => [
            'targets' => [],
        ],
    ],
];
