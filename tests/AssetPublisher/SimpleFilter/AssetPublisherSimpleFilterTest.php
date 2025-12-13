<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\AssetPublisher\SimpleFilter;

use PHPUnit\Framework\TestCase;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Assets\Tests\Support\AssertionsTrait;
use Yiisoft\Files\FileHelper;

final class AssetPublisherSimpleFilterTest extends TestCase
{
    use AssertionsTrait;

    private const PUBLIC_DIR = __DIR__ . '/public';

    protected function setUp(): void
    {
        parent::setUp();
        FileHelper::removeDirectory(self::PUBLIC_DIR);
    }

    public function testBase(): void
    {
        $bundle = new Bundle();
        $publisher = $this->createAssetPublisher();

        $publisher->publish($bundle);

        $this->assertDirectoryExists(self::PUBLIC_DIR);
        $this->assertDirectoryHasExactFiles(
            [
                'test/css/bootstrap.css',
                'test/js/bootstrap.js',
            ],
            self::PUBLIC_DIR,
        );
    }

    private function createAssetPublisher(): AssetPublisher
    {
        $aliases = new Aliases([
            '@asset' => self::PUBLIC_DIR,
            '@assetUrl' => '/baseUrl',
        ]);

        return (new AssetPublisher($aliases))
            ->withHashCallback(
                static fn(string $path) => 'test',
            );
    }
}
