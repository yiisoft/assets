<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use RuntimeException;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\Level3Asset;
use Yiisoft\Assets\Tests\stubs\PositionAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;
use Yiisoft\Files\FileHelper;

use function crc32;
use function sprintf;

final class AssetManagerTest extends TestCase
{
    public function testGetConverter(): void
    {
        $this->assertInstanceOf(
            AssetConverterInterface::class,
            $this->manager->getConverter(),
        );
    }

    public function testGetPublishedPathLinkAssetsFalse(): void
    {
        $bundle = new SourceAsset();

        $sourcePath = $this->aliases->get($bundle->sourcePath);

        $path = $sourcePath . FileHelper::lastModifiedTime($sourcePath);
        $path = sprintf('%x', crc32($path . '|' . $this->manager->getPublisher()->getLinkAssets()));

        $this->assertEmpty($this->manager->getAssetBundles());
        $this->manager->register([SourceAsset::class]);

        $this->assertEquals(
            $this->manager->getPublisher()->getPublishedPath($bundle->sourcePath),
            $this->aliases->get("@root/tests/public/assets/{$path}"),
        );
    }

    public function testGetPublishedPathWrong(): void
    {
        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register([SourceAsset::class]);

        $this->assertNull($this->manager->getPublisher()->getPublishedPath('/wrong'));
    }

    public function testGetPublishedUrl(): void
    {
        $bundle = new SourceAsset();

        $sourcePath = $this->aliases->get($bundle->sourcePath);

        $path = $sourcePath . FileHelper::lastModifiedTime($sourcePath);
        $path = sprintf('%x', crc32($path . '|' . $this->manager->getPublisher()->getLinkAssets()));

        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register([SourceAsset::class]);

        $this->assertEquals(
            $this->manager->getPublisher()->getPublishedUrl($bundle->sourcePath),
            "/baseUrl/{$path}"
        );
    }

    public function testGetPublishedUrlWrong(): void
    {
        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register([SourceAsset::class]);

        $this->assertNull($this->manager->getPublisher()->getPublishedUrl('/wrong'));
    }

    public function testAssetManagerSetBundles(): void
    {
        $urlJs = 'https://code.jquery.com/jquery-3.4.1.js';

        $this->manager->setBundles(
            [
                JqueryAsset::class => [
                    'sourcePath' => null, //no publish asset bundle
                    'js' => [
                        [
                            $urlJs,
                            'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
                            'crossorigin' => 'anonymous',
                        ],
                    ],
                ],
            ]
        );

        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register([JqueryAsset::class]);

        $this->assertStringContainsString(
            $urlJs,
            $this->manager->getJsFiles()[$urlJs]['url'],
        );
        $this->assertEquals(
            [
                'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
                'crossorigin' => 'anonymous',
                'position' => 3,
            ],
            $this->manager->getJsFiles()[$urlJs]['attributes'],
        );
    }

    /**
     * @return array
     */
    public function positionProvider(): array
    {
        return [
            [1, true],
            [1, false],
            [2, true],
            [2, false],
            [3, true],
            [3, false],
        ];
    }

