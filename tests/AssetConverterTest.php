<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

use Yiisoft\Assets\AssetConverterInterface;
use Yiisoft\Files\FileHelper;

use function file_get_contents;
use function file_put_contents;
use function time;
use function touch;
use function usleep;

final class AssetConverterTest extends TestCase
{
    /**
     * @var string Temporary files path.
     */
    private string $tmpPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpPath = $this->aliases->get('@converter');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@converter');
    }

    public function testConvert(): void
    {
        file_put_contents(
            $this->tmpPath . '/test.php',
            <<<EOF
            <?php

            echo "Hello World!\n";
            echo "Hello Yii!";
            EOF,
        );

        $converter = $this->converter->withCommand('php', 'txt', 'php {from} > {to}');

        $this->assertEquals('test.txt', $converter->convert('test.php', $this->tmpPath));
        $this->assertFileExists($this->tmpPath . '/test.txt', 'Failed asserting that asset output file exists.');
        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', "Hello World!\nHello Yii!");
    }

    /**
     * @depends testConvert
     */
    public function testConvertOutdated(): void
    {
        $srcFilename = $this->tmpPath . '/test.php';
        file_put_contents(
            $srcFilename,
            <<<'EOF'
            <?php

            echo microtime();
            EOF,
        );

        $converter = $this->converter->withCommand('php', 'txt', 'php {from} > {to}');
        $converter->convert('test.php', $this->tmpPath);
        $initialConvertTime = file_get_contents($this->tmpPath . '/test.txt');

        usleep(1);
        $converter->convert('test.php', $this->tmpPath);
        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', $initialConvertTime);

        touch($srcFilename, time() + 1000);
        $converter->convert('test.php', $this->tmpPath);
        $this->assertNotEquals($initialConvertTime, file_get_contents($this->tmpPath . '/test.txt'));
    }

    /**
     * @depends testConvertOutdated
     */
    public function testForceConvert(): void
    {
        file_put_contents(
            $this->tmpPath . '/test.php',
            <<<'EOF'
            <?php

            echo microtime();
            EOF,
        );

        $converter = $this->converter->withCommand('php', 'txt', 'php {from} > {to}');

        $converter->convert('test.php', $this->tmpPath);
        $initialConvertTime = file_get_contents($this->tmpPath . '/test.txt');

        usleep(1);
        $this->converter->convert('test.php', $this->tmpPath);

        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', $initialConvertTime);

        $converter = $converter->withForceConvert(true);
        $converter->convert('test.php', $this->tmpPath);

        $this->assertNotEquals($initialConvertTime, file_get_contents($this->tmpPath . '/test.txt'));
    }

    /**
     * @depends testConvertOutdated
     */
    public function testCheckOutdatedCallback(): void
    {
        file_put_contents(
            $this->tmpPath . '/test.php',
            <<<'EOF'
            <?php

            echo microtime();
            EOF,
        );

        $converter = $this->converter->withCommand('php', 'txt', 'php {from} > {to}');

        $converter->convert('test.php', $this->tmpPath);
        $initialConvertTime = file_get_contents($this->tmpPath . '/test.txt');

        $converter = $converter->withIsOutdatedCallback(static fn () => false);

        $converter->convert('test.php', $this->tmpPath);
        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', $initialConvertTime);

        $converter = $converter->withIsOutdatedCallback(static fn () => true);

        $converter->convert('test.php', $this->tmpPath);
        $this->assertNotEquals($initialConvertTime, file_get_contents($this->tmpPath . '/test.txt'));
    }

    public function testConvertWithOptions(): void
    {
        $converter = $this->converter->withCommand('scss', 'css', 'php {options} {from} > {to}');

        $converter->convert(
            'custom.scss',
            $this->aliases->get('@root/tests/public/sass'),
            [
                'scss' => [
                    'command' => '-r',
                    'path' => '@sourcePath/sass',
                ],
            ]
        );

        $this->assertFileExists($this->aliases->get('@root/tests/public/sass/custom.css'));

        FileHelper::unlink($this->aliases->get('@root/tests/public/sass/custom.css'));
    }

    public function testNotExistsConverter(): void
    {
        $converter = $this->converter->withCommand('scss', 'css', 'not-exist/sass');

        $converter->convert(
            'custom.scss',
            $this->aliases->get('@root/tests/public/sass'),
        );

        $this->assertFileDoesNotExist($this->aliases->get('@root/tests/public/sass/custom.css'));
    }

    public function testSettersImmutability(): void
    {
        $converter = $this->converter->withCommand('scss', 'css', 'php {options} {from} > {to}');
        $this->assertInstanceOf(AssetConverterInterface::class, $converter);
        $this->assertNotSame($this->converter, $converter);

        $converter = $this->converter->withForceConvert(false);
        $this->assertInstanceOf(AssetConverterInterface::class, $converter);
        $this->assertNotSame($this->converter, $converter);

        $converter = $this->converter->withIsOutdatedCallback(static fn () => false);
        $this->assertInstanceOf(AssetConverterInterface::class, $converter);
        $this->assertNotSame($this->converter, $converter);
    }
}
