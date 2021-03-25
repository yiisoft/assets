<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

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
            EOF
        );

        $this->converter->setCommand('php', 'txt', 'php {from} > {to}');

        $this->assertEquals('test.txt', $this->converter->convert('test.php', $this->tmpPath));
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
            EOF
        );

        $this->converter->setCommand('php', 'txt', 'php {from} > {to}');
        $this->converter->convert('test.php', $this->tmpPath);
        $initialConvertTime = file_get_contents($this->tmpPath . '/test.txt');

        usleep(1);
        $this->converter->convert('test.php', $this->tmpPath);
        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', $initialConvertTime);

        touch($srcFilename, time() + 1000);
        $this->converter->convert('test.php', $this->tmpPath);
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
            EOF
        );

        $this->converter->setCommand('php', 'txt', 'php {from} > {to}');

        $this->converter->convert('test.php', $this->tmpPath);
        $initialConvertTime = file_get_contents($this->tmpPath . '/test.txt');

        usleep(1);
        $this->converter->convert('test.php', $this->tmpPath);

        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', $initialConvertTime);

        $this->converter->setForceConvert(true);
        $this->converter->convert('test.php', $this->tmpPath);

        $this->assertNotEquals($initialConvertTime, file_get_contents($this->tmpPath . '/test.txt'));
    }

    /**
     * @depends testConvertOutdated
     */
    public function testCheckOutdatedCallback(): void
    {
        $srcFilename = $this->tmpPath . '/test.php';
        file_put_contents(
            $srcFilename,
            <<<'EOF'
            <?php

            echo microtime();
            EOF
        );

        $this->converter->setCommand('php', 'txt', 'php {from} > {to}');

        $this->converter->convert('test.php', $this->tmpPath);
        $initialConvertTime = file_get_contents($this->tmpPath . '/test.txt');

        $this->converter->setIsOutdatedCallback(static fn () => false);

        $this->converter->convert('test.php', $this->tmpPath);
        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', $initialConvertTime);

        $this->converter->setIsOutdatedCallback(static fn () => true);

        $this->converter->convert('test.php', $this->tmpPath);
        $this->assertNotEquals($initialConvertTime, file_get_contents($this->tmpPath . '/test.txt'));
    }

    public function testSassCli(): void
    {
        $this->converter->setCommand('scss', 'css', '@npm/.bin/sass {options} {from} {to}');

        $this->converter->convert(
            'custom.scss',
            $this->aliases->get('@root/tests/public/sass'),
            [
                'scss' => [
                    'command' => '-I {path} --style compressed',
                    'path' => '@sourcePath/sass',
                ],
            ]
        );

        $customCss = file_get_contents($this->aliases->get('@root/tests/public/sass/custom.css'));

        $this->assertFileExists($this->aliases->get('@root/tests/public/sass/custom.css'));
        $this->assertFileExists($this->aliases->get('@root/tests/public/sass/custom.css.map'));
        $this->assertStringEqualsFile(
            $this->aliases->get('@root/tests/public/sass/custom.css'),
            $customCss
        );

        FileHelper::unlink($this->aliases->get('@root/tests/public/sass/custom.css'));
        FileHelper::unlink($this->aliases->get('@root/tests/public/sass/custom.css.map'));
    }

    public function testNotExistsConverter(): void
    {
        $this->converter->setCommand('scss', 'css', 'not-exist/sass');

        $this->converter->convert(
            'custom.scss',
            $this->aliases->get('@root/tests/public/sass'),
        );

        $this->assertFileDoesNotExist($this->aliases->get('@root/tests/public/sass/custom.css'));
    }
}
