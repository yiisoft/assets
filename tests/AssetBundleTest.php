<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use RuntimeException;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\Exception\InvalidConfigException;

final class AssetBundleTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@asset');
    }

    public function testBasePath(): void
    {
        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register(['base']);

        $this->assertStringContainsString(
            '/baseUrl/css/basePath.css',
            $this->manager->getCssFiles()['/baseUrl/css/basePath.css']['url'],
        );
        $this->assertEquals(
            [
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
            ],
            $this->manager->getCssFiles()['/baseUrl/css/basePath.css']['attributes'],
        );

        $this->assertStringContainsString(
            '/baseUrl/js/basePath.js',
            $this->manager->getJsFiles()['/baseUrl/js/basePath.js']['url'],
        );
        $this->assertEquals(
            [
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
                'position' => 3,
            ],
            $this->manager->getJsFiles()['/baseUrl/js/basePath.js']['attributes'],
        );
    }

    public function testBasePathEmptyException(): void
    {
        $manager = new AssetManager($this->aliases, $this->publisher, [
            'test' => [
                'basePath' => null,
                'sourcePath' => '@sourcePath',
            ],
        ]);

        $this->assertEmpty($manager->getAssetBundles());

        $message = 'basePath must be set in AssetPublisher->setBasePath($path) or ' .
            'AssetBundle property public ?string $basePath = $path';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $manager->register(['test']);
    }

    public function testBaseUrlEmptyString(): void
    {
        $manager = new AssetManager($this->aliases, $this->publisher, [
            'test' => [
                'baseUrl' => '',
                'sourcePath' => '@sourcePath',
            ],
        ]);

        $manager->getPublisher()->setBasePath('@asset');

        $this->assertEmpty($this->manager->getAssetBundles());

        $manager->register(['test']);
    }

    public function testBaseUrlIsNotSetException(): void
    {
        $manager = new AssetManager($this->aliases, $this->publisher, [
            'test' => [
                'basePath' => '@asset',
                'baseUrl' => null,
                'sourcePath' => '@sourcePath',
            ],
        ]);

        $this->assertEmpty($manager->getAssetBundles());

        $message = 'baseUrl must be set in AssetPublisher->setBaseUrl($path) or ' .
            'AssetBundle property public ?string $baseUrl = $path';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $manager->register(['test']);
    }

    public function testBasePathEmptyWithAssetManagerSetBasePath(): void
    {
        $this->manager->getPublisher()->setBasePath('@asset');

        $this->assertEmpty($this->manager->getAssetBundles());
        $this->assertInstanceOf(AssetBundle::class, $this->manager->getBundle('base'));
    }

    public function testBasePathEmptyBaseUrlEmptyWithAssetManagerSetBasePathSetBaseUrl(): void
    {
        $this->manager->getPublisher()->setBasePath('@asset');
        $this->manager->getPublisher()->setBaseUrl('@assetUrl');

        $this->assertEmpty($this->manager->getAssetBundles());
        $this->assertInstanceOf(AssetBundle::class, $this->manager->getBundle('base'));
    }

    public function testBasePathWrongException(): void
    {
        $bundle = $this->createBundle('baseWrong');

        $this->assertEmpty($this->manager->getAssetBundles());

        $file = $bundle->js[0];
        $message = "Asset files not found: \"{$bundle->basePath}/{$file}\".";

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->manager->register([$bundle->name()]);
    }

    public function testCircularDependency(): void
    {
        $depends = $this->createBundle('circleDepends')->depends;

        $message = "A circular dependency is detected for bundle \"$depends[0]\".";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->manager->register(['circle']);
    }

    public function testDuplicateAssetFile(): void
    {
        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register(['jquery', 'simple']);

        $this->assertCount(3, $this->manager->getAssetBundles());
        $this->assertArrayHasKey('simple', $this->manager->getAssetBundles());
        $this->assertInstanceOf(AssetBundle::class, $this->manager->getAssetBundles()['simple']);

        $this->assertStringContainsString(
            '/js/jquery.js',
            $this->manager->getJsFiles()['/js/jquery.js']['url']
        );
        $this->assertEquals(
            [
                'position' => 3,
            ],
            $this->manager->getJsFiles()['/js/jquery.js']['attributes']
        );
    }

    public function testJsString(): void
    {
        $this->manager->register(['base']);

        $this->assertEquals(
            'app1.start();',
            $this->manager->getJsStrings()['uniqueName']['string'],
        );
        $this->assertEquals(
            'app2.start();',
            $this->manager->getJsStrings()['app2.start();']['string'],
        );
        $this->assertEquals(
            1,
            $this->manager->getJsStrings()['uniqueName2']['attributes']['position'],
        );
    }

    public function testJsVars(): void
    {
        $this->manager->register(['base']);

        $this->assertEquals(
            [
                'option1' => 'value1',
            ],
            $this->manager->getJsVar()['var1']['variables'],
        );
        $this->assertEquals(
            [
                'option2' => 'value2',
                'option3' => 'value3',
            ],
            $this->manager->getJsVar()['var2']['variables'],
        );
        $this->assertEquals(
            3,
            $this->manager->getJsVar()['var3']['attributes']['position'],
        );
    }

    public function testFileOptionsAsset(): void
    {
        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register(['fileOptions']);

        $this->assertStringContainsString(
            '/baseUrl/css/default_options.css',
            $this->manager->getCssFiles()['/baseUrl/css/default_options.css']['url'],
        );
        $this->assertEquals(
            [
                'media' => 'screen',
                'hreflang' => 'en',
            ],
            $this->manager->getCssFiles()['/baseUrl/css/default_options.css']['attributes'],
        );

        $this->assertStringContainsString(
            '/baseUrl/css/tv.css',
            $this->manager->getCssFiles()['/baseUrl/css/tv.css']['url'],
        );
        $this->assertEquals(
            [
                'media' => 'tv',
                'hreflang' => 'en',
            ],
            $this->manager->getCssFiles()['/baseUrl/css/tv.css']['attributes'],
        );

        $this->assertStringContainsString(
            '/baseUrl/css/screen_and_print.css',
            $this->manager->getCssFiles()['/baseUrl/css/screen_and_print.css']['url'],
        );
        $this->assertEquals(
            [
                'media' => 'screen, print',
                'hreflang' => 'en',
            ],
            $this->manager->getCssFiles()['/baseUrl/css/screen_and_print.css']['attributes'],
        );

        $this->assertStringContainsString(
            '/baseUrl/js/normal.js',
            $this->manager->getJsFiles()['/baseUrl/js/normal.js']['url'],
        );
        $this->assertEquals(
            [
                'charset' => 'utf-8',
                'position' => 3,
            ],
            $this->manager->getJsFiles()['/baseUrl/js/normal.js']['attributes'],
        );

        $this->assertStringContainsString(
            '/baseUrl/js/defered.js',
            $this->manager->getJsFiles()['/baseUrl/js/defered.js']['url'],
        );
        $this->assertEquals(
            [
                'charset' => 'utf-8',
                'defer' => true,
                'position' => 3,
            ],
            $this->manager->getJsFiles()['/baseUrl/js/defered.js']['attributes'],
        );
    }
}
