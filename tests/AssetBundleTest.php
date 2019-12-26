<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Assets\Tests\TestCase;
use Yiisoft\Assets\Tests\stubs\BaseAsset;
use Yiisoft\Assets\Tests\stubs\BaseWrongAsset;
use Yiisoft\Assets\Tests\stubs\CircleAsset;
use Yiisoft\Assets\Tests\stubs\CircleDependsAsset;
use Yiisoft\Assets\Tests\stubs\FileOptionsAsset;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\SimpleAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;

/**
 * AssetBundleTest.
 */
final class AssetBundleTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@basePath');
    }

    public function testBaseAppendtimestamp(): void
    {
        $bundle = new BaseAsset();

        $timestampCss = @filemtime($this->aliases->get($bundle->basePath) . '/' . $bundle->css[0]);
        $timestampJs = @filemtime($this->aliases->get($bundle->basePath) . '/' . $bundle->js[0]);

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->setAppendTimestamp(true);
        $this->assetManager->register([BaseAsset::class]);

        $this->webView->setCssFiles($this->assetManager->getCssFiles());
        $this->webView->setJsFiles($this->assetManager->getJsFiles());

        $expected = <<<EOT
1<link href="/baseUrl/css/basePath.css?v=$timestampCss" rel="stylesheet" integrity="integrity-hash" crossorigin="anonymous">23<script src="/baseUrl/js/basePath.js?v=$timestampJs" integrity="integrity-hash" crossorigin="anonymous"></script>4
EOT;

        $this->assertEquals(
            $expected,
            $this->webView->renderFile($this->aliases->get('@view/rawlayout.php'))
        );
    }

    public function testBasePath(): void
    {
        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([BaseAsset::class]);

        $expected = <<<'EOF'
1<link href="/baseUrl/css/basePath.css" rel="stylesheet" integrity="integrity-hash" crossorigin="anonymous">23<script src="/baseUrl/js/basePath.js" integrity="integrity-hash" crossorigin="anonymous"></script>4
EOF;

        $this->webView->setCssFiles($this->assetManager->getCssFiles());
        $this->webView->setJsFiles($this->assetManager->getJsFiles());

        $this->assertEquals(
            $expected,
            $this->webView->renderFile($this->aliases->get('@view/rawlayout.php'))
        );
    }

    public function testBasePathEmptyException(): void
    {
        $this->assetManager->setBundles(
            [
                BaseAsset::class => [
                    'basePath' => null,
                ]
            ]
        );

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $message = 'basePath must be set in AssetManager->setBasePath($path) or ' .
            'AssetBundle property public ?string $basePath = $path';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->assetManager->register([BaseAsset::class]);
    }

    public function testBaseUrlEmptyException(): void
    {
        $this->assetManager->setBundles(
            [
                BaseAsset::class => [
                    'basePath' => null,
                    'baseUrl' => null,
                ]
            ]
        );

        $this->assetManager->setBasePath('@basePath');

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $message = 'baseUrl must be set in AssetManager->setBaseUrl($path) or ' .
            'AssetBundle property public ?string $baseUrl = $path';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->assetManager->register([BaseAsset::class]);
    }

    public function testBasePathEmptyWithAssetManagerSetBasePath(): void
    {
        $config = [
            'basePath' => null,
        ];

        $this->assetManager->setBasePath('@basePath');

        $this->assertEmpty($this->assetManager->getAssetBundles());
        $this->assertIsObject($this->assetManager->getBundle(BaseAsset::class, $config, false));
    }

    public function testBasePathEmptyBaseUrlEmptyWithAssetManagerSetBasePathSetBaseUrl(): void
    {
        $config = [
            'basePath' => null,
            'baseUrl' => null,
        ];

        $this->assetManager->setBasePath('@basePath');
        $this->assetManager->setBaseUrl('@baseUrl');

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assertIsObject($this->assetManager->getBundle(BaseAsset::class, $config, false));
    }

    public function testBasePathWrongException(): void
    {
        $bundle = new BaseWrongAsset();

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $file = $bundle->js[0];
        $message = "Asset files not found: '$bundle->basePath/$file.'";

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->assetManager->register([BaseWrongAsset::class]);
    }

    public function testCircularDependency(): void
    {
        $depends = (new CircleDependsAsset())->depends;

        $message = "A circular dependency is detected for bundle '$depends[0]'.";


        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->assetManager->register([CircleAsset::class]);
    }

    public function testDuplicateAssetFile(): void
    {
        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([JqueryAsset::class, SimpleAsset::class]);

        $this->webView->setCssFiles($this->assetManager->getCssFiles());
        $this->webView->setJsFiles($this->assetManager->getJsFiles());

        $this->assertCount(3, $this->assetManager->getAssetBundles());
        $this->assertArrayHasKey(SimpleAsset::class, $this->assetManager->getAssetBundles());
        $this->assertInstanceOf(
            AssetBundle::class,
            $this->assetManager->getAssetBundles()[SimpleAsset::class]
        );

        $expected = <<<'EOF'
123<script src="/js/jquery.js"></script>4
EOF;
        $this->assertEquals(
            $expected,
            $this->webView->renderFile($this->aliases->get('@view/rawlayout.php'))
        );
    }

    public function testFileOptionsAsset(): void
    {
        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([FileOptionsAsset::class]);

        $this->webView->setCssFiles($this->assetManager->getCssFiles());
        $this->webView->setJsFiles($this->assetManager->getJsFiles());

        $expected = <<<'EOF'
1<link href="/baseUrl/css/default_options.css" rel="stylesheet" media="screen" hreflang="en">
<link href="/baseUrl/css/tv.css" rel="stylesheet" media="tv" hreflang="en">
<link href="/baseUrl/css/screen_and_print.css" rel="stylesheet" media="screen, print" hreflang="en">23<script src="/baseUrl/js/normal.js" charset="utf-8"></script>
<script src="/baseUrl/js/defered.js" charset="utf-8" defer></script>4
EOF;

        $this->assertEqualsWithoutLE(
            $expected,
            $this->webView->renderFile($this->aliases->get('@view/rawlayout.php'))
        );
    }


    public function testSourceSetHashCallback(): void
    {
        $this->assetManager->setHashCallback(function () {
            return 'HashCallback';
        });

        $this->assertEmpty($this->assetManager->getAssetBundles());

        $this->assetManager->register([SourceAsset::class]);

        $this->webView->setCssFiles($this->assetManager->getCssFiles());
        $this->webView->setJsFiles($this->assetManager->getJsFiles());

        $expected = <<<'EOF'
1<link href="/baseUrl/HashCallback/css/stub.css" rel="stylesheet">23<script src="/js/jquery.js"></script>
<script src="/baseUrl/HashCallback/js/stub.js"></script>4
EOF;

        $this->assertEqualsWithoutLE(
            $expected,
            $this->webView->renderFile($this->aliases->get('@view/rawlayout.php'))
        );
    }

    public function testSourcesPublishOptionsOnlyRegex(): void
    {
        $bundle = new SourceAsset();

        $bundle->publishOptions = [
            'only' => [
                'js/*'
            ],
        ];

        [$bundle->basePath, $bundle->baseUrl] = $this->assetManager->getPublish()->publish($bundle);

        $notNeededFilesDir = dirname($bundle->basePath . DIRECTORY_SEPARATOR . $bundle->css[0]);

        $this->assertFileNotExists($notNeededFilesDir);

        foreach ($bundle->js as $filename) {
            $publishedFile = $bundle->basePath . DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($publishedFile);
        }

        $this->assertDirectoryExists(dirname($bundle->basePath . DIRECTORY_SEPARATOR . $bundle->js[0]));
        $this->assertDirectoryExists($bundle->basePath);
    }

    public function testSourcesPathException(): void
    {
        $bundle = new SourceAsset();

        $bundle->sourcePath = '/wrong';

        $message = "The sourcePath to be published does not exist: $bundle->sourcePath";

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);

        $this->assetManager->getPublish()->publish($bundle);
    }
}
