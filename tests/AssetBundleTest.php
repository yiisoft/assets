<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Assets\Tests\stubs\BaseAsset;
use Yiisoft\Assets\Tests\stubs\BaseWrongAsset;
use Yiisoft\Assets\Tests\stubs\CircleAsset;
use Yiisoft\Assets\Tests\stubs\CircleDependsAsset;
use Yiisoft\Assets\Tests\stubs\FileOptionsAsset;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\SimpleAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;

/**
 * AssetBundleTest.
 */
final class AssetBundleTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@basePath');
    }

    public function testBaseAppendtimestamp(): void
    {
        $bundle = new BaseAsset();

        $timestampCss = @filemtime($this->aliases->get($bundle->basePath) . '/' . $bundle->css[0]);
        $urlCss = "/baseUrl/css/basePath.css?v=$timestampCss";

        $timestampJs = @filemtime($this->aliases->get($bundle->basePath) . '/' . $bundle->js[0]);
        $urlJs = "/baseUrl/js/basePath.js?v=$timestampJs";

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->setAppendTimestamp(true);
        $this->assetManager->register([BaseAsset::class]);

        $this->assertStringContainsString(
            $urlCss,
            $this->assetManager->getCssFiles()[$urlCss]['url']
        );
        $this->assertEquals(
            [
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous'
            ],
            $this->assetManager->getCssFiles()[$urlCss]['attributes']
        );

        $this->assertStringContainsString(
            $urlJs,
            $this->assetManager->getJsFiles()[$urlJs]['url']
        );
        $this->assertEquals(
            [
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
                'position' => 3
            ],
            $this->assetManager->getJsFiles()[$urlJs]['attributes']
        );
    }

    public function testBasePath(): void
    {
        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([BaseAsset::class]);

        $this->assertStringContainsString(
            '/baseUrl/css/basePath.css',
            $this->assetManager->getCssFiles()['/baseUrl/css/basePath.css']['url']
        );
        $this->assertEquals(
            [
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous'
            ],
            $this->assetManager->getCssFiles()['/baseUrl/css/basePath.css']['attributes']
        );

        $this->assertStringContainsString(
            '/baseUrl/js/basePath.js',
            $this->assetManager->getJsFiles()['/baseUrl/js/basePath.js']['url']
        );
        $this->assertEquals(
            [
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
                'position' => 3
            ],
            $this->assetManager->getJsFiles()['/baseUrl/js/basePath.js']['attributes']
        );
    }

    public function testBasePathEmptyException(): void
    {
        $this->assetManager->setBundles(
            [
                BaseAsset::class => [
                    'basePath' => null,
                ]
            ]
        );

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $message = 'basePath must be set in AssetManager->setBasePath($path) or ' .
            'AssetBundle property public ?string $basePath = $path';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->assetManager->register([BaseAsset::class]);
    }

    public function testBaseUrlEmptyException(): void
    {
        $this->assetManager->setBundles(
            [
                BaseAsset::class => [
                    'basePath' => null,
                    'baseUrl' => null,
                ]
            ]
        );

        $this->assetManager->setBasePath('@basePath');

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $message = 'baseUrl must be set in AssetManager->setBaseUrl($path) or ' .
            'AssetBundle property public ?string $baseUrl = $path';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->assetManager->register([BaseAsset::class]);
    }

    public function testBasePathEmptyWithAssetManagerSetBasePath(): void
    {
        $config = [
            'basePath' => null,
        ];

        $this->assetManager->setBasePath('@basePath');

        $this->assertEmpty($this->assetManager->getAssetBundles());
        $this->assertIsObject($this->assetManager->getBundle(BaseAsset::class));
    }

    public function testBasePathEmptyBaseUrlEmptyWithAssetManagerSetBasePathSetBaseUrl(): void
    {
        $config = [
            'basePath' => null,
            'baseUrl' => null,
        ];

        $this->assetManager->setBasePath('@basePath');
        $this->assetManager->setBaseUrl('@baseUrl');

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assertIsObject($this->assetManager->getBundle(BaseAsset::class));
    }

    public function testBasePathWrongException(): void
    {
        $bundle = new BaseWrongAsset();

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $file = $bundle->js[0];
        $message = "Asset files not found: '$bundle->basePath/$file.'";

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->assetManager->register([BaseWrongAsset::class]);
    }

    public function testCircularDependency(): void
    {
        $depends = (new CircleDependsAsset())->depends;

        $message = "A circular dependency is detected for bundle '$depends[0]'.";

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->assetManager->register([CircleAsset::class]);
    }

    public function testDuplicateAssetFile(): void
    {
        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([JqueryAsset::class, SimpleAsset::class]);

        $this->assertCount(3, $this->assetManager->getAssetBundles());
        $this->assertArrayHasKey(SimpleAsset::class, $this->assetManager->getAssetBundles());
        $this->assertInstanceOf(
            AssetBundle::class,
            $this->assetManager->getAssetBundles()[SimpleAsset::class]
        );

        $this->assertStringContainsString(
            '/js/jquery.js',
            $this->assetManager->getJsFiles()['/js/jquery.js']['url']
        );
        $this->assertEquals(
            [
                'position' => 3
            ],
            $this->assetManager->getJsFiles()['/js/jquery.js']['attributes']
        );
    }

    public function testFileOptionsAsset(): void
    {
        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([FileOptionsAsset::class]);

        $this->assertStringContainsString(
            '/baseUrl/css/default_options.css',
            $this->assetManager->getCssFiles()['/baseUrl/css/default_options.css']['url']
        );
        $this->assertEquals(
            [
                'media' => 'screen',
                'hreflang' => 'en'
            ],
            $this->assetManager->getCssFiles()['/baseUrl/css/default_options.css']['attributes']
        );

        $this->assertStringContainsString(
            '/baseUrl/css/tv.css',
            $this->assetManager->getCssFiles()['/baseUrl/css/tv.css']['url']
        );
        $this->assertEquals(
            [
                'media' => 'tv',
                'hreflang' => 'en'
            ],
            $this->assetManager->getCssFiles()['/baseUrl/css/tv.css']['attributes']
        );

        $this->assertStringContainsString(
            '/baseUrl/css/screen_and_print.css',
            $this->assetManager->getCssFiles()['/baseUrl/css/screen_and_print.css']['url']
        );
        $this->assertEquals(
            [
                'media' => 'screen, print',
                'hreflang' => 'en'
            ],
            $this->assetManager->getCssFiles()['/baseUrl/css/screen_and_print.css']['attributes']
        );

        $this->assertStringContainsString(
            '/baseUrl/js/normal.js',
            $this->assetManager->getJsFiles()['/baseUrl/js/normal.js']['url']
        );
        $this->assertEquals(
            [
                'charset' => 'utf-8',
                'position' => 3
            ],
            $this->assetManager->getJsFiles()['/baseUrl/js/normal.js']['attributes']
        );

        $this->assertStringContainsString(
            '/baseUrl/js/defered.js',
            $this->assetManager->getJsFiles()['/baseUrl/js/defered.js']['url']
        );
        $this->assertEquals(
            [
                'charset' => 'utf-8',
                'defer' => true,
                'position' => 3
            ],
            $this->assetManager->getJsFiles()['/baseUrl/js/defered.js']['attributes']
        );
    }


    public function testSourceSetHashCallback(): void
    {
        $this->assetManager->setHashCallback(function () {
            return 'HashCallback';
        });

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([SourceAsset::class]);

        $this->assertStringContainsString(
            '/baseUrl/HashCallback/css/stub.css',
            $this->assetManager->getCssFiles()['/baseUrl/HashCallback/css/stub.css']['url']
        );
        $this->assertEquals(
            [],
            $this->assetManager->getCssFiles()['/baseUrl/HashCallback/css/stub.css']['attributes']
        );

        $this->assertStringContainsString(
            '/js/jquery.js',
            $this->assetManager->getJsFiles()['/js/jquery.js']['url']
        );
        $this->assertEquals(
            [
                'position' => 3
            ],
            $this->assetManager->getJsFiles()['/js/jquery.js']['attributes']
        );

        $this->assertStringContainsString(
            '/baseUrl/HashCallback/js/stub.js',
            $this->assetManager->getJsFiles()['/baseUrl/HashCallback/js/stub.js']['url']
        );
        $this->assertEquals(
            [
                'position' => 3
            ],
            $this->assetManager->getJsFiles()['/baseUrl/HashCallback/js/stub.js']['attributes']
        );
    }

    public function testSourcesPublishOptionsOnlyRegex(): void
    {
        $bundle = new SourceAsset();

        $bundle->publishOptions = [
            'only' => [
                'js/*'
            ],
        ];

        [$bundle->basePath, $bundle->baseUrl] = $this->assetManager->getPublish()->publish(
            $this->assetManager,
            $bundle
        );

        $notNeededFilesDir = dirname($bundle->basePath . DIRECTORY_SEPARATOR . $bundle->css[0]);

        $this->assertFileNotExists($notNeededFilesDir);

        foreach ($bundle->js as $filename) {
            $publishedFile = $bundle->basePath . DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($publishedFile);
        }

        $this->assertDirectoryExists(dirname($bundle->basePath . DIRECTORY_SEPARATOR . $bundle->js[0]));
        $this->assertDirectoryExists($bundle->basePath);
    }

    public function testSourcesPathException(): void
    {
        $bundle = new SourceAsset();

        $bundle->sourcePath = '/wrong';

        $message = "The sourcePath to be published does not exist: $bundle->sourcePath";

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->assetManager->getPublish()->publish($this->assetManager, $bundle);
    }
}
