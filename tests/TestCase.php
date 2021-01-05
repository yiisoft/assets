<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Exception;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetConverter;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\Di\Container;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Files\FileHelper;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Aliases
     */
    protected $aliases;

    /**
     * @var AssetManager
     */
    protected $assetManager;

    /**
     * @var AssetPublisher
     */
    protected $publisher;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container($this->config());

        $this->aliases = $this->container->get(Aliases::class);
        $this->assetManager = $this->container->get(AssetManager::class);
        $this->logger = $this->container->get(LoggerInterface::class);
        $this->publisher = $this->container->get(AssetPublisherInterface::class);

        $this->removeAssets('@asset');
    }

    protected function tearDown(): void
    {
        $this->container = null;
        parent::tearDown();
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

    /**
     * Properly removes symlinked directory under Windows, MacOS and Linux.
     *
     * @param string $file path to symlink
     *
     * @return bool
     */
    protected function unlink(string $file): bool
    {
        return FileHelper::unlink($file);
    }

    private function config(): array
    {
        $params = $this->params();

        return [
            Aliases::class => [
                '__construct()' => [
                    [
                        '@root' => dirname(__DIR__, 1),
                        '@asset' => '@root/tests/public/assets',
                        '@assetUrl' => '/baseUrl',
                        '@converter' => '@root/tests/public/assetconverter',
                        '@npm' => '@root/node_modules',
                        '@testSourcePath' => '@root/tests/public/assetsources',
                    ],
                ],
            ],

            LoggerInterface::class => NullLogger::class,

            AssetConverterInterface::class => [
                '__class' => AssetConverter::class,
                'setCommand()' => [
                    $params['yiisoft/assets']['assetConverter']['command']['from'],
                    $params['yiisoft/assets']['assetConverter']['command']['to'],
                    $params['yiisoft/assets']['assetConverter']['command']['command'],
                ],
                'setForceConvert()' => [$params['yiisoft/assets']['assetConverter']['forceConvert']],
            ],

            AssetPublisherInterface::class => [
                '__class' => AssetPublisher::class,
                'setAppendTimestamp()' => [$params['yiisoft/assets']['assetPublisher']['appendTimestamp']],
                'setAssetMap()' => [$params['yiisoft/assets']['assetPublisher']['assetMap']],
                'setBasePath()' => [$params['yiisoft/assets']['assetPublisher']['basePath']],
                'setBaseUrl()' => [$params['yiisoft/assets']['assetPublisher']['baseUrl']],
                'setForceCopy()' => [$params['yiisoft/assets']['assetPublisher']['forceCopy']],
                'setLinkAssets()' => [$params['yiisoft/assets']['assetPublisher']['linkAssets']],
            ],

            AssetManager::class => [
                '__class' => AssetManager::class,
                'setConverter()' => [Reference::to(AssetConverterInterface::class)],
                'setPublisher()' => [Reference::to(AssetPublisherInterface::class)],
                'setBundles()' => [$params['yiisoft/assets']['assetManager']['bundles']],
                'register()' => [$params['yiisoft/assets']['assetManager']['register']],
            ],
        ];
    }

    private function params(): array
    {
        return require dirname(__DIR__) . '/config/params.php';
    }
}
