<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use RuntimeException;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetLoader;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Assets\Exporter\JsonAssetExporter;
use Yiisoft\Assets\Tests\stubs\BaseAsset;
use Yiisoft\Assets\Tests\stubs\CdnAsset;
use Yiisoft\Assets\Tests\stubs\CssPositionDependencyConflict\OneAsset;
use Yiisoft\Assets\Tests\stubs\CssPositionDependencyConflict\TwoAsset;
use Yiisoft\Assets\Tests\stubs\ExportAsset;
use Yiisoft\Assets\Tests\stubs\InvalidConfig\CssAsArrayWithEmptyUrlAsset;
use Yiisoft\Assets\Tests\stubs\InvalidConfig\CssAsArrayWithIntegerUrlAsset;
use Yiisoft\Assets\Tests\stubs\InvalidConfig\CssAsArrayWithoutUrlAsset;
use Yiisoft\Assets\Tests\stubs\InvalidConfig\JsAsArrayWithEmptyUrlAsset;
use Yiisoft\Assets\Tests\stubs\InvalidConfig\JsAsArrayWithIntegerUrlAsset;
use Yiisoft\Assets\Tests\stubs\InvalidConfig\JsAsArrayWithoutUrlAsset;
use Yiisoft\Assets\Tests\stubs\InvalidConfig\JsOptionsWithIntegerKeyAsset;
use Yiisoft\Assets\Tests\stubs\InvalidConfig\JsVarAsArrayWithIntegerNameAsset;
use Yiisoft\Assets\Tests\stubs\InvalidConfig\JsVarAsArrayWithoutNameAsset;
use Yiisoft\Assets\Tests\stubs\InvalidConfig\JsVarAsArrayWithoutValueAsset;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\Level3Asset;
use Yiisoft\Assets\Tests\stubs\PositionAsset;
use Yiisoft\Assets\Tests\stubs\PureAsset;
use Yiisoft\Assets\Tests\stubs\RepeatAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;
use Yiisoft\Assets\Tests\stubs\UnicodeAsset;
use Yiisoft\Files\FileHelper;

use function dirname;

final class AssetManagerTest extends TestCase
{
    public function testRegister(): void
    {
        $manager = $this->createManager([
            '@root' => dirname(__DIR__),
            '@assetUrl' => '/',
        ]);

        $manager->register([BaseAsset::class]);

        $this->assertSame([
            '/css/basePath.css' => [
                '/css/basePath.css',
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
            ],
            '/css/main.css' => [
                '/css/main.css',
                1,
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
            ],
        ], $manager->getCssFiles());
        $this->assertSame([
            ['a { color: red; }', 'integrity' => 'integrity-hash', 'crossorigin' => 'anonymous'],
            ['a { color: red; }', 'integrity' => 'integrity-hash', 'crossorigin' => 'anonymous'],
            ['a { color: red; }', 3, 'integrity' => 'integrity-hash', 'crossorigin' => 'anonymous'],
            ['a { color: red; }', 3, 'crossorigin' => 'any', 'integrity' => 'integrity-hash',],
            'key1' => ['a { color: red; }', 'integrity' => 'integrity-hash', 'crossorigin' => 'anonymous'],
            'key2' => ['a { color: red; }', 'integrity' => 'integrity-hash', 'crossorigin' => 'anonymous'],
            'key3' => ['a { color: red; }', 3, 'integrity' => 'integrity-hash', 'crossorigin' => 'anonymous'],
            'key4' => ['a { color: red; }', 3, 'crossorigin' => 'any', 'integrity' => 'integrity-hash'],
        ], $manager->getCssStrings());
        $this->assertSame([
            '/js/basePath.js' => [
                '/js/basePath.js',
                'data-test' => 'one',
            ],
            '/js/main.js' => [
                '/js/main.js',
                1,
                'data-test' => 'one',
            ],
        ], $manager->getJsFiles());
        $this->assertSame([
            'uniqueName' => ['app1.start();', 'data-test' => 'one'],
            ['app2.start();', 'data-test' => 'one'],
            'uniqueName2' => ['app3.start();', 3, 'data-test' => 'one'],
            ['app4.start();', 3, 'data-test' => 'one'],
        ], $manager->getJsStrings());
        $this->assertSame([
            ['var1', 'value1'],
            ['var2', [1, 2]],
            ['var3', 'value3', 3],
        ], $manager->getJsVars());
    }

