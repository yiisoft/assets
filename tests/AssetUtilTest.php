<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use RuntimeException;
use Yiisoft\Assets\AssetUtil;

final class AssetUtilTest extends TestCase
{
    /**
     * @var string Temporary files path.
     */
    private string $tmpPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpPath = $this->aliases->get('@util');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@util');
    }

    public function testFailExportToFile(): void
    {
        $targetFile = $this->tmpPath . '/export.txt';
        fclose(fopen($targetFile, 'w'));
        chmod($targetFile, 0000);
        error_reporting(0);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An error occurred while writing to the "' . $targetFile . '" file.');
        AssetUtil::exportToFile($targetFile, '');
    }
}
