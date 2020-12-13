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
use Yiisoft\Assets\Tests\stubs\RootAsset;
use Yiisoft\Assets\Tests\stubs\SimpleAsset;

/**
 * AssetBundleTest.
 */
final class AssetBundleTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@asset');
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
                'crossorigin' => 'anonymous',
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
                'position' => 3,
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
                ],
            ]
        );

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $message = 'basePath must be set in AssetPublisher->setBasePath($path) or ' .
            'AssetBundle property public ?string $basePath = $path';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->assetManager->register([BaseAsset::class]);
    }

    public function testBaseUrlEmptyString(): void
    {
        $this->assetManager->setBundles(
            [
                RootAsset::class => [
                    'baseUrl' => '',
                ],
            ]
        );

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([RootAsset::class]);
    }

    public function testBaseUrlEmptyStringChain(): void
    {
        $this->assetManager->setBundles(
            [
                RootAsset::class => [
                    'depends' => [BaseAsset::class],
                ],
                BaseAsset::class => [
                    'baseUrl' => null,
                ],
            ]
        );

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([RootAsset::class]);
    }

    public function testBaseUrlIsNotSetException(): void
    {
        $this->assetManager->setBundles(
            [
                BaseAsset::class => [
                    'basePath' => null,
                    'baseUrl' => null,
                ],
            ]
        );

        $this->assetManager->getPublisher()->setBasePath('@asset');

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $message = 'baseUrl must be set in AssetPublisher->setBaseUrl($path) or ' .
            'AssetBundle property public ?string $baseUrl = $path';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->assetManager->register([BaseAsset::class]);
    }

    public function testBasePathEmptyWithAssetManagerSetBasePath(): void
    {
        $this->assetManager->getPublisher()->setBasePath('@asset');

        $this->assertEmpty($this->assetManager->getAssetBundles());
        $this->assertIsObject($this->assetManager->getBundle(BaseAsset::class));
    }

    public function testBasePathEmptyBaseUrlEmptyWithAssetManagerSetBasePathSetBaseUrl(): void
    {
        $this->assetManager->getPublisher()->setBasePath('@asset');
        $this->assetManager->getPublisher()->setBaseUrl('@assetUrl');

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
                'position' => 3,
            ],
            $this->assetManager->getJsFiles()['/js/jquery.js']['attributes']
        );
    }

    public function testJsString(): void
    {
        $this->assetManager->register([BaseAsset::class]);

        $this->assertEquals(
            'app1.start();',
            $this->assetManager->getJsStrings()['uniqueName']['string']
        );
        $this->assertEquals(
            'app2.start();',
            $this->assetManager->getJsStrings()['app2.start();']['string']
        );
        $this->assertEquals(
            1,
            $this->assetManager->getJsStrings()['uniqueName2']['attributes']['position']
        );
    }

    public function testJsVars(): void
    {
        $this->assetManager->register([BaseAsset::class]);

        $this->assertEquals(
            [
                'option1' => 'value1',
            ],
            $this->assetManager->getJsVars()['var1']['variables']
        );
        $this->assertEquals(
            [
                'option2' => 'value2',
                'option3' => 'value3',
            ],
            $this->assetManager->getJsVars()['var2']['variables']
        );
        $this->assertEquals(
            3,
            $this->assetManager->getJsVars()['var3']['attributes']['position']
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
                'hreflang' => 'en',
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
                'hreflang' => 'en',
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
                'hreflang' => 'en',
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
                'position' => 3,
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
                'position' => 3,
            ],
            $this->assetManager->getJsFiles()['/baseUrl/js/defered.js']['attributes']
        );
    }
}
