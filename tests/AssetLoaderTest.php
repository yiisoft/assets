<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

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
        $this->loader->setBasePath('@asset');
        $this->loader->setBaseUrl('@assetUrl');

        $bundle = $this->loader->loadBundle(WithoutBaseAsset::class);

        $this->assertSame($this->aliases->get('@asset'), $bundle->basePath);
        $this->assertSame($this->aliases->get('@assetUrl'), $bundle->baseUrl);
    }

    public function testBaseAppendTimestamp(): void
    {
        $bundle = new BaseAsset();
        $manager = new AssetManager($this->aliases, $this->loader);

        $timestampCss = FileHelper::lastModifiedTime($this->aliases->get($bundle->basePath) . '/' . $bundle->css[0]);
        $urlCss = "/baseUrl/css/basePath.css?v=$timestampCss";

        $timestampJs = FileHelper::lastModifiedTime($this->aliases->get($bundle->basePath) . '/' . $bundle->js[0]);
        $urlJs = "/baseUrl/js/basePath.js?v=$timestampJs";

        $this->assertEmpty($manager->getRegisteredBundles());

        $this->loader->setAppendTimestamp(true);

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

    public function testSetAssetMap(): void
    {
        $manager = new AssetManager($this->aliases, $this->loader);
        $urlJs = '//testme.css';

        $this->loader->setAssetMap(['jquery.js' => $urlJs]);

        $this->assertEmpty($manager->getRegisteredBundles());

        $manager->register([JqueryAsset::class]);

        $this->assertStringContainsString($urlJs, $manager->getJsFiles()[$urlJs]['url']);
        $this->assertEquals(['position' => 3], $manager->getJsFiles()[$urlJs]['attributes']);
    }

    public function testAssetUrlWithCdn(): void
    {
        $this->loader->setBaseUrl('https://example.com/test');

        $this->assertSame(
            'https://example.com/main.css',
            $this->loader->getAssetUrl(new CdnAsset(), 'https://example.com/main.css'),
        );

        $this->assertSame(
            'https://example.com/base/main.css',
            $this->loader->getAssetUrl(new CdnWithBaseUrlAsset(), 'main.css'),
        );
    }
}
