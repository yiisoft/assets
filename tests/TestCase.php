<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Exception;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\Files\FileHelper;
use Yiisoft\Test\Support\Container\SimpleContainer;

abstract class TestCase extends BaseTestCase
{
    protected Aliases $aliases;
    protected AssetManager $assetManager;
    protected AssetPublisher $publisher;
    protected LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->createContainer();

        $this->aliases = $container->get(Aliases::class);
        $this->assetManager = $container->get(AssetManager::class);
        $this->logger = $container->get(LoggerInterface::class);
        $this->publisher = $container->get(AssetPublisherInterface::class);

        $this->removeAssets('@asset');
    }

    /**
     * Asserting two strings equality ignoring line endings.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    protected function assertEqualsWithoutLE(string $expected, string $actual, string $message = ''): void
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertEquals($expected, $actual, $message);
    }

    protected function removeAssets(string $basePath): void
    {
        $handle = opendir($dir = $this->aliases->get($basePath));

        if ($handle === false) {
            throw new Exception("Unable to open directory: $dir");
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..' || $file === '.gitignore') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                FileHelper::removeDirectory($path);
            } else {
                FileHelper::unlink($path);
            }
        }

        closedir($handle);
    }

    /**
     * Verify sources publish files assetbundle.
     *
     * @param string $type
     * @param AssetBundle $bundle
     */
    protected function sourcesPublishVerifyFiles(string $type, AssetBundle $bundle): void
    {
        foreach ($bundle->$type as $filename) {
            $publishedFile = $bundle->basePath . DIRECTORY_SEPARATOR . $filename;
            $sourceFile = $this->aliases->get($bundle->sourcePath) . DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($publishedFile);
            $this->assertFileEquals($publishedFile, $sourceFile);
        }

        $this->assertDirectoryExists($bundle->basePath . DIRECTORY_SEPARATOR . $type);
    }

    private function createContainer(): SimpleContainer
    {
        $params = require dirname(__DIR__) . '/config/params.php';
        $logger = new NullLogger();
        $aliases = new Aliases([
            '@root' => dirname(__DIR__),
            '@asset' => '@root/tests/public/assets',
            '@assetUrl' => '/baseUrl',
            '@converter' => '@root/tests/public/assetconverter',
            '@npm' => '@root/node_modules',
            '@testSourcePath' => '@root/tests/public/assetsources',
        ]);

        $converter = new AssetConverter($aliases, $logger);
        $converter->setCommand(
            $params['yiisoft/assets']['assetConverter']['command']['from'],
            $params['yiisoft/assets']['assetConverter']['command']['to'],
            $params['yiisoft/assets']['assetConverter']['command']['command'],
        );
        $converter->setForceConvert($params['yiisoft/assets']['assetConverter']['forceConvert']);

        $publisher = new AssetPublisher($aliases);
        $publisher->setAppendTimestamp($params['yiisoft/assets']['assetPublisher']['appendTimestamp']);
        $publisher->setAssetMap($params['yiisoft/assets']['assetPublisher']['assetMap']);
        $publisher->setBasePath($params['yiisoft/assets']['assetPublisher']['basePath']);
        $publisher->setBaseUrl($params['yiisoft/assets']['assetPublisher']['baseUrl']);
        $publisher->setForceCopy($params['yiisoft/assets']['assetPublisher']['forceCopy']);
        $publisher->setLinkAssets($params['yiisoft/assets']['assetPublisher']['linkAssets']);

        $manager = new AssetManager($aliases);
        $manager->setConverter($converter);
        $manager->setPublisher($publisher);
        $manager->setBundles($params['yiisoft/assets']['assetManager']['bundles']);
        $manager->register($params['yiisoft/assets']['assetManager']['register']);

        return new SimpleContainer([
            Aliases::class => $aliases,
            LoggerInterface::class => $logger,
            AssetConverterInterface::class => $converter,
            AssetPublisherInterface::class => $publisher,
            AssetManager::class => $manager,
        ]);
    }
}
