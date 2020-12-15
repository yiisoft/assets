<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\Level3Asset;
use Yiisoft\Assets\Tests\stubs\PositionAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;
use Yiisoft\Files\FileHelper;

final class AssetManagerTest extends TestCase
{
    public function testGetConverter(): void
    {
        $this->assertInstanceOf(
            AssetConverterInterface::class,
            $this->assetManager->getConverter()
        );
    }

    public function testGetPublishedPathLinkAssetsFalse(): void
    {
        $bundle = new SourceAsset();

        $sourcePath = $this->aliases->get($bundle->sourcePath);

        $path = $sourcePath . FileHelper::lastModifiedTime($sourcePath);
        $path = sprintf('%x', crc32($path . '|' . $this->assetManager->getPublisher()->getLinkAssets()));

        $this->assertEmpty($this->assetManager->getAssetBundles());
        $this->assetManager->register([SourceAsset::class]);

        $this->assertEquals(
            $this->assetManager->getPublisher()->getPublishedPath($bundle->sourcePath),
            $this->aliases->get("@root/tests/public/assets/$path")
        );
    }

    public function testGetPublishedPathWrong(): void
    {
        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([SourceAsset::class]);

        $this->assertNull($this->assetManager->getPublisher()->getPublishedPath('/wrong'));
    }

    public function testGetPublishedUrl(): void
    {
        $bundle = new SourceAsset();

        $sourcePath = $this->aliases->get($bundle->sourcePath);

        $path = $sourcePath . FileHelper::lastModifiedTime($sourcePath);
        $path = sprintf('%x', crc32($path . '|' . $this->assetManager->getPublisher()->getLinkAssets()));

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([SourceAsset::class]);

        $this->assertEquals(
            $this->assetManager->getPublisher()->getPublishedUrl($bundle->sourcePath),
            "/baseUrl/$path"
        );
    }

    public function testGetPublishedUrlWrong(): void
    {
        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([SourceAsset::class]);

        $this->assertNull($this->assetManager->getPublisher()->getPublishedUrl('/wrong'));
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
                            'crossorigin' => 'anonymous',
                        ],
                    ],
                ],
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
                'position' => 3,
            ],
            $this->assetManager->getJsFiles()[$urlJs]['attributes']
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
        $this->assetManager->setBundles([
            PositionAsset::class => [
                'jsOptions' => [
                    'position' => $pos,
                ],
            ],
        ]);

        $this->assertEmpty($this->assetManager->getAssetBundles());

        if ($jqAlreadyRegistered) {
            $this->assetManager->register([JqueryAsset::class, PositionAsset::class]);
        } else {
            $this->assetManager->register([PositionAsset::class]);
        }

        $this->assertCount(3, $this->assetManager->getAssetBundles());
        $this->assertArrayHasKey(PositionAsset::class, $this->assetManager->getAssetBundles());
        $this->assertArrayHasKey(JqueryAsset::class, $this->assetManager->getAssetBundles());
        $this->assertArrayHasKey(Level3Asset::class, $this->assetManager->getAssetBundles());

        $this->assertInstanceOf(
            AssetBundle::class,
            $this->assetManager->getAssetBundles()[PositionAsset::class]
        );
        $this->assertInstanceOf(
            AssetBundle::class,
            $this->assetManager->getAssetBundles()[JqueryAsset::class]
        );
        $this->assertInstanceOf(
            AssetBundle::class,
            $this->assetManager->getAssetBundles()[Level3Asset::class]
        );

        $this->assertArrayHasKey(
            'position',
            $this->assetManager->getAssetBundles()[PositionAsset::class]->jsOptions
        );
        $this->assertEquals(
            $pos,
            $this->assetManager->getAssetBundles()[PositionAsset::class]->jsOptions['position']
        );
        $this->assertArrayHasKey(
            'position',
            $this->assetManager->getAssetBundles()[JqueryAsset::class]->jsOptions
        );

        $this->assertEquals(
            $pos,
            $this->assetManager->getAssetBundles()[JqueryAsset::class]->jsOptions['position']
        );
        $this->assertArrayHasKey(
            'position',
            $this->assetManager->getAssetBundles()[Level3Asset::class]->jsOptions
        );
        $this->assertEquals(
            $pos,
            $this->assetManager->getAssetBundles()[Level3Asset::class]->jsOptions['position']
        );

        $this->assertEquals(
            [
                'position' => $pos,
            ],
            $this->assetManager->getJsFiles()['/js/jquery.js']['attributes']
        );
        $this->assertEquals(
            [
                'position' => $pos,
            ],
            $this->assetManager->getJsFiles()['/files/jsFile.js']['attributes']
        );
    }

    /**
     * @return array
     */
    public function positionProvider2(): array
    {
        return [
            [1, true],
            [1, false],
            [2, true],
            [2, false],
        ];
    }

    /**
     * @dataProvider positionProvider2
     *
     * @param int $pos
     * @param bool $jqAlreadyRegistered
     */
    public function testPositionDependencyConflict(int $pos, bool $jqAlreadyRegistered): void
    {
        $jqAsset = JqueryAsset::class;

        $this->assetManager->setBundles([
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

        if ($jqAlreadyRegistered) {
            $message = "An asset bundle that depends on '$jqAsset' has a higher javascript file " .
            "position configured than '$jqAsset'.";

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage($message);

            $this->assetManager->register([JqueryAsset::class, PositionAsset::class]);
        } else {
            $message = "An asset bundle that depends on '$jqAsset' has a higher javascript file " .
                "position configured than '$jqAsset'.";

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage($message);

            $this->assetManager->register([PositionAsset::class]);
        }
    }

    public function testLoadDummyBundle(): void
    {
        $jqueryBundle = new JqueryAsset();

        $this->assetManager->setBundles(
            [
                JqueryAsset::class => false,
            ]
        );

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([JqueryAsset::class]);

        $this->assertNotSame($jqueryBundle, $this->assetManager->getBundle(JqueryAsset::class));
        $this->assertEmpty($this->assetManager->getCssFiles());
        $this->assertEmpty($this->assetManager->getJsFiles());
    }

    public function testGetAssetBundleException(): void
    {
        $this->assetManager->setBundles(
            [
                JqueryAsset::class => 'noExist',
            ]
        );

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid asset bundle configuration: Yiisoft\Assets\Tests\stubs\JqueryAsset');

        $this->assetManager->register([JqueryAsset::class]);
    }

    public function testGetAssetBundleInstanceOfAssetBundle(): void
    {
        $jqueryBundle = new JqueryAsset();

        $this->assetManager->setBundles(
            [
                JqueryAsset::class => $jqueryBundle,
            ]
        );

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([JqueryAsset::class]);

        $this->assertSame($jqueryBundle, $this->assetManager->getBundle(JqueryAsset::class));
    }
}
