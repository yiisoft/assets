<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Assets\Tests\TestCase;
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

        $this->assetManager->setConverter(null);

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

        $this->webView->setCssFiles($this->assetManager->getCssFiles());
        $this->webView->setJsFiles($this->assetManager->getJsFiles());

        $this->assertEquals(
            $this->assetManager->getPublishedPath($bundle->sourcePath),
            $this->aliases->get("@public/assets/$path")
        );
    }

    public function testGetPublishedPathWrong(): void
    {
        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([SourceAsset::class]);


        $this->webView->setCssFiles($this->assetManager->getCssFiles());
        $this->webView->setJsFiles($this->assetManager->getJsFiles());

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

        $this->webView->setCssFiles($this->assetManager->getCssFiles());
        $this->webView->setJsFiles($this->assetManager->getJsFiles());

        $this->assertEquals(
            $this->assetManager->getPublishedUrl($bundle->sourcePath),
            "/baseUrl/$path"
        );
    }

    public function testGetPublishedUrlWrong(): void
    {
        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([SourceAsset::class]);

        $this->webView->setCssFiles($this->assetManager->getCssFiles());
        $this->webView->setJsFiles($this->assetManager->getJsFiles());

        $this->assertNull($this->assetManager->getPublishedUrl('/wrong'));
    }

    public function testAssetManagerSetBundles(): void
    {
        $this->assetManager->setBundles(
            [
                JqueryAsset::class => [
                    'sourcePath' => null, //no publish asset bundle
                    'js' => [
                        [
                            'https://code.jquery.com/jquery-3.4.1.js',
                            'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
                            'crossorigin' => 'anonymous'
                        ]
                    ]
                ]
            ]
        );

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([JqueryAsset::class]);

        $this->webView->setCssFiles($this->assetManager->getCssFiles());
        $this->webView->setJsFiles($this->assetManager->getJsFiles());

        $expected = <<<'EOF'
123<script src="https://code.jquery.com/jquery-3.4.1.js" integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=" crossorigin="anonymous"></script>4
EOF;
        $this->assertEquals(
            $expected,
            $this->webView->renderFile($this->aliases->get('@view/rawlayout.php'))
        );
    }

    public function testAssetManagerSetAssetMap(): void
    {
        $this->assetManager->setAssetMap(
            [
                'jquery.js' => '//testme.css',
            ]
        );

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([JqueryAsset::class]);

        $this->webView->setCssFiles($this->assetManager->getCssFiles());
        $this->webView->setJsFiles($this->assetManager->getJsFiles());

        $expected = <<<'EOF'
123<script src="//testme.css"></script>4
EOF;
        $this->assertEquals(
            $expected,
            $this->webView->renderFile($this->aliases->get('@view/rawlayout.php'))
        );
    }
}
