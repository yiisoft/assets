<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;

/* @var array $params */

return [
    Aliases::class => [
        '__class' => Aliases::class,
        '__construct()' => [$params['aliases']],
    ],

    LoggerInterface::class => NullLogger::class,

    AssetConverterInterface::class => static function (ContainerInterface $container) use ($params) {

        $assetConverter = new AssetConverter(
            $container->get(Aliases::class),
            $container->get(LoggerInterface::class)
        );

        if (
            $params['yiisoft/asset']['assetConverter']['command']['from'] !== '' &&
            $params['yiisoft/asset']['assetconverter']['command']['to'] !== '' &&
            $params['yiisoft/asset']['assetconverter']['command']['command'] !== ''
        ) {
            $assetConverter->setCommand(
                $params['yiisoft/asset']['assetConverter']['command']['from'],
                $params['yiisoft/asset']['assetConverter']['command']['to'],
                $params['yiisoft/asset']['assetConverter']['command']['command'],
            );
        }

        $assetConverter->setForceConvert($params['yiisoft/asset']['assetConverter']['forceConvert']);

        return $assetConverter;
    },

    AssetPublisherInterface::class => static function (ContainerInterface $container) use ($params) {
        $assetPublisher = new AssetPublisher($container->get(Aliases::class));

        $assetPublisher->setAppendTimestamp($params['yiisoft/asset']['assetPublisher']['appendTimestamp']);

        if ($params['yiisoft/asset']['assetPublisher']['assetMap'] != []) {
            $assetPublisher->setAssetMap($params['yiisoft/asset']['assetPublisher']['assetMap']);
        }

        if ($params['yiisoft/asset']['assetPublisher']['basePath'] !== '') {
            $assetPublisher->setBasePath($params['yiisoft/asset']['assetPublisher']['basePath']);
        }

        if ($params['yiisoft/asset']['assetPublisher']['baseUrl'] !== '') {
            $assetPublisher->setBaseUrl($params['yiisoft/asset']['assetPublisher']['baseUrl']);
        }

        $assetPublisher->setForceCopy($params['yiisoft/asset']['assetPublisher']['forceCopy']);
        $assetPublisher->setLinkAssets($params['yiisoft/asset']['assetPublisher']['linkAssets']);

        return $assetPublisher;
    },

    AssetManager::class => static function (ContainerInterface $container) use ($params) {
        $assetManager = new AssetManager($container->get(LoggerInterface::class));

        $assetManager->setConverter($container->get(AssetConverterInterface::class));
        $assetManager->setPublisher($container->get(AssetPublisherInterface::class));

        if ($params['yiisoft/asset']['assetManager']['bundles'] !== []) {
            $assetManager->setBundles($params['yiisoft/asset']['assetManager']['bundles']);
        }

        if ($params['yiisoft/asset']['assetManager']['register'] !== []) {
            $assetManager->register($params['yiisoft/asset']['assetManager']['register']);
        }

        return $assetManager;
    },
];
