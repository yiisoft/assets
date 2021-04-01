<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use RuntimeException;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Assets\Exporter\JsonAssetExporter;
use Yiisoft\Assets\Tests\stubs\CdnAsset;
use Yiisoft\Assets\Tests\stubs\JqueryAsset;
use Yiisoft\Assets\Tests\stubs\Level3Asset;
use Yiisoft\Assets\Tests\stubs\PositionAsset;
use Yiisoft\Assets\Tests\stubs\SourceAsset;
use Yiisoft\Files\FileHelper;

use function crc32;
use function sprintf;
use function ucfirst;

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

        $this->assertEmpty($this->getRegisteredBundles($this->manager));
        $this->manager->register([SourceAsset::class]);

        $this->assertEquals(
            $this->manager->getPublisher()->getPublishedPath($bundle->sourcePath),
            $this->aliases->get("@root/tests/public/assets/{$path}"),
        );
    }

    public function testGetPublishedPathWrong(): void
    {
        $this->assertEmpty($this->getRegisteredBundles($this->manager));

        $this->manager->register([SourceAsset::class]);

        $this->assertNull($this->manager->getPublisher()->getPublishedPath('/wrong'));
    }

    public function testGetPublishedUrl(): void
    {
        $bundle = new SourceAsset();

        $sourcePath = $this->aliases->get($bundle->sourcePath);

        $path = $sourcePath . FileHelper::lastModifiedTime($sourcePath);
        $path = sprintf('%x', crc32($path . '|' . $this->manager->getPublisher()->getLinkAssets()));

        $this->assertEmpty($this->getRegisteredBundles($this->manager));

        $this->manager->register([SourceAsset::class]);

        $this->assertEquals(
            $this->manager->getPublisher()->getPublishedUrl($bundle->sourcePath),
            "/baseUrl/{$path}"
        );
    }

    public function testGetPublishedUrlWrong(): void
    {
        $this->assertEmpty($this->getRegisteredBundles($this->manager));

        $this->manager->register([SourceAsset::class]);

        $this->assertNull($this->manager->getPublisher()->getPublishedUrl('/wrong'));
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

        $this->assertStringContainsString(
            $urlJs,
            $manager->getJsFiles()[$urlJs]['url'],
        );
        $this->assertEquals(
            [
                'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
                'crossorigin' => 'anonymous',
                'position' => 3,
            ],
            $manager->getJsFiles()[$urlJs]['attributes'],
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
                'jsOptions' => [
                    'position' => $pos,
                ],
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

        $this->assertArrayHasKey('position', $this->getRegisteredBundles($manager)[PositionAsset::class]->jsOptions);
        $this->assertEquals($pos, $this->getRegisteredBundles($manager)[PositionAsset::class]->jsOptions['position']);

        $this->assertArrayHasKey('position', $this->getRegisteredBundles($manager)[JqueryAsset::class]->jsOptions);
        $this->assertEquals($pos, $this->getRegisteredBundles($manager)[JqueryAsset::class]->jsOptions['position']);

        $this->assertArrayHasKey('position', $this->getRegisteredBundles($manager)[Level3Asset::class]->jsOptions);
        $this->assertEquals($pos, $this->getRegisteredBundles($manager)[Level3Asset::class]->jsOptions['position']);

        $this->assertEquals(['position' => $pos], $manager->getJsFiles()['/js/jquery.js']['attributes']);
        $this->assertEquals(['position' => $pos], $manager->getJsFiles()['/files/jsFile.js']['attributes']);
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

        $manager = new AssetManager($this->aliases, $this->loader, [], [
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
            . " JavaScript file position configured than \"{$jqAsset}\".";

        if ($jqAlreadyRegistered) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);

            $manager->register([JqueryAsset::class, PositionAsset::class]);
        } else {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);

            $manager->register([PositionAsset::class]);
        }
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

    /**
     * @return array
     */
    public function registerFileDataProvider(): array
    {
        return [
            // Custom alias repeats in the asset URL
            [
                'css', '@assetUrl/assetSources/repeat/css/stub.css', false,
                '/repeat/assetSources/repeat/css/stub.css',
                '/repeat',
            ],
            [
                'js', '@assetUrl/assetSources/repeat/js/jquery.js', false,
                '/repeat/assetSources/repeat/js/jquery.js',
                '/repeat',
            ],
            // JS files registration
            [
                'js', '@assetUrl/assetSources/js/missing-file.js', true,
                '/baseUrl/assetSources/js/missing-file.js',
            ],
            [
                'js', '@assetUrl/assetSources/js/jquery.js', false,
                '/baseUrl/assetSources/js/jquery.js',
            ],
            [
                'js', 'http://example.com/assetSources/js/jquery.js', false,
                'http://example.com/assetSources/js/jquery.js',
            ],
            [
                'js', '//example.com/assetSources/js/jquery.js', false,
                '//example.com/assetSources/js/jquery.js',
            ],
            [
                'js', 'assetSources/js/jquery.js', false,
                'assetSources/js/jquery.js',
            ],
            [
                'js', '/assetSources/js/jquery.js', false,
                '/assetSources/js/jquery.js',
            ],
            // CSS file registration
            [
                'css', '@assetUrl/assetSources/css/missing-file.css', true,
                '/baseUrl/assetSources/css/missing-file.css',
            ],
            [
                'css', '@assetUrl/assetSources/css/stub.css', false,
                '/baseUrl/assetSources/css/stub.css',
            ],
            [
                'css', 'http://example.com/assetSources/css/stub.css', false,
                'http://example.com/assetSources/css/stub.css',
            ],
            [
                'css', '//example.com/assetSources/css/stub.css', false,
                '//example.com/assetSources/css/stub.css',
            ],
            [
                'css', 'assetSources/css/stub.css', false,
                'assetSources/css/stub.css',
            ],
            [
                'css', '/assetSources/css/stub.css', false,
                '/assetSources/css/stub.css',
            ],
            // Custom `@assetUrl` aliases
            [
                'js', '@assetUrl/assetSources/js/missing-file1.js', true,
                '/backend/assetSources/js/missing-file1.js',
                '/backend',
            ],
            [
                'js', 'http://full-url.example.com/backend/assetSources/js/missing-file.js', true,
                'http://full-url.example.com/backend/assetSources/js/missing-file.js',
                '/backend',
            ],
            [
                'css', '//backend/backend/assetSources/js/missing-file.js', true,
                '//backend/backend/assetSources/js/missing-file.js',
                '/backend',
            ],
            [
                'css', '@assetUrl/assetSources/css/stub.css', false,
                '/en/blog/backend/assetSources/css/stub.css',
                '/en/blog/backend',
            ],
            // UTF-8 chars
            [
                'css', '@assetUrl/assetSources/css/stub.css', false,
                '/рус/сайт/assetSources/css/stub.css',
                '/рус/сайт',
            ],
            [
                'js', '@assetUrl/assetSources/js/jquery.js', false,
                '/汉语/漢語/assetSources/js/jquery.js',
                '/汉语/漢語',
            ],
        ];
    }

    /**
     * @dataProvider registerFileDataProvider
     *
     * @param string $type either `js` or `css`
     * @param string $path
     * @param bool $appendTimestamp
     * @param string $expected
     * @param string|null $webAlias
     */
    public function testRegisterFileAppendTimestamp(
        string $type,
        string $path,
        bool $appendTimestamp,
        string $expected,
        ?string $webAlias = null
    ): void {
        $originalAlias = $this->aliases->get('@assetUrl');

        if ($webAlias === null) {
            $webAlias = $originalAlias;
        }

        $this->aliases->set('@assetUrl', $webAlias);
        $path = $this->aliases->get($path);
        $this->loader->setAppendTimestamp($appendTimestamp);
        $this->invokeMethod($this->manager, 'register' . ucfirst($type) . 'File', [$path, [], null]);

        $this->assertStringContainsString(
            $expected,
            $type === 'css' ? $this->manager->getCssFiles()[$expected]['url']
                : $this->manager->getJsFiles()[$expected]['url'],
        );
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
        $manager->setPublisher($this->publisher);
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
        $manager->setPublisher($this->publisher);
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
        $manager->setPublisher($this->publisher);
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
}
