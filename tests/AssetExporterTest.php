<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use RuntimeException;
use stdClass;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetExporter;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\Level3Asset;
use Yiisoft\Assets\Tests\stubs\PositionAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;

use function array_keys;
use function dirname;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function implode;
use function is_subclass_of;
use function str_replace;

final class AssetExporterTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@exporter');
    }

    public function testExport(): void
    {
        $result = '';
        $exporter = $this->createAssetExporter();
        $export = static function (array $assets) use (&$result): void {
            $result = implode(',', array_keys($assets));
        };

        $expected = PositionAsset::class . ',' . JqueryAsset::class . ',' . Level3Asset::class;
        $exporter->export($export);

        $this->assertSame($expected, $result);
    }

    public function testExportToJson(): void
    {
        $exporter = $this->createAssetExporter();
        $expected = $this->encode([
            PositionAsset::class => new PositionAsset(),
            JqueryAsset::class => new JqueryAsset(),
            Level3Asset::class => new Level3Asset(),
        ]);

        $this->assertSame($expected, $exporter->exportToJson());
    }

    public function testExportToJsonFile(): void
    {
        $exporter = $this->createAssetExporter();
        $expected = $this->encode([
            PositionAsset::class => new PositionAsset(),
            JqueryAsset::class => new JqueryAsset(),
            Level3Asset::class => new Level3Asset(),
        ]);

        $targetFile = '@exporter/test.json';
        $exporter->exportToJsonFile($targetFile);

        $this->assertFileExists($this->aliases->get($targetFile));
        $this->assertSame($expected, file_get_contents($this->aliases->get($targetFile)));
    }

    public function testExportToJsonFileThrowExceptionForNotExistTargetDirectory(): void
    {
        $exporter = $this->createAssetExporter();
        $targetFile = '@exporter/not-exist/test.json';
        $targetDirectory = $this->aliases->get(dirname($targetFile));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Target directory \"{$targetDirectory}\" does not exist or is not writable.");

        $exporter->exportToJsonFile($targetFile);
    }

    public function invalidBundleConfigurationProvider(): array
    {
        return [
            'int' => [1],
            'float' => [1.1],
            'string' => ['a'],
            'bool' => [true],
            'callable' => [fn () => null],
            'object-not-asset-bundle' => [new stdClass()],
        ];
    }

    /**
     * @dataProvider invalidBundleConfigurationProvider
     *
     * @param mixed $config
     */
    public function testBuildBundleThrowExceptionForInvalidConfiguration($config): void
    {
        $exporter = $this->createAssetExporter([JqueryAsset::class => $config]);

        $this->expectException(InvalidConfigException::class);

        $exporter->exportToJson();
    }

    public function testBuildBundleDependencies(): void
    {
        $exporter = $this->createAssetExporter([JqueryAsset::class => null]);
        $json = $this->encode([
            JqueryAsset::class => new JqueryAsset(),
            Level3Asset::class => new Level3Asset(),
        ]);

        $this->assertSame($json, $exporter->exportToJson());
        $this->assertSame($this->aliases->get('@assetUrl/js'), $this->decode($json)[Level3Asset::class]->baseUrl);
    }

    public function testBuildBundleDependenciesWithSpecifyDependency(): void
    {
        $changedBaseUrl = '@assetUrl/test/js';
        $exporter = $this->createAssetExporter([
            JqueryAsset::class => new JqueryAsset(),
            Level3Asset::class => ['baseUrl' => $changedBaseUrl],
        ]);

        $json = $exporter->exportToJson();

        $this->assertStringContainsString($this->aliases->get($changedBaseUrl), $json);
        $this->assertSame($this->aliases->get($changedBaseUrl), $this->decode($json)[Level3Asset::class]->baseUrl);
    }

    public function testResolvePathAliases(): void
    {
        $exporter = $this->createAssetExporter([SourceAsset::class => null]);
        $sourceBundle = new SourceAsset();
        $json = $exporter->exportToJson();

        // For OS Windows
        $basePath = str_replace('\\', '\\\\', $this->aliases->get($sourceBundle->basePath));
        $sourcePath = str_replace('\\', '\\\\', $this->aliases->get($sourceBundle->sourcePath));

        $this->assertStringContainsString($basePath, $json);
        $this->assertStringContainsString($this->aliases->get($sourceBundle->baseUrl), $json);
        $this->assertStringContainsString($sourcePath, $json);
    }

    public function testExportWithoutUseClassNamesAsKeysInConfiguration(): void
    {
        $exporter = $this->createAssetExporter([
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
        $json = $this->encode(['first' => $first, 'second' => $second, 'third' => $third]);

        $this->assertSame($json, $exporter->exportToJson());
    }

    private function createAssetExporter(array $bundles = null): AssetExporter
    {
        return new AssetExporter($bundles ?? [PositionAsset::class => null], $this->aliases);
    }

    private function encode(array $data): string
    {
        $exporter = $this->createAssetExporter();
        $resolved = [];

        foreach ($data as $name => $object) {
            $resolved[$name] = $this->invokeMethod($exporter, 'resolvePathAliases', [$object]);
        }

        return json_encode($resolved, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function decode(string $data): array
    {
        $instances = [];

        foreach (json_decode($data) as $name => $object) {
            $instance = is_subclass_of($name, AssetBundle::class) ? new $name() : new AssetBundle();

            foreach ((array) $object as $property => $value) {
                $instance->{$property} = is_object($value) ? (array) $value : $value;
            }

            $instances[$name] = $instance;
        }

        return $instances;
    }
}
