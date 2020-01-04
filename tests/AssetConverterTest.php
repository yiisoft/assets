<?php

namespace Yiisoft\Assets\Tests;

use Yiisoft\Assets\AssetConverter;

/**
 * AssetConverterTest.
 */
final class AssetConverterTest extends TestCase
{
    /**
     * @var string temporary files path
     */
    private $tmpPath;

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
        file_put_contents($this->tmpPath . '/test.php', <<<EOF
<?php

echo "Hello World!\n";
echo "Hello Yii!";
EOF
        );

        $converter = new AssetConverter($this->aliases, $this->logger);

        $converter->setCommand('php', 'txt', 'php {from} > {to}');

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
        file_put_contents($srcFilename, <<<'EOF'
<?php

echo microtime();
EOF
        );

        $converter = new AssetConverter($this->aliases, $this->logger);

        $converter->setCommand('php', 'txt', 'php {from} > {to}');

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
        file_put_contents($this->tmpPath . '/test.php', <<<'EOF'
<?php

echo microtime();
EOF
        );

        $converter = new AssetConverter($this->aliases, $this->logger);

        $converter->setCommand('php', 'txt', 'php {from} > {to}');

        $converter->convert('test.php', $this->tmpPath);
        $initialConvertTime = file_get_contents($this->tmpPath . '/test.txt');

        usleep(1);
        $converter->convert('test.php', $this->tmpPath);

        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', $initialConvertTime);

        $converter->setForceConvert(true);
        $converter->convert('test.php', $this->tmpPath);

        $this->assertNotEquals($initialConvertTime, file_get_contents($this->tmpPath . '/test.txt'));
    }

    /**
     * @depends testConvertOutdated
     */
    public function testCheckOutdatedCallback(): void
    {
        $srcFilename = $this->tmpPath . '/test.php';
        file_put_contents($srcFilename, <<<'EOF'
<?php

echo microtime();
EOF
        );

        $converter = new AssetConverter($this->aliases, $this->logger);

        $converter->setCommand('php', 'txt', 'php {from} > {to}');

        $converter->convert('test.php', $this->tmpPath);
        $initialConvertTime = file_get_contents($this->tmpPath . '/test.txt');

        $converter->setIsOutdatedCallback(static function () {
            return false;
        });

        $converter->convert('test.php', $this->tmpPath);
        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', $initialConvertTime);

        $converter->setIsOutdatedCallback(static function () {
            return true;
        });

        $converter->convert('test.php', $this->tmpPath);
        $this->assertNotEquals($initialConvertTime, file_get_contents($this->tmpPath . '/test.txt'));
    }
}
