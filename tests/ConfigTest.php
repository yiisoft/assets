<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetLoader;
use Yiisoft\Assets\AssetLoaderInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

final class ConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testBase(): void
    {
        $container = $this->createContainer();

        $converter = $container->get(AssetConverterInterface::class);
        $loader = $container->get(AssetLoaderInterface::class);
        $publisher = $container->get(AssetPublisherInterface::class);
        $manager = $container->get(AssetManager::class);

        $this->assertInstanceOf(AssetConverter::class, $converter);
        $this->assertInstanceOf(AssetLoader::class, $loader);
        $this->assertInstanceOf(AssetPublisher::class, $publisher);
        $this->assertInstanceOf(AssetManager::class, $manager);
    }

    private function createContainer(?array $params = null): Container
    {
        return new Container(
            ContainerConfig::create()->withDefinitions(
                $this->getDiConfig($params)
                + [
                    LoggerInterface::class => new NullLogger(),
                ]
            )
        );
    }

    private function getDiConfig(?array $params = null): array
    {
        if ($params === null) {
            $params = $this->getParams();
        }
        return require dirname(__DIR__) . '/config/di-web.php';
    }

    private function getParams(): array
    {
        return require dirname(__DIR__) . '/config/params.php';
    }
}
