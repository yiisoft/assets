<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use RuntimeException;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Assets\Tests\stubs\BaseAsset;
use Yiisoft\Assets\Tests\stubs\BaseWrongAsset;
use Yiisoft\Assets\Tests\stubs\CircleAsset;
use Yiisoft\Assets\Tests\stubs\CircleDependsAsset;
use Yiisoft\Assets\Tests\stubs\FileOptionsAsset;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\RootAsset;
use Yiisoft\Assets\Tests\stubs\SimpleAsset;

final class AssetBundleTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@asset');
    }

    public function testBasePath(): void
    {
        $this->assertEmpty($this->getRegisteredBundles($this->manager));

        $this->manager->register([BaseAsset::class]);

        $this->assertSame(
            [
                '/baseUrl/css/basePath.css',
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
            ],
            $this->manager->getCssFiles()['/baseUrl/css/basePath.css'],
        );

        $this->assertSame(
            [
                '/baseUrl/js/basePath.js',
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
            ],
            $this->manager->getJsFiles()['/baseUrl/js/basePath.js'],
        );
    }

    public function testBasePathEmptyException(): void
    {
        $manager = new AssetManager($this->aliases, $this->loader, [], [
            BaseAsset::class => [
                'basePath' => null,
            ],
        ]);

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $message = 'basePath must be set in AssetLoader->withBasePath($path)'
            . ' or AssetBundle property public ?string $basePath = $path';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $manager->register([BaseAsset::class]);
    }

    public function testBaseUrlEmptyString(): void
    {
        $manager = new AssetManager($this->aliases, $this->loader, [], [
            BaseAsset::class => [
                'baseUrl' => '',
            ],
        ]);

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $manager->register([RootAsset::class]);
    }

    public function testBaseUrlIsNotSetException(): void
    {
        $manager = (new AssetManager($this->aliases, $this->loader, [], [
            BaseAsset::class => [
                'basePath' => null,
                'baseUrl' => null,
            ],
        ]))->withLoader($this->loader->withBasePath('@asset'));

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $message = 'baseUrl must be set in AssetLoader->withBaseUrl($path)'
            . ' or AssetBundle property public ?string $baseUrl = $path';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $manager->register([BaseAsset::class]);
    }

    public function testBasePathEmptyWithAssetManagerWithBasePath(): void
    {
        $manager = $this->manager->withLoader($this->loader->withBasePath('@asset'));

        $this->assertEmpty($this->getRegisteredBundles($manager));
        $this->assertIsObject($manager->getBundle(BaseAsset::class));
        $this->assertInstanceOf(BaseAsset::class, $manager->getBundle(BaseAsset::class));
    }

    public function testBasePathEmptyBaseUrlEmptyWithAssetManagerWithBasePathWithBaseUrl(): void
    {
        $manager = $this->manager->withLoader($this->loader->withBasePath('@asset')->withBaseUrl('@assetUrl'));

        $this->assertEmpty($this->getRegisteredBundles($manager));
        $this->assertIsObject($manager->getBundle(BaseAsset::class));
        $this->assertInstanceOf(BaseAsset::class, $manager->getBundle(BaseAsset::class));
    }

    public function testBasePathWrongException(): void
    {
        $bundle = new BaseWrongAsset();

        $this->assertEmpty($this->getRegisteredBundles($this->manager));

        $file = $bundle->js[0];
        $message = "Asset files not found: \"{$bundle->basePath}/{$file}\".";

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->manager->register([BaseWrongAsset::class]);
    }

    public function testCircularDependency(): void
    {
        $depends = (new CircleDependsAsset())->depends;

        $message = "A circular dependency is detected for bundle \"$depends[0]\".";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->manager->register([CircleAsset::class]);
    }

    public function testDuplicateAssetFile(): void
    {
        $this->assertEmpty($this->getRegisteredBundles($this->manager));

        $this->manager->register([JqueryAsset::class, SimpleAsset::class]);

        $this->assertCount(3, $this->getRegisteredBundles($this->manager));
        $this->assertArrayHasKey(SimpleAsset::class, $this->getRegisteredBundles($this->manager));
        $this->assertInstanceOf(AssetBundle::class, $this->getRegisteredBundles($this->manager)[SimpleAsset::class]);

        $this->assertSame(
            ['/js/jquery.js'],
            $this->manager->getJsFiles()['/js/jquery.js'],
        );
    }

    public function testJsString(): void
    {
        $this->manager->register([BaseAsset::class]);

        $this->assertSame(
            [
                'uniqueName' => 'app1.start();',
                'app2.start();',
                'uniqueName2' => ['app3.start();', 3],
                ['app4.start();', 3],
            ],
            $this->manager->getJsStrings(),
        );
    }

    public function testJsVars(): void
    {
        $this->manager->register([BaseAsset::class]);

        $this->assertSame(
            [
                'var1' => 'value1',
                'var2' => [1, 2],
                ['var3', 'value3', 3],
            ],
            $this->manager->getJsVars(),
        );
    }

    public function testFileOptionsAsset(): void
    {
        $this->assertEmpty($this->getRegisteredBundles($this->manager));

        $this->manager->register([FileOptionsAsset::class]);

        $this->assertSame(
            [
                '/baseUrl/css/default_options.css',
                'media' => 'screen',
                'hreflang' => 'en',
            ],
            $this->manager->getCssFiles()['/baseUrl/css/default_options.css'],
        );
        $this->assertSame(
            [
                '/baseUrl/css/tv.css',
                'media' => 'tv',
                'hreflang' => 'en',
            ],
            $this->manager->getCssFiles()['/baseUrl/css/tv.css'],
        );
        $this->assertSame(
            [
                '/baseUrl/css/screen_and_print.css',
                'media' => 'screen, print',
                'hreflang' => 'en',
            ],
            $this->manager->getCssFiles()['/baseUrl/css/screen_and_print.css'],
        );
        $this->assertSame(
            [
                '/baseUrl/js/normal.js',
                'charset' => 'utf-8',
            ],
            $this->manager->getJsFiles()['/baseUrl/js/normal.js'],
        );
        $this->assertSame(
            [
                '/baseUrl/js/defered.js',
                'defer' => true,
                'charset' => 'utf-8',
            ],
            $this->manager->getJsFiles()['/baseUrl/js/defered.js'],
        );
    }
}
