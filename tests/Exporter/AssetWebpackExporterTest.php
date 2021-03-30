<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\Exporter;

use RuntimeException;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\Exporter\AssetWebpackExporter;
use Yiisoft\Assets\Tests\stubs\CdnAsset;
use Yiisoft\Assets\Tests\stubs\PositionAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;
use Yiisoft\Assets\Tests\stubs\WebpackAsset;
use Yiisoft\Assets\Tests\TestCase;

use function dirname;
use function file_get_contents;

final class AssetWebpackExporterTest extends TestCase
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
        $webpackBundle = new WebpackAsset();

        $expected = "import '{$this->aliases->get($sourceBundle->sourcePath)}/{$sourceBundle->css[0]}';\n"
            . "import '{$this->aliases->get($sourceBundle->sourcePath)}/{$sourceBundle->js[0]}';\n"
            . "import '{$this->aliases->get($webpackBundle->sourcePath)}/{$webpackBundle->css[0]}';\n"
            . "import '{$this->aliases->get($webpackBundle->sourcePath)}/{$webpackBundle->js[0]}';\n"
        ;

        $this->manager->register([CdnAsset::class, PositionAsset::class, WebpackAsset::class]);
        $this->manager->export(new AssetWebpackExporter($targetFile));

        $this->assertFileExists($targetFile);
        $this->assertSame($expected, file_get_contents($targetFile));
    }

    public function testExportWithoutRegisterAndWithAllowedAndCustomizedBundles(): void
    {
        $targetFile = $this->aliases->get('@exporter/test.js');
        $manager = new AssetManager($this->aliases, $this->loader, [CdnAsset::class, WebpackAsset::class], [
            WebpackAsset::class => [
                'css' => ['css/stub.css'],
                'js' => ['js/stub.js'],
            ],
        ]);
        $manager->setPublisher($this->publisher);
        $sourceBundle = new SourceAsset();

        $expected = "import '{$this->aliases->get($sourceBundle->sourcePath)}/{$sourceBundle->css[0]}';\n"
            . "import '{$this->aliases->get($sourceBundle->sourcePath)}/{$sourceBundle->js[0]}';\n"
        ;

        $manager->export(new AssetWebpackExporter($targetFile));

        $this->assertFileExists($targetFile);
        $this->assertSame($expected, file_get_contents($targetFile));
    }

    public function testExportThrowExceptionForNotExistTargetDirectory(): void
    {
        $targetFile = $this->aliases->get('@exporter/not-exist/test.js');
        $exporter = new AssetWebpackExporter($targetFile);
        $targetDirectory = dirname($targetFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Target directory \"{$targetDirectory}\" does not exist or is not writable.");

        $exporter->export([SourceAsset::class => new SourceAsset()]);
    }
}
