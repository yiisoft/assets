<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests\Support;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function strlen;

/**
 * @psalm-require-extends TestCase
 */
trait AssertionsTrait
{
    protected function assertDirectoryHasExactFiles(array $expectedFiles, string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dir,
                FilesystemIterator::SKIP_DOTS
            )
        );

        $actualFiles = [];

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $actualFiles[] = str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    substr($file->getPathname(), strlen($dir) + 1)
                );
            }
        }

        sort($actualFiles);
        sort($expectedFiles);

        $this->assertSame($expectedFiles, $actualFiles);
    }
}
