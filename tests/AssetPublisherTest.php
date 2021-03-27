<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Yiisoft\Assets\AssetPublisher;
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
        $path = (is_file($sourcePath) ? dirname($sourcePath) : $sourcePath) .
            FileHelper::lastModifiedTime($sourcePath);
        $hash = sprintf('%x', crc32($path . '|' . $this->publisher->getLinkAssets()));

        $this->loader->setCssDefaultOptions([
            'media' => 'none',
        ]);

        $this->loader->setJsDefaultOptions([
            'position' => 2,
        ]);

        $this->assertEmpty($this->getRegisteredBundles($this->manager));

        $this->manager->register([SourceAsset::class]);

        $this->assertEquals(
            [
                'media' => 'none',
            ],
            $this->manager->getCssFiles()["/baseUrl/{$hash}/css/stub.css"]['attributes'],
        );
        $this->assertEquals(
            [
                'position' => 2,
            ],
            $this->manager->getJsFiles()['/js/jquery.js']['attributes'],
        );
        $this->assertEquals(
            [
                'position' => 2,
            ],
            $this->manager->getJsFiles()["/baseUrl/{$hash}/js/stub.js"]['attributes'],
        );
    }

    public function testSourceSetHashCallback(): void
    {
        $this->publisher->setHashCallback(function () {
            return 'HashCallback';
        });

        $this->assertEmpty($this->getRegisteredBundles($this->manager));

        $this->manager->register([SourceAsset::class]);

        $this->assertStringContainsString(
            '/baseUrl/HashCallback/css/stub.css',
            $this->manager->getCssFiles()['/baseUrl/HashCallback/css/stub.css']['url'],
        );
        $this->assertEquals(
            [],
            $this->manager->getCssFiles()['/baseUrl/HashCallback/css/stub.css']['attributes'],
        );

        $this->assertStringContainsString(
            '/js/jquery.js',
            $this->manager->getJsFiles()['/js/jquery.js']['url'],
        );
        $this->assertEquals(
            [
                'position' => 3,
            ],
            $this->manager->getJsFiles()['/js/jquery.js']['attributes'],
        );

        $this->assertStringContainsString(
            '/baseUrl/HashCallback/js/stub.js',
            $this->manager->getJsFiles()['/baseUrl/HashCallback/js/stub.js']['url'],
        );
        $this->assertEquals(
            [
                'position' => 3,
            ],
            $this->manager->getJsFiles()['/baseUrl/HashCallback/js/stub.js']['attributes'],
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
        $this->publisher->setLinkAssets(true);

        $this->publisher->setHashCallback(
            static function ($path) {
                return sprintf('%x/%x', crc32($path), crc32('3.0-dev'));
            }
        );

        $bundle = $this->verifySourcesPublishedBySymlink();

        $this->assertDirectoryExists(dirname($bundle->basePath));
    }

    private function verifySourcesPublishedBySymlink(): SourceAsset
    {
        $bundle = new SourceAsset();

        [$bundle->basePath, $bundle->baseUrl] = $this->publisher->publish($bundle);

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

        return $bundle;
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
}