    /**
     * @dataProvider positionProvider
     *
     * @param int $pos
     * @param bool $jqAlreadyRegistered
     */
    public function testPositionDependency(int $pos, bool $jqAlreadyRegistered): void
    {
        $this->manager->setBundles([
            PositionAsset::class => [
                'jsOptions' => [
                    'position' => $pos,
                ],
            ],
        ]);

        $this->assertEmpty($this->manager->getAssetBundles());

        if ($jqAlreadyRegistered) {
            $this->manager->register([JqueryAsset::class, PositionAsset::class]);
        } else {
            $this->manager->register([PositionAsset::class]);
        }

        $this->assertCount(3, $this->manager->getAssetBundles());
        $this->assertArrayHasKey(PositionAsset::class, $this->manager->getAssetBundles());
        $this->assertArrayHasKey(JqueryAsset::class, $this->manager->getAssetBundles());
        $this->assertArrayHasKey(Level3Asset::class, $this->manager->getAssetBundles());

        $this->assertInstanceOf(AssetBundle::class, $this->manager->getAssetBundles()[PositionAsset::class]);
        $this->assertInstanceOf(AssetBundle::class, $this->manager->getAssetBundles()[JqueryAsset::class]);
        $this->assertInstanceOf(AssetBundle::class, $this->manager->getAssetBundles()[Level3Asset::class]);

        $this->assertArrayHasKey('position', $this->manager->getAssetBundles()[PositionAsset::class]->jsOptions);
        $this->assertEquals($pos, $this->manager->getAssetBundles()[PositionAsset::class]->jsOptions['position']);

        $this->assertArrayHasKey('position', $this->manager->getAssetBundles()[JqueryAsset::class]->jsOptions);
        $this->assertEquals($pos, $this->manager->getAssetBundles()[JqueryAsset::class]->jsOptions['position']);

        $this->assertArrayHasKey('position', $this->manager->getAssetBundles()[Level3Asset::class]->jsOptions);
        $this->assertEquals($pos, $this->manager->getAssetBundles()[Level3Asset::class]->jsOptions['position']);

        $this->assertEquals(['position' => $pos], $this->manager->getJsFiles()['/js/jquery.js']['attributes']);
        $this->assertEquals(['position' => $pos], $this->manager->getJsFiles()['/files/jsFile.js']['attributes']);
    }

    /**
     * @return array
     */
    public function positionProviderConflict(): array
    {
        return [
            [1, true],
            [1, false],
            [2, true],
            [2, false],
        ];
    }

    /**
     * @dataProvider positionProviderConflict
     *
     * @param int $pos
     * @param bool $jqAlreadyRegistered
     */
    public function testPositionDependencyConflict(int $pos, bool $jqAlreadyRegistered): void
    {
        $jqAsset = JqueryAsset::class;

        $this->manager->setBundles([
            PositionAsset::class => [
                'jsOptions' => [
                    'position' => $pos - 1,
                ],
            ],
            JqueryAsset::class => [
                'jsOptions' => [
                    'position' => $pos,
                ],
            ],
        ]);

        $message = "An asset bundle that depends on \"{$jqAsset}\" has a higher"
            . " javascript file position configured than \"{$jqAsset}\".";

        if ($jqAlreadyRegistered) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);

            $this->manager->register([JqueryAsset::class, PositionAsset::class]);
        } else {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);

            $this->manager->register([PositionAsset::class]);
        }
    }

    public function testLoadDummyBundle(): void
    {
        $jqueryBundle = new JqueryAsset();

        $this->manager->setBundles(
            [
                JqueryAsset::class => false,
            ]
        );

        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register([JqueryAsset::class]);

        $this->assertNotSame($jqueryBundle, $this->manager->getBundle(JqueryAsset::class));
        $this->assertEmpty($this->manager->getCssFiles());
        $this->assertEmpty($this->manager->getJsFiles());
    }

    public function testGetAssetBundleException(): void
    {
        $jqueryBundle = JqueryAsset::class;

        $this->manager->setBundles(
            [
                JqueryAsset::class => 'noExist',
            ],
        );

        $this->assertEmpty($this->manager->getAssetBundles());

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("Invalid configuration of the \"{$jqueryBundle}\" asset bundle.");

        $this->manager->register([JqueryAsset::class]);
    }

    public function testGetAssetBundleInstanceOfAssetBundle(): void
    {
        $jqueryBundle = new JqueryAsset();

        $this->manager->setBundles(
            [
                JqueryAsset::class => $jqueryBundle,
            ],
        );

        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register([JqueryAsset::class]);

        $this->assertSame($jqueryBundle, $this->manager->getBundle(JqueryAsset::class));
    }
}