    public function testRegisterWithCustomPosition(): void
    {
        $manager = $this->createManager([
            '@root' => dirname(__DIR__),
            '@assetUrl' => '/',
        ]);

        $manager->register([BaseAsset::class], 7, 42);

        $this->assertSame([
            '/css/basePath.css' => [
                '/css/basePath.css',
                42,
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
            ],
            '/css/main.css' => [
                '/css/main.css',
                1,
                'integrity' => 'integrity-hash',
                'crossorigin' => 'anonymous',
            ],
        ], $manager->getCssFiles());
        $this->assertSame([
            ['a { color: red; }', 42, 'integrity' => 'integrity-hash', 'crossorigin' => 'anonymous'],
            ['a { color: red; }', 42, 'integrity' => 'integrity-hash', 'crossorigin' => 'anonymous'],
            ['a { color: red; }', 3, 'integrity' => 'integrity-hash', 'crossorigin' => 'anonymous'],
            ['a { color: red; }', 3, 'crossorigin' => 'any', 'integrity' => 'integrity-hash',],
            'key1' => ['a { color: red; }', 42, 'integrity' => 'integrity-hash', 'crossorigin' => 'anonymous'],
            'key2' => ['a { color: red; }', 42, 'integrity' => 'integrity-hash', 'crossorigin' => 'anonymous'],
            'key3' => ['a { color: red; }', 3, 'integrity' => 'integrity-hash', 'crossorigin' => 'anonymous'],
            'key4' => ['a { color: red; }', 3, 'crossorigin' => 'any', 'integrity' => 'integrity-hash'],
        ], $manager->getCssStrings());
        $this->assertSame([
            '/js/basePath.js' => [
                '/js/basePath.js',
                7,
                'data-test' => 'one',
            ],
            '/js/main.js' => [
                '/js/main.js',
                1,
                'data-test' => 'one',
            ],
        ], $manager->getJsFiles());
        $this->assertSame([
            'uniqueName' => ['app1.start();', 7, 'data-test' => 'one'],
            ['app2.start();', 7, 'data-test' => 'one'],
            'uniqueName2' => ['app3.start();', 3, 'data-test' => 'one'],
            ['app4.start();', 3, 'data-test' => 'one'],
        ], $manager->getJsStrings());
        $this->assertSame([
            ['var1', 'value1', 7],
            ['var2', [1, 2], 7],
            ['var3', 'value3', 3],
        ], $manager->getJsVars());
    }

    public function testGetPublishedPathLinkAssetsFalse(): void
    {
        $bundle = new SourceAsset();

        $sourcePath = $this->aliases->get($bundle->sourcePath);
        $hash = $this->getPublishedHash($sourcePath . FileHelper::lastModifiedTime($sourcePath), $this->publisher);

        $this->assertEmpty($this->getRegisteredBundles($this->manager));
        $this->manager->register([SourceAsset::class]);

        $this->assertEquals(
            $this->publisher->getPublishedPath($bundle->sourcePath),
            $this->aliases->get("@root/tests/public/assets/{$hash}"),
        );
    }

    public function testGetPublishedPathWrong(): void
    {
        $this->assertEmpty($this->getRegisteredBundles($this->manager));

        $this->manager->register([SourceAsset::class]);

        $this->assertNull($this->publisher->getPublishedPath('/wrong'));
    }

    public function testGetPublishedUrl(): void
    {
        $bundle = new SourceAsset();

        $sourcePath = $this->aliases->get($bundle->sourcePath);
        $hash = $this->getPublishedHash($sourcePath . FileHelper::lastModifiedTime($sourcePath), $this->publisher);

        $this->assertEmpty($this->getRegisteredBundles($this->manager));

        $this->manager->register([SourceAsset::class]);

        $this->assertEquals($this->publisher->getPublishedUrl($bundle->sourcePath), "/baseUrl/{$hash}");
    }

    public function testGetPublishedUrlWrong(): void
    {
        $this->assertEmpty($this->getRegisteredBundles($this->manager));

        $this->manager->register([SourceAsset::class]);

        $this->assertNull($this->publisher->getPublishedUrl('/wrong'));
    }

