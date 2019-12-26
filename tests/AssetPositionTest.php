<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\Level3Asset;
use Yiisoft\Assets\Tests\stubs\PositionAsset;
use Yiisoft\View\WebView;

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
            [WebView::POSITION_HEAD, true],
            [WebView::POSITION_HEAD, false],
            [WebView::POSITION_BEGIN, true],
            [WebView::POSITION_BEGIN, false],
            [WebView::POSITION_END, true],
            [WebView::POSITION_END, false],
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

        $this->webView->setCssFiles($this->assetManager->getCssFiles());
        $this->webView->setJsFiles($this->assetManager->getJsFiles());

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

        switch ($pos) {
            case WebView::POSITION_HEAD:
                $expected = <<<'EOF'
1<link href="/files/cssFile.css" rel="stylesheet">
<script src="/js/jquery.js"></script>
<script src="/files/jsFile.js"></script>234
EOF;
                break;
            case WebView::POSITION_BEGIN:
                $expected = <<<'EOF'
1<link href="/files/cssFile.css" rel="stylesheet">2<script src="/js/jquery.js"></script>
<script src="/files/jsFile.js"></script>34
EOF;
                break;
            case WebView::POSITION_END:
                $expected = <<<'EOF'
1<link href="/files/cssFile.css" rel="stylesheet">23<script src="/js/jquery.js"></script>
<script src="/files/jsFile.js"></script>4
EOF;
                break;
        }
        $this->assertEqualsWithoutLE(
            $expected,
            $this->webView->renderFile($this->aliases->get('@view/rawlayout.php'))
        );
    }

    /**
     * @return array
     */
    public function positionProvider2(): array
    {
        return [
            [WebView::POSITION_BEGIN, true],
            [WebView::POSITION_BEGIN, false],
            [WebView::POSITION_END, true],
            [WebView::POSITION_END, false],
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
