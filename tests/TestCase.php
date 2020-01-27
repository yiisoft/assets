<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use hiqdev\composer\config\Builder;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Files\FileHelper;
use Yiisoft\Di\Container;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * @var Aliases $aliases
     */
    protected $aliases;

    /**
     * @var AssetManager $assetManager
     */
    protected $assetManager;

    /**
     * @var AssetPublisher $assetPublisher
     */
    protected $publisher;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = require Builder::path('tests');

        $this->container = new Container($config);

        $this->aliases = $this->container->get(Aliases::class);
        $this->assetManager = $this->container->get(AssetManager::class);
        $this->logger = $this->container->get(LoggerInterface::class);
        $this->publisher = $this->container->get(AssetPublisher::class);

        $this->removeAssets('@basePath');
    }

    /**
     * tearDown
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->container = null;
        parent::tearDown();
    }

    /**
     * Asserting two strings equality ignoring line endings.
     * @param string $expected
     * @param string $actual
     * @param string $message
     *
     * @return void
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
            throw new \Exception("Unable to open directory: $dir");
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..' || $file === '.gitignore') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$file;
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
     *
     * @return void
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
}
