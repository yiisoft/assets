<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\Level3Asset;
use Yiisoft\Assets\Tests\stubs\PositionAsset;

/**
 * AssetPositionTest.
 */
final class AssetPositionTest extends TestCase
{
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
                'jsOptions' =>  [
                    'position' => $pos,
                ],
            ]
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
                'position' => $pos
            ],
            $this->assetManager->getJsFiles()['/js/jquery.js']['attributes']
        );
        $this->assertEquals(
            [
                'position' => $pos
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
                'jsOptions' =>  [
                    'position' => $pos - 1,
                ],
            ],
            JqueryAsset::class => [
                'jsOptions' =>  [
                    'position' => $pos,
                ],
            ]
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
}
