<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetLoader;
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
        $loader = $this->loader
            ->withBasePath('@asset')
            ->withBaseUrl('@assetUrl');
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

        $manager->register(BaseAsset::class);

        $this->assertSame(
            [
                $urlCss,
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
            ],
            $manager->getCssFiles()[$urlCss],
        );

        $this->assertEquals(
            [
                $urlJs,
                'data-test' => 'one',
            ],
            $manager->getJsFiles()[$urlJs],
        );
    }

    public function testWithAssetMap(): void
    {
        $urlJs = '//testme.css';
        $loader = $this->loader->withAssetMap(['jquery.js' => $urlJs]);
        $manager = new AssetManager($this->aliases, $loader);

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $manager->register(JqueryAsset::class);

        $this->assertSame([$urlJs], $manager->getJsFiles()[$urlJs]);
    }

    public function testWithAssetMapWithCustomizedBundles(): void
    {
        $urlJs = '//testme.css';
        $loader = $this->loader->withAssetMap(['jquery.js' => $urlJs]);
        $manager = new AssetManager($this->aliases, $loader, [], [JqueryAsset::class => ['js' => ['dist/jquery.js']]]);

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $manager->register(JqueryAsset::class);

        $this->assertSame([$urlJs], $manager->getJsFiles()[$urlJs]);
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

    public function testCssDefaultPosition(): void
    {
        $loader = $this->createLoader()->withCssDefaultPosition(5);

        $bundle = $loader->loadBundle('test', (array)(new AssetBundle()));

        $this->assertSame(5, $bundle->cssPosition);
        $this->assertNull($bundle->jsPosition);
    }

    public function testCssDefaultPostionForBundleWithPosition(): void
    {
        $loader = $this->createLoader()->withCssDefaultPosition(5);

        $config = (array)(new AssetBundle());
        $config['cssPosition'] = 7;
        $bundle = $loader->loadBundle('test', $config);

        $this->assertSame(7, $bundle->cssPosition);
        $this->assertNull($bundle->jsPosition);
    }

    public function testJsDefaultPosition(): void
    {
        $loader = $this->createLoader()->withJsDefaultPosition(5);

        $bundle = $loader->loadBundle('test', (array)(new AssetBundle()));

        $this->assertSame(5, $bundle->jsPosition);
        $this->assertNull($bundle->cssPosition);
    }

    public function testJsDefaultPostionForBundleWithPosition(): void
    {
        $loader = $this->createLoader()->withJsDefaultPosition(5);

        $config = (array)(new AssetBundle());
        $config['jsPosition'] = 7;
        $bundle = $loader->loadBundle('test', $config);

        $this->assertSame(7, $bundle->jsPosition);
        $this->assertNull($bundle->cssPosition);
    }

    public function testImmutability(): void
    {
        $loader = $this->createLoader();
        $this->assertNotSame($loader, $loader->withAppendTimestamp(false));
        $this->assertNotSame($loader, $loader->withAssetMap([]));
        $this->assertNotSame($loader, $loader->withBasePath(null));
        $this->assertNotSame($loader, $loader->withBaseUrl(null));
        $this->assertNotSame($loader, $loader->withCssDefaultOptions([]));
        $this->assertNotSame($loader, $loader->withCssDefaultPosition(null));
        $this->assertNotSame($loader, $loader->withJsDefaultOptions([]));
        $this->assertNotSame($loader, $loader->withJsDefaultPosition(null));
    }

    private function createLoader(): AssetLoader
    {
        return new AssetLoader(new Aliases());
    }
}
