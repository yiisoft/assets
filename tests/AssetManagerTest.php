<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use RuntimeException;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Files\FileHelper;

use function crc32;
use function sprintf;

final class AssetManagerTest extends TestCase
{
    public function testGetConverter(): void
    {
        $this->assertInstanceOf(
            AssetConverterInterface::class,
            $this->manager->getConverter()
        );
    }

    public function testGetPublishedPathLinkAssetsFalse(): void
    {
        $bundle = $this->createBundle('source');

        $sourcePath = $this->aliases->get($bundle->sourcePath);

        $path = $sourcePath . FileHelper::lastModifiedTime($sourcePath);
        $path = sprintf('%x', crc32($path . '|' . $this->manager->getPublisher()->getLinkAssets()));

        $this->assertEmpty($this->manager->getAssetBundles());
        $this->manager->register(['source']);

        $this->assertEquals(
            $this->manager->getPublisher()->getPublishedPath($bundle->sourcePath),
            $this->aliases->get("@root/tests/public/assets/{$path}"),
        );
    }

    public function testGetPublishedPathWrong(): void
    {
        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register(['source']);

        $this->assertNull($this->manager->getPublisher()->getPublishedPath('/wrong'));
    }

    public function testGetPublishedUrl(): void
    {
        $bundle = $this->createBundle('source');

        $sourcePath = $this->aliases->get($bundle->sourcePath);

        $path = $sourcePath . FileHelper::lastModifiedTime($sourcePath);
        $path = sprintf('%x', crc32($path . '|' . $this->manager->getPublisher()->getLinkAssets()));

        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register(['source']);

        $this->assertEquals(
            $this->manager->getPublisher()->getPublishedUrl($bundle->sourcePath),
            "/baseUrl/{$path}"
        );
    }

    public function testGetPublishedUrlWrong(): void
    {
        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register(['source']);

        $this->assertNull($this->manager->getPublisher()->getPublishedUrl('/wrong'));
    }

    public function testAssetManagerSetBundles(): void
    {
        $urlJs = 'https://code.jquery.com/jquery-3.4.1.js';
        $jquery = $this->getBundleConfiguration('jquery');
        $jquery['js'] = [
            [
                $urlJs,
                'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
                'crossorigin' => 'anonymous',
            ],
        ];

        $manager = new AssetManager($this->aliases, $this->publisher, ['jquery' => $jquery]);

        $this->assertEmpty($manager->getAssetBundles());

        $manager->register(['jquery']);

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
        $position = $this->getBundleConfiguration('position');
        $position['jsOptions']['position'] = $pos;

        $manager = new AssetManager($this->aliases, $this->publisher, [
            'position' => $position,
            'jquery' => $this->getBundleConfiguration('jquery'),
            'level3' => $this->getBundleConfiguration('level3'),
        ]);

        $this->assertEmpty($manager->getAssetBundles());

        if ($jqAlreadyRegistered) {
            $manager->register(['jquery', 'position']);
        } else {
            $manager->register(['position']);
        }

        $this->assertCount(3, $manager->getAssetBundles());
        $this->assertArrayHasKey('position', $manager->getAssetBundles());
        $this->assertArrayHasKey('jquery', $manager->getAssetBundles());
        $this->assertArrayHasKey('level3', $manager->getAssetBundles());

        $this->assertInstanceOf(AssetBundle::class, $manager->getAssetBundles()['position']);
        $this->assertInstanceOf(AssetBundle::class, $manager->getAssetBundles()['jquery']);
        $this->assertInstanceOf(AssetBundle::class, $manager->getAssetBundles()['level3']);

        $this->assertArrayHasKey('position', $manager->getAssetBundles()['position']->jsOptions);
        $this->assertEquals($pos, $manager->getAssetBundles()['position']->jsOptions['position']);

        $this->assertArrayHasKey('position', $manager->getAssetBundles()['jquery']->jsOptions);
        $this->assertEquals($pos, $manager->getAssetBundles()['jquery']->jsOptions['position']);

        $this->assertArrayHasKey('position', $manager->getAssetBundles()['level3']->jsOptions);
        $this->assertEquals($pos, $manager->getAssetBundles()['level3']->jsOptions['position']);

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
        $position = $this->getBundleConfiguration('position');
        $jquery = $this->getBundleConfiguration('jquery');

        $position['jsOptions']['position'] = $pos - 1;
        $jquery['jsOptions']['position'] = $pos;

        $manager = new AssetManager($this->aliases, $this->publisher, ['position' => $position, 'jquery' => $jquery]);
        $message = 'An asset bundle that depends on "jquery" has a higher'
            . ' javascript file position configured than "jquery".';

        if ($jqAlreadyRegistered) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);

            $manager->register(['jquery', 'position']);
        } else {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);

            $manager->register(['position']);
        }
    }

    public function testLoadDummyBundle(): void
    {
        $jqueryBundle = $this->createBundle('jquery');
        $manager = new AssetManager($this->aliases, $this->publisher, ['jquery' => false]);

        $this->assertEmpty($manager->getAssetBundles());

        $manager->register(['jquery']);

        $this->assertNotSame($jqueryBundle, $manager->getBundle('jquery'));
        $this->assertEmpty($manager->getCssFiles());
        $this->assertEmpty($manager->getJsFiles());
    }

    public function testGetAssetBundleException(): void
    {
        $manager = new AssetManager($this->aliases, $this->publisher, ['jquery' => 'noExist']);

        $this->assertEmpty($manager->getAssetBundles());

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid configuration of the "jquery" asset bundle.');

        $manager->register(['jquery']);
    }

    public function testGetAssetBundleInstanceOfAssetBundle(): void
    {
        $jqueryBundle = $this->createBundle('jquery');
        $manager = new AssetManager($this->aliases, $this->publisher, ['jquery' => $jqueryBundle]);

        $this->assertEmpty($this->manager->getAssetBundles());

        $this->manager->register(['jquery']);

        $this->assertSame($jqueryBundle, $manager->getBundle('jquery'));
    }
}
