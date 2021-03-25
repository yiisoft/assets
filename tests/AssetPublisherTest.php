<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use ReflectionObject;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Assets\Tests\stubs\BaseAsset;
use Yiisoft\Assets\Tests\stubs\CdnAsset;
use Yiisoft\Assets\Tests\stubs\CdnWithBaseUrlAsset;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;
use Yiisoft\Assets\Tests\stubs\WithoutBaseAsset;
use Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

use function dirname;
use function crc32;
use function is_file;
use function is_link;
use function sprintf;
use function ucfirst;

final class AssetPublisherTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@asset');
    }

    public function testBaseAppendTimestamp(): void
    {
        $bundle = new BaseAsset();

        $timestampCss = FileHelper::lastModifiedTime($this->aliases->get($bundle->basePath) . '/' . $bundle->css[0]);
        $urlCss = "/baseUrl/css/basePath.css?v=$timestampCss";

        $timestampJs = FileHelper::lastModifiedTime($this->aliases->get($bundle->basePath) . '/' . $bundle->js[0]);
        $urlJs = "/baseUrl/js/basePath.js?v=$timestampJs";

        $this->assertEmpty($this->manager->getRegisteredBundles());

        $this->publisher->setAppendTimestamp(true);

        $this->manager->register([BaseAsset::class]);

        $this->assertStringContainsString(
            $urlCss,
            $this->manager->getCssFiles()[$urlCss]['url']
        );
        $this->assertEquals(
            [
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
            ],
            $this->manager->getCssFiles()[$urlCss]['attributes'],
        );

        $this->assertStringContainsString(
            $urlJs,
            $this->manager->getJsFiles()[$urlJs]['url'],
        );
        $this->assertEquals(
            [
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
                'position' => 3,
            ],
            $this->manager->getJsFiles()[$urlJs]['attributes'],
        );
    }

    public function testPublisherSetAssetMap(): void
    {
        $urlJs = '//testme.css';

        $this->publisher->setDirMode(0777);
        $this->publisher->setFileMode(0777);
        $this->publisher->setAssetMap(
            [
                'jquery.js' => $urlJs,
            ]
        );

        $this->assertEmpty($this->manager->getRegisteredBundles());

        $this->manager->register([JqueryAsset::class]);

        $this->assertStringContainsString(
            $urlJs,
            $this->manager->getJsFiles()[$urlJs]['url'],
        );
        $this->assertEquals(
            [
                'position' => 3,
            ],
            $this->manager->getJsFiles()[$urlJs]['attributes'],
        );
    }

    /**
     * @return array
     */
    public function registerFileDataProvider(): array
    {
        return [
            // Custom alias repeats in the asset URL
            [
                'css', '@assetUrl/assetSources/repeat/css/stub.css', false,
                '/repeat/assetSources/repeat/css/stub.css',
                '/repeat',
            ],
            [
                'js', '@assetUrl/assetSources/repeat/js/jquery.js', false,
                '/repeat/assetSources/repeat/js/jquery.js',
                '/repeat',
            ],
            // JS files registration
            [
                'js', '@assetUrl/assetSources/js/missing-file.js', true,
                '/baseUrl/assetSources/js/missing-file.js',
            ],
            [
                'js', '@assetUrl/assetSources/js/jquery.js', false,
                '/baseUrl/assetSources/js/jquery.js',
            ],
            [
                'js', 'http://example.com/assetSources/js/jquery.js', false,
                'http://example.com/assetSources/js/jquery.js',
            ],
            [
                'js', '//example.com/assetSources/js/jquery.js', false,
                '//example.com/assetSources/js/jquery.js',
            ],
            [
                'js', 'assetSources/js/jquery.js', false,
                'assetSources/js/jquery.js',
            ],
            [
                'js', '/assetSources/js/jquery.js', false,
                '/assetSources/js/jquery.js',
            ],
            // CSS file registration
            [
                'css', '@assetUrl/assetSources/css/missing-file.css', true,
                '/baseUrl/assetSources/css/missing-file.css',
            ],
            [
                'css', '@assetUrl/assetSources/css/stub.css', false,
                '/baseUrl/assetSources/css/stub.css',
            ],
            [
                'css', 'http://example.com/assetSources/css/stub.css', false,
                'http://example.com/assetSources/css/stub.css',
            ],
            [
                'css', '//example.com/assetSources/css/stub.css', false,
                '//example.com/assetSources/css/stub.css',
            ],
            [
                'css', 'assetSources/css/stub.css', false,
                'assetSources/css/stub.css',
            ],
            [
                'css', '/assetSources/css/stub.css', false,
                '/assetSources/css/stub.css',
            ],
            // Custom `@assetUrl` aliases
            [
                'js', '@assetUrl/assetSources/js/missing-file1.js', true,
                '/backend/assetSources/js/missing-file1.js',
                '/backend',
            ],
            [
                'js', 'http://full-url.example.com/backend/assetSources/js/missing-file.js', true,
                'http://full-url.example.com/backend/assetSources/js/missing-file.js',
                '/backend',
            ],
            [
                'css', '//backend/backend/assetSources/js/missing-file.js', true,
                '//backend/backend/assetSources/js/missing-file.js',
                '/backend',
            ],
            [
                'css', '@assetUrl/assetSources/css/stub.css', false,
                '/en/blog/backend/assetSources/css/stub.css',
                '/en/blog/backend',
            ],
            // UTF-8 chars
            [
                'css', '@assetUrl/assetSources/css/stub.css', false,
                '/рус/сайт/assetSources/css/stub.css',
                '/рус/сайт',
            ],
            [
                'js', '@assetUrl/assetSources/js/jquery.js', false,
                '/汉语/漢語/assetSources/js/jquery.js',
                '/汉语/漢語',
            ],
        ];
    }

    /**
     * @dataProvider registerFileDataProvider
     *
     * @param string $type either `js` or `css`
     * @param string $path
     * @param bool $appendTimestamp
     * @param string $expected
     * @param string|null $webAlias
     */
    public function testRegisterFileAppendTimestamp(
        string $type,
        string $path,
        bool $appendTimestamp,
        string $expected,
        ?string $webAlias = null
    ): void {
        $originalAlias = $this->aliases->get('@assetUrl');

        if ($webAlias === null) {
            $webAlias = $originalAlias;
        }

        $this->aliases->set('@assetUrl', $webAlias);
        $path = $this->aliases->get($path);
        $this->publisher->setAppendTimestamp($appendTimestamp);

        $reflection = new ReflectionObject($this->manager);
        $method = $reflection->getMethod('register' . ucfirst($type) . 'File');
        $method->setAccessible(true);
        $method->invokeArgs($this->manager, [$path, [], null]);
        $method->setAccessible(false);

        $this->assertStringContainsString(
            $expected,
            $type === 'css' ? $this->manager->getCssFiles()[$expected]['url']
                : $this->manager->getJsFiles()[$expected]['url'],
        );
    }

    public function testSourcesCssJsDefaultOptions(): void
    {
        $bundle = new SourceAsset();

        $sourcePath = $this->aliases->get($bundle->sourcePath);
        $path = (is_file($sourcePath) ? dirname($sourcePath) : $sourcePath) .
            FileHelper::lastModifiedTime($sourcePath);
        $hash = sprintf('%x', crc32($path . '|' . $this->publisher->getLinkAssets()));

        $this->publisher->setCssDefaultOptions([
            'media' => 'none',
        ]);

        $this->publisher->setJsDefaultOptions([
            'position' => 2,
        ]);

        $this->assertEmpty($this->manager->getRegisteredBundles());

        $this->manager->register([SourceAsset::class]);

        $this->assertEquals(
            [
                'media' => 'none',
            ],
            $this->manager->getCssFiles()["/baseUrl/$hash/css/stub.css"]['attributes'],
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
            $this->manager->getJsFiles()["/baseUrl/$hash/js/stub.js"]['attributes'],
        );
    }

    public function testSourceSetHashCallback(): void
    {
        $this->publisher->setHashCallback(function () {
            return 'HashCallback';
        });

        $this->assertEmpty($this->manager->getRegisteredBundles());

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
        $this->expectExceptionMessageMatches('/^basePath must be set in AssetPublisher->setBasePath\(\$path\)/');
        $publisher->publish(new WithoutBaseAsset());
    }

    public function testCdn(): void
    {
        $this->publisher->setBaseUrl('https://example.com/test');

        $this->assertSame(
            'https://example.com/main.css',
            $this->publisher->getAssetUrl(new CdnAsset(), 'https://example.com/main.css'),
        );

        $this->assertSame(
            'https://example.com/base/main.css',
            $this->publisher->getAssetUrl(new CdnWithBaseUrlAsset(), 'main.css'),
        );
    }
}
