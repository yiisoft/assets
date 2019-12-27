<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;

final class AssetManagerTest extends TestCase
{
    public function testGetConverter(): void
    {
        $this->assertInstanceOf(
            AssetConverterInterface::class,
            $this->assetManager->getConverter()
        );
    }

    public function testGetPublishedPath(): void
    {
        $bundle = new SourceAsset();

        $sourcePath = $this->aliases->get($bundle->sourcePath);

        $path = $sourcePath . @filemtime($sourcePath);
        $path = sprintf('%x', crc32($path . '|' . $this->assetManager->getLinkAssets()));

        $this->assertEmpty($this->assetManager->getAssetBundles());
        $this->assetManager->register([SourceAsset::class]);

        $this->assertEquals(
            $this->assetManager->getPublishedPath($bundle->sourcePath),
            $this->aliases->get("@public/assets/$path")
        );
    }

    public function testGetPublishedPathWrong(): void
    {
        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([SourceAsset::class]);

        $this->assertNull($this->assetManager->getPublishedPath('/wrong'));
    }

    public function testGetPublishedUrl(): void
    {
        $bundle = new SourceAsset();

        $sourcePath = $this->aliases->get($bundle->sourcePath);

        $path = $sourcePath . @filemtime($sourcePath);
        $path = sprintf('%x', crc32($path . '|' . $this->assetManager->getLinkAssets()));

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([SourceAsset::class]);

        $this->assertEquals(
            $this->assetManager->getPublishedUrl($bundle->sourcePath),
            "/baseUrl/$path"
        );
    }

    public function testGetPublishedUrlWrong(): void
    {
        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([SourceAsset::class]);

        $this->assertNull($this->assetManager->getPublishedUrl('/wrong'));
    }

    public function testAssetManagerSetBundles(): void
    {
        $urlJs = 'https://code.jquery.com/jquery-3.4.1.js';

        $this->assetManager->setBundles(
            [
                JqueryAsset::class => [
                    'sourcePath' => null, //no publish asset bundle
                    'js' => [
                        [
                            $urlJs,
                            'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
                            'crossorigin' => 'anonymous'
                        ]
                    ]
                ]
            ]
        );

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([JqueryAsset::class]);

        $this->assertStringContainsString(
            $urlJs,
            $this->assetManager->getJsFiles()[$urlJs]['url']
        );
        $this->assertEquals(
            [
                'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
                'crossorigin' => 'anonymous',
                'position' => 3
            ],
            $this->assetManager->getJsFiles()[$urlJs]['attributes']
        );
    }

    public function testAssetManagerSetAssetMap(): void
    {
        $urlJs = '//testme.css';

        $this->assetManager->setAssetMap(
            [
                'jquery.js' => $urlJs,
            ]
        );

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([JqueryAsset::class]);

        $this->assertStringContainsString(
            $urlJs,
            $this->assetManager->getJsFiles()[$urlJs]['url']
        );
        $this->assertEquals(
            [
                'position' => 3
            ],
            $this->assetManager->getJsFiles()[$urlJs]['attributes']
        );
    }
}