    public function testAssetManagerWithCustomizedBundles(): void
    {
        $urlJs = 'https://code.jquery.com/jquery-3.4.1.js';
        $manager = new AssetManager($this->aliases, $this->loader, [], [
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
        ]);

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $manager->register([JqueryAsset::class]);

        $this->assertSame(
            [
                $urlJs,
                'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
                'crossorigin' => 'anonymous',
            ],
            $manager->getJsFiles()[$urlJs],
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
        $manager = new AssetManager($this->aliases, $this->loader, [], [
            PositionAsset::class => [
                'jsPosition' => $pos,
            ],
        ]);

        $this->assertEmpty($this->getRegisteredBundles($manager));

        if ($jqAlreadyRegistered) {
            $manager->register([JqueryAsset::class, PositionAsset::class]);
        } else {
            $manager->register([PositionAsset::class]);
        }

        $this->assertCount(3, $this->getRegisteredBundles($manager));
        $this->assertArrayHasKey(PositionAsset::class, $this->getRegisteredBundles($manager));
        $this->assertArrayHasKey(JqueryAsset::class, $this->getRegisteredBundles($manager));
        $this->assertArrayHasKey(Level3Asset::class, $this->getRegisteredBundles($manager));

        $this->assertInstanceOf(AssetBundle::class, $this->getRegisteredBundles($manager)[PositionAsset::class]);
        $this->assertInstanceOf(AssetBundle::class, $this->getRegisteredBundles($manager)[JqueryAsset::class]);
        $this->assertInstanceOf(AssetBundle::class, $this->getRegisteredBundles($manager)[Level3Asset::class]);

        $this->assertEquals($pos, $this->getRegisteredBundles($manager)[PositionAsset::class]->jsPosition);
        $this->assertEquals($pos, $this->getRegisteredBundles($manager)[JqueryAsset::class]->jsPosition);
        $this->assertEquals($pos, $this->getRegisteredBundles($manager)[Level3Asset::class]->jsPosition);

        $this->assertEquals($pos, $manager->getJsFiles()['/js/jquery.js'][1]);
        $this->assertEquals($pos, $manager->getJsFiles()['/files/jsFile.js'][1]);
    }

    public function dataJsPositionDependencyConflict(): array
    {
        return [
            [1, true],
            [1, false],
            [2, true],
            [2, false],
        ];
    }

    /**
     * @dataProvider dataJsPositionDependencyConflict
     */
    public function testJsPositionDependencyConflict(int $pos, bool $jQueryAlreadyRegistered): void
    {
        $jQueryAsset = JqueryAsset::class;

        $manager = new AssetManager($this->aliases, $this->loader, [], [
            PositionAsset::class => [
                'jsPosition' => $pos - 1,
            ],
            JqueryAsset::class => [
                'jsPosition' => $pos,
            ],
        ]);

        $message = "An asset bundle that depends on \"{$jQueryAsset}\" has a higher"
            . " JavaScript file position configured than \"{$jQueryAsset}\".";


        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        $jQueryAlreadyRegistered
            ? $manager->register([JqueryAsset::class, PositionAsset::class])
            : $manager->register([PositionAsset::class]);
    }

    public function dataCssPositionDependencyConflict(): array
    {
        return [
            [1, true],
            [1, false],
            [2, true],
            [2, false],
        ];
    }

    /**
     * @dataProvider dataCssPositionDependencyConflict
     */
    public function testCssPositionDependencyConflict(int $pos, bool $oneAlreadyRegistered): void
    {
        $oneAsset = OneAsset::class;

        $manager = new AssetManager($this->aliases, $this->loader, [], [
            TwoAsset::class => [
                'cssPosition' => $pos - 1,
            ],
            OneAsset::class => [
                'cssPosition' => $pos,
            ],
        ]);

        $message = "An asset bundle that depends on \"{$oneAsset}\" has a higher"
            . " CSS file position configured than \"{$oneAsset}\".";


        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        $oneAlreadyRegistered
            ? $manager->register([OneAsset::class, TwoAsset::class])
            : $manager->register([TwoAsset::class]);
    }

    public function testLoadDummyBundle(): void
    {
        $jqueryBundle = new JqueryAsset();

        $manager = new AssetManager($this->aliases, $this->loader, [], [
            JqueryAsset::class => false,
        ]);

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $manager->register([JqueryAsset::class]);

        $this->assertNotSame($jqueryBundle, $manager->getBundle(JqueryAsset::class));
        $this->assertEmpty($manager->getCssFiles());
        $this->assertEmpty($manager->getJsFiles());
    }

    public function testGetAssetBundleException(): void
    {
        $jqueryBundle = JqueryAsset::class;

        $manager = new AssetManager($this->aliases, $this->loader, [], [
            JqueryAsset::class => 'noExist',
        ]);

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("Invalid configuration of the \"{$jqueryBundle}\" asset bundle.");

        $manager->register([JqueryAsset::class]);
    }

    public function testGetAssetBundleInstanceOfAssetBundle(): void
    {
        $jqueryBundle = new JqueryAsset();

        $manager = new AssetManager($this->aliases, $this->loader, [], [
            JqueryAsset::class => $jqueryBundle,
        ]);

        $this->assertEmpty($this->getRegisteredBundles($manager));

        $manager->register([JqueryAsset::class]);

        $bundle = $manager->getBundle(JqueryAsset::class);

        $this->assertNotSame($jqueryBundle, $bundle);
        $this->assertEquals($jqueryBundle, $bundle);
        $this->assertNotSame($bundle, $manager->getBundle(JqueryAsset::class));
    }

    public function testRegisterWithAllowedBundlesWithCustomizedBundles(): void
    {
        $manager = new AssetManager($this->aliases, $this->loader, [CdnAsset::class], [
            CdnAsset::class => [
                'js' => [],
            ],
        ]);
        $manager->register([CdnAsset::class]);

        $this->assertTrue($manager->isRegisteredBundle(CdnAsset::class));
        $this->assertCount(1, $this->getRegisteredBundles($manager));
        $this->assertEmpty($manager->getBundle(CdnAsset::class)->depends);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "' . PositionAsset::class . '" asset bundle is not allowed.');

        $manager->register([PositionAsset::class]);
    }

    public function testRegisterWithAllowedBundlesWithDependencies(): void
    {
        $manager = new AssetManager($this->aliases, $this->loader, [JqueryAsset::class]);
        $manager = $manager->withPublisher($this->publisher);
        $manager->register([JqueryAsset::class]);

        $this->assertTrue($manager->isRegisteredBundle(JqueryAsset::class));
        $this->assertTrue($manager->isRegisteredBundle(Level3Asset::class));

        $this->assertInstanceOf(JqueryAsset::class, $manager->getBundle(JqueryAsset::class));
        $this->assertInstanceOf(Level3Asset::class, $manager->getBundle(Level3Asset::class));

        $this->assertCount(2, $this->getRegisteredBundles($manager));
        $this->assertFalse($manager->isRegisteredBundle(PositionAsset::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "' . PositionAsset::class . '" asset bundle is not allowed.');

        $manager->getBundle(PositionAsset::class);
    }

    public function testRegisterParentWithAllowedBundlesWithDependencies(): void
    {
        $manager = new AssetManager($this->aliases, $this->loader, [PositionAsset::class]);
        $manager = $manager->withPublisher($this->publisher);
        $manager->register([JqueryAsset::class]);

        $this->assertFalse($manager->isRegisteredBundle(PositionAsset::class));
        $this->assertTrue($manager->isRegisteredBundle(JqueryAsset::class));
        $this->assertTrue($manager->isRegisteredBundle(Level3Asset::class));

        $this->assertCount(2, $this->getRegisteredBundles($manager));
        $this->assertInstanceOf(JqueryAsset::class, $manager->getBundle(JqueryAsset::class));
        $this->assertInstanceOf(Level3Asset::class, $manager->getBundle(Level3Asset::class));

        $manager->register([PositionAsset::class]);

        $this->assertCount(3, $this->getRegisteredBundles($manager));
        $this->assertTrue($manager->isRegisteredBundle(PositionAsset::class));
        $this->assertInstanceOf(PositionAsset::class, $manager->getBundle(PositionAsset::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "' . CdnAsset::class . '" asset bundle is not allowed.');

        $manager->getBundle(CdnAsset::class);
    }

    public function testRegisterAllAllowedBundlesWithDependencies(): void
    {
        $manager = new AssetManager($this->aliases, $this->loader, [PositionAsset::class]);
        $manager = $manager->withPublisher($this->publisher);
        $manager->registerAllAllowed();

        $this->assertTrue($manager->isRegisteredBundle(PositionAsset::class));
        $this->assertTrue($manager->isRegisteredBundle(JqueryAsset::class));
        $this->assertTrue($manager->isRegisteredBundle(Level3Asset::class));

        $this->assertCount(3, $this->getRegisteredBundles($manager));
        $this->assertInstanceOf(JqueryAsset::class, $manager->getBundle(JqueryAsset::class));
        $this->assertInstanceOf(Level3Asset::class, $manager->getBundle(Level3Asset::class));
    }

    public function testRegisterAllAllowedWithoutSpecifiedAllowedBundles(): void
    {
        $manager = new AssetManager($this->aliases, $this->loader);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The allowed names of the asset bundles were not set.');

        $manager->registerAllAllowed();
    }

    public function testExportWithoutRegisterWithoutAllowedBundles(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not a single asset bundle was registered.');

        $this->manager->export(new JsonAssetExporter($this->aliases->get('@asset/test.json')));
    }

    public function testGetAssetUrl(): void
    {
        $bundle = new ExportAsset();
        $sourcePath = $this->aliases->get($bundle->sourcePath);
        $hash = $this->getPublishedHash($sourcePath . FileHelper::lastModifiedTime($sourcePath), $this->publisher);

        $this->assertSame(
            $this->aliases->get("@assetUrl/{$hash}/css/stub.css"),
            $this->manager->getAssetUrl(ExportAsset::class, 'css/stub.css'),
        );
        $this->assertSame(
            $this->aliases->get("@assetUrl/{$hash}/js/stub.js"),
            $this->manager->getAssetUrl(ExportAsset::class, 'js/stub.js'),
        );
        $this->assertSame(
            $this->aliases->get("@assetUrl/{$hash}/export/stub.css"),
            $this->manager->getAssetUrl(ExportAsset::class, 'export/stub.css'),
        );
        $this->assertSame(
            $this->aliases->get("@assetUrl/{$hash}/export/stub.js"),
            $this->manager->getAssetUrl(ExportAsset::class, 'export/stub.js'),
        );
        $this->assertSame(
            $this->aliases->get("@assetUrl/{$hash}/export/yii-logo.png"),
            $this->manager->getAssetUrl(ExportAsset::class, 'export/yii-logo.png'),
        );
    }

    public function testRegisterWithCustomBaseUrl(): void
    {
        $manager = $this->createManagerWithAliasesAndBaseUrl(['@web' => 'https://example.com/assets/'], '@web');

        $manager->register([PureAsset::class]);

        $this->assertSame(
            ['https://example.com/assets/pure/main.css' => ['https://example.com/assets/pure/main.css']],
            $manager->getCssFiles(),
        );
        $this->assertSame(
            ['https://example.com/assets/pure/main.js' => ['https://example.com/assets/pure/main.js']],
            $manager->getJsFiles(),
        );
    }

    public function testRepeatsAliasInAssetUrl(): void
    {
        $manager = $this->createManagerWithAliasesAndBaseUrl(['@web' => '/repeat'], '@web');

        $manager->register([RepeatAsset::class]);

        $this->assertSame(
            ['/repeat/repeat/assets/repeat/main.css' => ['/repeat/repeat/assets/repeat/main.css']],
            $manager->getCssFiles(),
        );
        $this->assertSame(
            ['/repeat/repeat/assets/repeat/main.js' => ['/repeat/repeat/assets/repeat/main.js']],
            $manager->getJsFiles(),
        );
    }

    public function testUnicodeInPath(): void
    {
        $manager = $this->createManager();

        $manager->register([UnicodeAsset::class]);

        $this->assertSame(
            ['/unicode/русский/main.css' => ['/unicode/русский/main.css']],
            $manager->getCssFiles(),
        );
        $this->assertSame(
            ['/unicode/汉语漢語/main.js' => ['/unicode/汉语漢語/main.js']],
            $manager->getJsFiles(),
        );
    }

    public function testSettersImmutability(): void
    {
        $manager = $this->manager->withConverter($this->converter);
        $this->assertInstanceOf(AssetManager::class, $manager);
        $this->assertNotSame($this->manager, $manager);

        $manager = $this->manager->withLoader($this->loader);
        $this->assertInstanceOf(AssetManager::class, $manager);
        $this->assertNotSame($this->manager, $manager);

        $manager = $this->manager->withPublisher($this->publisher);
        $this->assertInstanceOf(AssetManager::class, $manager);
        $this->assertNotSame($this->manager, $manager);
    }

    public function testAppendTimestamp(): void
    {
        $manager = $this->createManagerWithAppendTimestamp();

        $manager->register([PureAsset::class]);

        $this->assertMatchesRegularExpression(
            '~/pure/main\.css\?v=\d+~',
            array_key_first($manager->getCssFiles()),
        );

        $this->assertMatchesRegularExpression(
            '~/pure/main\.js\?v=\d+~',
            array_key_first($manager->getJsFiles()),
        );
    }

    public function testCssAsArrayWithoutUrl(): void
    {
        $manager = $this->createManager();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Do not set in array CSS URL.');
        $manager->register([CssAsArrayWithoutUrlAsset::class]);
    }

    public function testCssAsArrayWithIntegerUrl(): void
    {
        $manager = $this->createManager();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('CSS file should be string. Got integer.');
        $manager->register([CssAsArrayWithIntegerUrlAsset::class]);
    }

    public function testCssAsArrayWithEmptyUrl(): void
    {
        $manager = $this->createManager();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('CSS file should be non empty string.');
        $manager->register([CssAsArrayWithEmptyUrlAsset::class]);
    }

    public function testJsAsArrayWithoutUrl(): void
    {
        $manager = $this->createManager();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Do not set in array JS URL.');
        $manager->register([JsAsArrayWithoutUrlAsset::class]);
    }

    public function testJsAsArrayWithIntegerUrl(): void
    {
        $manager = $this->createManager();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('JS file should be string. Got integer.');
        $manager->register([JsAsArrayWithIntegerUrlAsset::class]);
    }

    public function testJsAsArrayWithEmptyUrl(): void
    {
        $manager = $this->createManager();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('JS file should be non empty string.');
        $manager->register([JsAsArrayWithEmptyUrlAsset::class]);
    }

    public function testJsVarAsArrayWithoutName(): void
    {
        $manager = $this->createManager();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Do not set JavaScript variable name.');
        $manager->register([JsVarAsArrayWithoutNameAsset::class]);
    }

    public function testsVarAsArrayWithIntegerName(): void
    {
        $manager = $this->createManager();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('JavaScript variable name should be string. Got integer.');
        $manager->register([JsVarAsArrayWithIntegerNameAsset::class]);
    }

    public function testJsOptionsWithIntegerKey(): void
    {
        $manager = $this->createManager();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'JavaScript or CSS options should be list of key/value pairs with string ketys. Got integer key.'
        );
        $manager->register([JsOptionsWithIntegerKeyAsset::class]);
    }

    public function testJsVarAsArrayWithoutValue(): void
    {
        $manager = $this->createManager();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Do not set JavaScript variable value.');
        $manager->register([JsVarAsArrayWithoutValueAsset::class]);
    }

    private function createManager(array $aliases = []): AssetManager
    {
        $aliases = new Aliases($aliases);
        return new AssetManager(
            $aliases,
            new AssetLoader($aliases, false, [], __DIR__ . '/public', ''),
        );
    }

    private function createManagerWithAppendTimestamp(): AssetManager
    {
        $aliases = new Aliases();
        return new AssetManager(
            $aliases,
            new AssetLoader($aliases, true, [], __DIR__ . '/public', ''),
        );
    }

    private function createManagerWithAliasesAndBaseUrl(array $aliases, string $baseUrl): AssetManager
    {
        $aliases = new Aliases($aliases);
        return new AssetManager(
            $aliases,
            new AssetLoader($aliases, false, [], __DIR__ . '/public', $baseUrl),
        );
    }
}
