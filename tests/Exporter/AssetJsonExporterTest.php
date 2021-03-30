<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\Exporter;

use RuntimeException;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetUtil;
use Yiisoft\Assets\Exporter\AssetJsonExporter;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\Level3Asset;
use Yiisoft\Assets\Tests\stubs\PositionAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;
use Yiisoft\Assets\Tests\TestCase;
use Yiisoft\Json\Json;

use function dirname;
use function file_get_contents;

final class AssetJsonExporterTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@exporter');
    }

    public function testExportWithCreateDependencies(): void
    {
        $targetFile = $this->aliases->get('@exporter/test.json');
        $expected = Json::encode([
            Level3Asset::class => AssetUtil::resolvePathAliases(new Level3Asset(), $this->aliases),
            JqueryAsset::class => AssetUtil::resolvePathAliases(new JqueryAsset(), $this->aliases),
            PositionAsset::class => AssetUtil::resolvePathAliases(new PositionAsset(), $this->aliases),
        ]);

        $this->manager->register([PositionAsset::class]);
        $this->manager->export(new AssetJsonExporter($targetFile));

        $this->assertFileExists($targetFile);
        $this->assertSame($expected, file_get_contents($targetFile));
    }

    public function testExportWithoutRegisterWithAllowedAndCustomizedBundles(): void
    {
        $targetFile = $this->aliases->get('@exporter/test.json');
        $config = [
            'basePath' => '@root/tests/public/jquery',
            'baseUrl' => '/js',
            'js' => ['jquery.js'],
            'css' => [],
            'sourcePath' => '',
        ];
        $sourceBundle = AssetUtil::createAsset(SourceAsset::class, $config);
        $manager = new AssetManager($this->aliases, $this->loader, [SourceAsset::class], [
            SourceAsset::class => $config,
        ]);
        $manager->setPublisher($this->publisher);
        $expected = Json::encode([
            Level3Asset::class => AssetUtil::resolvePathAliases(new Level3Asset(), $this->aliases),
            JqueryAsset::class => AssetUtil::resolvePathAliases(new JqueryAsset(), $this->aliases),
            SourceAsset::class => AssetUtil::resolvePathAliases($sourceBundle, $this->aliases),
        ]);

        $manager->export(new AssetJsonExporter($targetFile));

        $this->assertFileExists($targetFile);
        $this->assertSame($expected, file_get_contents($targetFile));
    }

    public function testExportWithoutRegisterAndWithAllowed(): void
    {
        $targetFile = $this->aliases->get('@exporter/test.json');
        $manager = new AssetManager($this->aliases, $this->loader, [PositionAsset::class]);
        $manager->setPublisher($this->publisher);
        $expected = Json::encode([
            Level3Asset::class => AssetUtil::resolvePathAliases(new Level3Asset(), $this->aliases),
            JqueryAsset::class => AssetUtil::resolvePathAliases(new JqueryAsset(), $this->aliases),
            PositionAsset::class => AssetUtil::resolvePathAliases(new PositionAsset(), $this->aliases),
        ]);

        $manager->export(new AssetJsonExporter($targetFile));

        $this->assertFileExists($targetFile);
        $this->assertSame($expected, file_get_contents($targetFile));
    }

    public function testExportToJsonFileThrowExceptionForNotExistTargetDirectory(): void
    {
        $targetFile = $this->aliases->get('@exporter/not-exist/test.json');
        $exporter = new AssetJsonExporter($targetFile);
        $targetDirectory = dirname($targetFile);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Target directory \"{$targetDirectory}\" does not exist or is not writable.");

        $exporter->export([PositionAsset::class => new PositionAsset()]);
    }
}
