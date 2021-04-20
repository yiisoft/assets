<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\Exporter;

use RuntimeException;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetUtil;
use Yiisoft\Assets\Exporter\JsonAssetExporter;
use Yiisoft\Assets\Tests\stubs\CdnAsset;
use Yiisoft\Assets\Tests\stubs\ExportAsset;
use Yiisoft\Assets\Tests\stubs\PositionAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;
use Yiisoft\Assets\Tests\TestCase;
use Yiisoft\Json\Json;

use function dirname;
use function file_get_contents;

final class JsonAssetExporterTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@exporter');
    }

    public function testExportWithCreateDependencies(): void
    {
        $targetFile = $this->aliases->get('@exporter/test.json');
        $exportBundle = AssetUtil::resolvePathAliases(new ExportAsset(), $this->aliases);
        $sourceBundle = AssetUtil::resolvePathAliases(new SourceAsset(), $this->aliases);
        $expected = Json::encode([
            "{$sourceBundle->sourcePath}/{$sourceBundle->css[0]}",
            "{$sourceBundle->sourcePath}/{$sourceBundle->js[0]}",
            "{$exportBundle->sourcePath}/{$exportBundle->export[0]}",
            "{$exportBundle->sourcePath}/{$exportBundle->export[1]}",
        ]);

        $this->manager->register([ExportAsset::class]);
        $this->manager->export(new JsonAssetExporter($targetFile));

        $this->assertFileExists($targetFile);
        $this->assertSame($expected, file_get_contents($targetFile));
    }

    public function testExportWithoutRegisterAndWithAllowed(): void
    {
        $targetFile = $this->aliases->get('@exporter/test.json');
        $exportBundle = AssetUtil::resolvePathAliases(new ExportAsset(), $this->aliases);
        $sourceBundle = AssetUtil::resolvePathAliases(new SourceAsset(), $this->aliases);
        $manager = new AssetManager($this->aliases, $this->loader, [ExportAsset::class]);

        $expected = Json::encode([
            "{$sourceBundle->sourcePath}/{$sourceBundle->css[0]}",
            "{$sourceBundle->sourcePath}/{$sourceBundle->js[0]}",
            "{$exportBundle->sourcePath}/{$exportBundle->export[0]}",
            "{$exportBundle->sourcePath}/{$exportBundle->export[1]}",
        ]);

        $manager->setPublisher($this->publisher);
        $manager->export(new JsonAssetExporter($targetFile));

        $this->assertFileExists($targetFile);
        $this->assertSame($expected, file_get_contents($targetFile));
    }

    public function testExportWithoutRegisterWithAllowedAndCustomizedBundles(): void
    {
        $targetFile = $this->aliases->get('@exporter/test.json');
        $config = [
            'js' => ['jquery.js'],
            'css' => [],
            'export' => [],
            'sourcePath' => '@root/tests/public/jquery',
        ];

        $sourceBundle = AssetUtil::resolvePathAliases(new SourceAsset(), $this->aliases);
        $exportBundle = AssetUtil::resolvePathAliases(
            AssetUtil::createAsset(ExportAsset::class, $config),
            $this->aliases,
        );

        $manager = new AssetManager($this->aliases, $this->loader, [ExportAsset::class], [
            ExportAsset::class => $config,
        ]);

        $expected = Json::encode([
            "{$sourceBundle->sourcePath}/{$sourceBundle->css[0]}",
            "{$sourceBundle->sourcePath}/{$sourceBundle->js[0]}",
            "{$exportBundle->sourcePath}/{$exportBundle->js[0]}",
        ]);

        $manager->setPublisher($this->publisher);
        $manager->export(new JsonAssetExporter($targetFile));

        $this->assertFileExists($targetFile);
        $this->assertSame($expected, file_get_contents($targetFile));
    }

    public function testExportWithCdnBundle(): void
    {
        $targetFile = $this->aliases->get('@exporter/test.json');
        $this->manager->register([CdnAsset::class]);
        $this->manager->export(new JsonAssetExporter($targetFile));

        $this->assertFileExists($targetFile);
        $this->assertSame('[]', file_get_contents($targetFile));
    }

    public function testExportWithBundleWithoutSourcePath(): void
    {
        $targetFile = $this->aliases->get('@exporter/test.json');
        $this->manager->register([PositionAsset::class]);
        $this->manager->export(new JsonAssetExporter($targetFile));

        $this->assertFileExists($targetFile);
        $this->assertSame('[]', file_get_contents($targetFile));
    }

    public function testExportToJsonFileThrowExceptionForNotExistTargetDirectory(): void
    {
        $targetFile = $this->aliases->get('@exporter/not-exist/test.json');
        $exporter = new JsonAssetExporter($targetFile);
        $targetDirectory = dirname($targetFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Target directory \"{$targetDirectory}\" does not exist or is not writable.");

        $exporter->export([ExportAsset::class => new ExportAsset()]);
    }
}
