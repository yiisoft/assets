<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Yiisoft\Assets\AssetLoaderInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\Tests\stubs\BaseAsset;
use Yiisoft\Assets\Tests\stubs\CdnAsset;
use Yiisoft\Assets\Tests\stubs\CdnWithBaseUrlAsset;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\WithoutBaseAsset;
use Yiisoft\Files\FileHelper;

final class AssetLoaderTest extends TestCase
{
    public function testLoadBundleWithDefaultPathAndUrl(): void
    {
        $loader = $this->loader->withBasePath('@asset')->withBaseUrl('@assetUrl');
        $bundle = $loader->loadBundle(WithoutBaseAsset::class);

        $this->assertSame($this->aliases->get('@asset'), $bundle->basePath);
        $this->assertSame($this->aliases->get('@assetUrl'), $bundle->baseUrl);
    }

    public function testBaseAppendTimestamp(): void
    {
        $bundle = new BaseAsset();
        $manager = new AssetManager($this->aliases, $this->loader->withAppendTimestamp(true));

        $timestampCss = FileHelper::lastModifiedTime($this->aliases->get($bundle->basePath) . '/' . $bundle->css[0]);
        $urlCss = "/baseUrl/css/basePath.css?v=$timestampCss";

        $timestampJs = FileHelper::lastModifiedTime($this->aliases->get($bundle->basePath) . '/' . $bundle->js[0]);
        $urlJs = "/baseUrl/js/basePath.js?v=$timestampJs";

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $manager->register([BaseAsset::class]);

        $this->assertStringContainsString($urlCss, $manager->getCssFiles()[$urlCss]['url']);
        $this->assertEquals(
            [
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
            ],
            $manager->getCssFiles()[$urlCss]['attributes'],
        );

        $this->assertStringContainsString($urlJs, $manager->getJsFiles()[$urlJs]['url']);
        $this->assertEquals(
            [
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
                'position' => 3,
            ],
            $manager->getJsFiles()[$urlJs]['attributes'],
        );
    }

    public function testWithAssetMap(): void
    {
        $urlJs = '//testme.css';
        $loader = $this->loader->withAssetMap(['jquery.js' => $urlJs]);
        $manager = new AssetManager($this->aliases, $loader);

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $manager->register([JqueryAsset::class]);

        $this->assertStringContainsString($urlJs, $manager->getJsFiles()[$urlJs]['url']);
        $this->assertEquals(['position' => 3], $manager->getJsFiles()[$urlJs]['attributes']);
    }

    public function testWithAssetMapWithCustomizedBundles(): void
    {
        $urlJs = '//testme.css';
        $loader = $this->loader->withAssetMap(['jquery.js' => $urlJs]);
        $manager = new AssetManager($this->aliases, $loader, [], [JqueryAsset::class => ['js' => ['dist/jquery.js']]]);

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $manager->register([JqueryAsset::class]);

        $this->assertStringContainsString($urlJs, $manager->getJsFiles()[$urlJs]['url']);
        $this->assertEquals(['position' => 3], $manager->getJsFiles()[$urlJs]['attributes']);
    }

    public function testAssetUrlWithCdn(): void
    {
        $loader = $this->loader->withBaseUrl('https://example.com/test');

        $this->assertSame(
            'https://example.com/main.css',
            $loader->getAssetUrl(new CdnAsset(), 'https://example.com/main.css'),
        );

        $this->assertSame(
            'https://example.com/base/main.css',
            $loader->getAssetUrl(new CdnWithBaseUrlAsset(), 'main.css'),
        );
    }

    public function testSettersImmutability(): void
    {
        $loader = $this->loader->withAppendTimestamp(false);
        $this->assertInstanceOf(AssetLoaderInterface::class, $loader);
        $this->assertNotSame($this->loader, $loader);

        $loader = $this->loader->withAssetMap([]);
        $this->assertInstanceOf(AssetLoaderInterface::class, $loader);
        $this->assertNotSame($this->loader, $loader);

        $loader = $this->loader->withBasePath(null);
        $this->assertInstanceOf(AssetLoaderInterface::class, $loader);
        $this->assertNotSame($this->loader, $loader);

        $loader = $this->loader->withBaseUrl(null);
        $this->assertInstanceOf(AssetLoaderInterface::class, $loader);
        $this->assertNotSame($this->loader, $loader);

        $loader = $this->loader->withCssDefaultOptions([]);
        $this->assertInstanceOf(AssetLoaderInterface::class, $loader);
        $this->assertNotSame($this->loader, $loader);

        $loader = $this->loader->withJsDefaultOptions([]);
        $this->assertInstanceOf(AssetLoaderInterface::class, $loader);
        $this->assertNotSame($this->loader, $loader);
    }
}
