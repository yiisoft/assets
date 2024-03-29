<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\Exporter;

use RuntimeException;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\Exporter\WebpackAssetExporter;
use Yiisoft\Assets\Tests\stubs\CdnAsset;
use Yiisoft\Assets\Tests\stubs\ExportAsset;
use Yiisoft\Assets\Tests\stubs\PositionAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;
use Yiisoft\Assets\Tests\stubs\WebpackAsset;
use Yiisoft\Assets\Tests\TestCase;

use function dirname;
use function file_get_contents;

final class WebpackAssetExporterTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@exporter');
    }

    public function testExportWithCreateDependencies(): void
    {
        $targetFile = $this->aliases->get('@exporter/test.js');

        $sourceBundle = new SourceAsset();
        $exportBundle = new ExportAsset();
        $webpackBundle = new WebpackAsset();

        $expected = "import '{$this->aliases->get($sourceBundle->sourcePath)}/{$sourceBundle->css[0]}';\n"
            . "import '{$this->aliases->get($sourceBundle->sourcePath)}/{$sourceBundle->js[0]}';\n"
            . "import '{$this->aliases->get($exportBundle->sourcePath)}/{$exportBundle->export[0]}';\n"
            . "import '{$this->aliases->get($exportBundle->sourcePath)}/{$exportBundle->export[1]}';\n"
            . "import '{$this->aliases->get($webpackBundle->sourcePath)}/{$webpackBundle->css[0]}';\n"
            . "import '{$this->aliases->get($webpackBundle->sourcePath)}/{$webpackBundle->js[0][0]}';\n"
        ;

        $this->manager->registerMany([CdnAsset::class, PositionAsset::class, WebpackAsset::class]);
        $this->manager->export(new WebpackAssetExporter($targetFile));

        $this->assertFileExists($targetFile);
        $this->assertSame($expected, file_get_contents($targetFile));
    }

    public function testExportWithoutRegisterAndWithAllowedAndCustomizedBundles(): void
    {
        $targetFile = $this->aliases->get('@exporter/test.js');
        $manager = new AssetManager($this->aliases, $this->loader, [CdnAsset::class, WebpackAsset::class], [
            ExportAsset::class => [
                'export' => [
                    'css/stub.css',
                    'js/stub.js',
                ],
            ],
            WebpackAsset::class => [
                'css' => ['css/stub.css'],
                'js' => ['js/stub.js'],
            ],
        ]);
        $sourceBundle = new SourceAsset();

        $expected = "import '{$this->aliases->get($sourceBundle->sourcePath)}/{$sourceBundle->css[0]}';\n"
            . "import '{$this->aliases->get($sourceBundle->sourcePath)}/{$sourceBundle->js[0]}';\n"
        ;

        $manager = $manager->withPublisher($this->publisher);
        $manager->export(new WebpackAssetExporter($targetFile));

        $this->assertFileExists($targetFile);
        $this->assertSame($expected, file_get_contents($targetFile));
    }

    public function testExportThrowExceptionForNotExistTargetDirectory(): void
    {
        $targetFile = $this->aliases->get('@exporter/not-exist/test.js');
        $exporter = new WebpackAssetExporter($targetFile);
        $targetDirectory = dirname($targetFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Target directory \"{$targetDirectory}\" does not exist or is not writable.");

        $exporter->export([SourceAsset::class => new SourceAsset()]);
    }
}
