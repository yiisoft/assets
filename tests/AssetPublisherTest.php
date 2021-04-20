<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Assets\Tests\stubs\SourceAsset;
use Yiisoft\Assets\Tests\stubs\WithoutBaseAsset;
use Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

use function dirname;
use function crc32;
use function is_file;
use function is_link;
use function sprintf;

final class AssetPublisherTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@asset');
    }

    public function testDefaultHashAndSourcesCssJsDefaultOptions(): void
    {
        $bundle = new SourceAsset();

        $sourcePath = $this->aliases->get($bundle->sourcePath);
        $hash = $this->getPublishedHash(
            (is_file($sourcePath) ? dirname($sourcePath) : $sourcePath) . FileHelper::lastModifiedTime($sourcePath),
            $this->publisher,
        );

        $manager = $this->manager->withLoader(
            $this->loader->withCssDefaultOptions(['media' => 'none'])->withJsDefaultOptions(['position' => 2]),
        );

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $manager->register([SourceAsset::class]);

        $this->assertEquals(
            [
                'media' => 'none',
            ],
            $manager->getCssFiles()["/baseUrl/{$hash}/css/stub.css"]['attributes'],
        );
        $this->assertEquals(
            [
                'position' => 2,
            ],
            $manager->getJsFiles()['/js/jquery.js']['attributes'],
        );
        $this->assertEquals(
            [
                'position' => 2,
            ],
            $manager->getJsFiles()["/baseUrl/{$hash}/js/stub.js"]['attributes'],
        );
    }

    public function testSourceWithHashCallback(): void
    {
        $manager = $this->manager->withPublisher($this->publisher->withHashCallback(static fn (): string => 'hash'));

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $manager->register([SourceAsset::class]);

        $this->assertStringContainsString(
            '/baseUrl/hash/css/stub.css',
            $manager->getCssFiles()['/baseUrl/hash/css/stub.css']['url'],
        );
        $this->assertEquals(
            [],
            $manager->getCssFiles()['/baseUrl/hash/css/stub.css']['attributes'],
        );

        $this->assertStringContainsString(
            '/js/jquery.js',
            $manager->getJsFiles()['/js/jquery.js']['url'],
        );
        $this->assertEquals(
            [
                'position' => 3,
            ],
            $manager->getJsFiles()['/js/jquery.js']['attributes'],
        );

        $this->assertStringContainsString(
            '/baseUrl/hash/js/stub.js',
            $manager->getJsFiles()['/baseUrl/hash/js/stub.js']['url'],
        );
        $this->assertEquals(
            [
                'position' => 3,
            ],
            $manager->getJsFiles()['/baseUrl/hash/js/stub.js']['attributes'],
        );
    }

    public function testSourcesPublishOptionsOnlyRegex(): void
    {
        $bundle = new SourceAsset();

        $bundle->publishOptions = [
            'filter' => (new PathMatcher())->only('**js/*'),
        ];

        [$bundle->basePath, $bundle->baseUrl] = $this->publisher->publish($bundle);

        $notNeededFilesDir = dirname($bundle->basePath . DIRECTORY_SEPARATOR . $bundle->css[0]);

        $this->assertFileDoesNotExist($notNeededFilesDir);

        foreach ($bundle->js as $filename) {
            $publishedFile = $bundle->basePath . DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($publishedFile);
        }

        $this->assertDirectoryExists(dirname($bundle->basePath . DIRECTORY_SEPARATOR . $bundle->js[0]));
        $this->assertDirectoryExists($bundle->basePath);
    }

    public function testSourcesPathEmptyException(): void
    {
        $bundle = new SourceAsset();

        $bundle->sourcePath = '';
        $message = 'The sourcePath must be defined in AssetBundle property public ?string $sourcePath = $path.';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->publisher->publish($bundle);
    }

    public function testBasePathEmptyException(): void
    {
        $bundle = new SourceAsset();

        $bundle->basePath = '';
        $message = 'The basePath must be defined in AssetBundle property public ?string $basePath = $path.';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->publisher->publish($bundle);
    }

    public function testBaseUrlNullException(): void
    {
        $bundle = new SourceAsset();

        $bundle->baseUrl = null;
        $message = 'The baseUrl must be defined in AssetBundle property public ?string $baseUrl = $path.';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->publisher->publish($bundle);
    }

    public function testSourcesPathException(): void
    {
        $bundle = new SourceAsset();

        $bundle->sourcePath = '/wrong';

        $message = "The sourcePath to be published does not exist: $bundle->sourcePath";

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->publisher->publish($bundle);
    }

    public function testSourcesPublishedBySymlinkIssue9333(): void
    {
        $bundle = new SourceAsset();
        $publisher = $this->publisher
            ->withLinkAssets(true)
            ->withDirMode(0775)
            ->withFileMode(0775)
            ->withHashCallback(static fn (string $path): string => sprintf('%x/%x', crc32($path), crc32('3.0-dev')))
        ;

        [$bundle->basePath, $bundle->baseUrl] = $publisher->publish($bundle);

        $this->assertDirectoryExists($bundle->basePath);

        foreach ($bundle->js as $filename) {
            $publishedFile = $bundle->basePath . DIRECTORY_SEPARATOR . $filename;
            $sourceFile = $bundle->basePath . DIRECTORY_SEPARATOR . $filename;

            $this->assertTrue(is_link($bundle->basePath));
            $this->assertFileExists($publishedFile);
            $this->assertFileEquals($publishedFile, $sourceFile);
        }

        FileHelper::unlink($bundle->basePath);
        $this->assertDirectoryDoesNotExist($bundle->basePath);

        $this->assertDirectoryExists(dirname($bundle->basePath));
    }

    public function testPublishWithAndWithoutBasePath(): void
    {
        $publisher = new AssetPublisher($this->aliases);
        $publisher->publish(new SourceAsset());

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'The basePath must be defined in AssetBundle property public ?string $basePath = $path.',
        );

        $publisher->publish(new WithoutBaseAsset());
    }

    public function testSettersImmutability(): void
    {
        $publisher = $this->publisher->withDirMode(755);
        $this->assertInstanceOf(AssetPublisherInterface::class, $publisher);
        $this->assertNotSame($this->publisher, $publisher);

        $publisher = $this->publisher->withFileMode(755);
        $this->assertInstanceOf(AssetPublisherInterface::class, $publisher);
        $this->assertNotSame($this->publisher, $publisher);

        $publisher = $this->publisher->withForceCopy(false);
        $this->assertInstanceOf(AssetPublisherInterface::class, $publisher);
        $this->assertNotSame($this->publisher, $publisher);

        $publisher = $this->publisher->withHashCallback(static fn (): string => 'hash');
        $this->assertInstanceOf(AssetPublisherInterface::class, $publisher);
        $this->assertNotSame($this->publisher, $publisher);

        $publisher = $this->publisher->withLinkAssets(false);
        $this->assertInstanceOf(AssetPublisherInterface::class, $publisher);
        $this->assertNotSame($this->publisher, $publisher);
    }
}
