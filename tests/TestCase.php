<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Exception;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetLoader;
use Yiisoft\Assets\AssetLoaderInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\Files\FileHelper;
use Yiisoft\Test\Support\Container\SimpleContainer;

use function closedir;
use function dirname;
use function is_dir;
use function opendir;
use function readdir;
use function str_replace;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected Aliases $aliases;
    protected AssetManager $manager;
    protected AssetLoader $loader;
    protected AssetConverter $converter;
    protected AssetPublisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->createContainer();

        $this->aliases = $container->get(Aliases::class);
        $this->manager = $container->get(AssetManager::class);
        $this->loader = $container->get(AssetLoaderInterface::class);
        $this->converter = $container->get(AssetConverterInterface::class);
        $this->publisher = $container->get(AssetPublisherInterface::class);

        $this->removeAssets('@asset');
    }

    /**
     * Returns the registered asset bundles.
     *
     * @param AssetManager $manager
     *
     * @return array The registered asset bundles {@see AssetManager::$registeredBundles}.
     */
    protected function getRegisteredBundles(AssetManager $manager): array
    {
        $reflection = new ReflectionClass($manager);
        $property = $reflection->getProperty('registeredBundles');
        $property->setAccessible(true);
        $registeredBundles = $property->getValue($manager);
        $property->setAccessible(false);
        return $registeredBundles;
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
     * Verify sources publish files asset bundle.
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

    /**
     * Invokes a inaccessible method.
     *
     * @param object $object
     * @param string $method
     * @param array $args
     *
     * @throws ReflectionException
     *
     * @return mixed
     */
    protected function invokeMethod(object $object, string $method, array $args = [])
    {
        $reflection = new ReflectionObject($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        $result = $method->invokeArgs($object, $args);
        $method->setAccessible(false);
        return $result;
    }

    private function createContainer(): SimpleContainer
    {
        $params = require dirname(__DIR__) . '/config/params.php';
        $aliases = new Aliases([
            '@root' => dirname(__DIR__),
            '@asset' => '@root/tests/public/assets',
            '@assetUrl' => '/baseUrl',
            '@converter' => '@root/tests/public/assetconverter',
            '@exporter' => '@root/tests/public/assetexporter',
            '@npm' => '@root/node_modules',
            '@sourcePath' => '@root/tests/public/sourcepath',
        ]);

        $converter = new AssetConverter($aliases, new NullLogger());
        $converter->setCommand(
            $params['yiisoft/assets']['assetConverter']['command']['from'],
            $params['yiisoft/assets']['assetConverter']['command']['to'],
            $params['yiisoft/assets']['assetConverter']['command']['command'],
        );
        $converter->setForceConvert($params['yiisoft/assets']['assetConverter']['forceConvert']);

        $loader = new AssetLoader($aliases);
        $loader->setAppendTimestamp($params['yiisoft/assets']['assetLoader']['appendTimestamp']);
        $loader->setAssetMap($params['yiisoft/assets']['assetLoader']['assetMap']);
        $loader->setBasePath($params['yiisoft/assets']['assetLoader']['basePath']);
        $loader->setBaseUrl($params['yiisoft/assets']['assetLoader']['baseUrl']);

        $publisher = new AssetPublisher($aliases);
        $publisher->setForceCopy($params['yiisoft/assets']['assetPublisher']['forceCopy']);
        $publisher->setLinkAssets($params['yiisoft/assets']['assetPublisher']['linkAssets']);

        $manager = new AssetManager(
            $aliases,
            $loader,
            $params['yiisoft/assets']['assetManager']['allowedBundleNames'],
            $params['yiisoft/assets']['assetManager']['customizedBundles'],
        );
        $manager->setConverter($converter);
        $manager->setPublisher($publisher);

        return new SimpleContainer([
            Aliases::class => $aliases,
            AssetManager::class => $manager,
            AssetLoaderInterface::class => $loader,
            AssetConverterInterface::class => $converter,
            AssetPublisherInterface::class => $publisher,
        ]);
    }
}
