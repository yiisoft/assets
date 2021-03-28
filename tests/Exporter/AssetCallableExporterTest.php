<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\Exporter;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\Exporter\AssetCallableExporter;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\Level3Asset;
use Yiisoft\Assets\Tests\stubs\PositionAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;
use Yiisoft\Assets\Tests\TestCase;

use function array_keys;
use function implode;

final class AssetCallableExporterTest extends TestCase
{
    public function testExportWithCreateDependencies(): void
    {
        $result = '';
        $exporter = new AssetCallableExporter(static function (array $assets) use (&$result): void {
            $result = implode(',', array_keys($assets));
        });

        $this->manager->register([PositionAsset::class]);
        $this->manager->export($exporter);

        $this->assertSame(PositionAsset::class . ',' . JqueryAsset::class . ',' . Level3Asset::class, $result);
    }

    public function testExportWithoutRegisterWithAllowedAndCustomizedBundles(): void
    {
        $result = '';
        $exporter = new AssetCallableExporter(static function (array $assets) use (&$result): void {
            $result = $assets[SourceAsset::class]->baseUrl;
        });

        $manager = new AssetManager($this->aliases, $this->loader, [SourceAsset::class], [
            SourceAsset::class => [
                'baseUrl' => '@assetUrl/test',
            ],
        ]);
        $manager->setPublisher($this->publisher);

        $manager->export($exporter);

        $this->assertSame($manager->getBundle(SourceAsset::class)->baseUrl, $result);
    }

    public function testExportWithoutUseClassNamesAsKeysInConfiguration(): void
    {
        $result = '';
        $exporter = new AssetCallableExporter(static function (array $assets) use (&$result): void {
            $result = ['first' => $assets['first'], 'second' => $assets['second'], 'third' => $assets['third']];
        });

        $manager = new AssetManager($this->aliases, $this->loader, [], [
            'first' => ['cdn' => true, 'js' => ['https://example.com/first.js']],
            'second' => ['cdn' => true, 'js' => ['https://example.com/second.js'], 'depends' => ['first', 'third']],
        ]);

        $first = new AssetBundle();
        $first->cdn = true;
        $first->js = ['https://example.com/first.js'];

        $second = new AssetBundle();
        $second->cdn = true;
        $second->js = ['https://example.com/second.js'];
        $second->depends = ['first', 'third'];

        $third = new AssetBundle();

        $manager->register(['second']);
        $manager->export($exporter);

        $this->assertEquals(['first' => $first, 'second' => $second, 'third' => $third], $result);
    }
}
