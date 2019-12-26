<?php
declare(strict_types=1);

namespace Yiisoft\Assets\Tests;

/**
 * AssetAppendTimeStamp.
 */
final class AssetAppendTimeStampTest extends TestCase
{
    /**
     * @return array
     */
    public function registerFileDataProvider(): array
    {
        return [
            // Custom alias repeats in the asset URL
            [
                'css', '@web/assetSources/repeat/css/stub.css', false,
                '1<link href="/repeat/assetSources/repeat/css/stub.css" rel="stylesheet">234',
                '/repeat',
            ],
            [
                'js', '@web/assetSources/repeat/js/jquery.js', false,
                '123<script src="/repeat/assetSources/repeat/js/jquery.js"></script>4',
                '/repeat',
            ],
            // JS files registration
            [
                'js', '@web/assetSources/js/missing-file.js', true,
                '123<script src="/baseUrl/assetSources/js/missing-file.js"></script>4'
            ],
            [
                'js', '@web/assetSources/js/jquery.js', false,
                '123<script src="/baseUrl/assetSources/js/jquery.js"></script>4',
            ],
            [
                'js', 'http://example.com/assetSources/js/jquery.js', false,
                '123<script src="http://example.com/assetSources/js/jquery.js"></script>4',
            ],
            [
                'js', '//example.com/assetSources/js/jquery.js', false,
                '123<script src="//example.com/assetSources/js/jquery.js"></script>4',
            ],
            [
                'js', 'assetSources/js/jquery.js', false,
                '123<script src="assetSources/js/jquery.js"></script>4',
            ],
            [
                'js', '/assetSources/js/jquery.js', false,
                '123<script src="/assetSources/js/jquery.js"></script>4',
            ],
            // CSS file registration
            [
                'css', '@web/assetSources/css/missing-file.css', true,
                '1<link href="/baseUrl/assetSources/css/missing-file.css" rel="stylesheet">234',
            ],
            [
                'css', '@web/assetSources/css/stub.css', false,
                '1<link href="/baseUrl/assetSources/css/stub.css" rel="stylesheet">234',
            ],
            [
                'css', 'http://example.com/assetSources/css/stub.css', false,
                '1<link href="http://example.com/assetSources/css/stub.css" rel="stylesheet">234',
            ],
            [
                'css', '//example.com/assetSources/css/stub.css', false,
                '1<link href="//example.com/assetSources/css/stub.css" rel="stylesheet">234',
            ],
            [
                'css', 'assetSources/css/stub.css', false,
                '1<link href="assetSources/css/stub.css" rel="stylesheet">234',
            ],
            [
                'css', '/assetSources/css/stub.css', false,
                '1<link href="/assetSources/css/stub.css" rel="stylesheet">234',
            ],
            // Custom `@web` aliases
            [
                'js', '@web/assetSources/js/missing-file1.js', true,
                '123<script src="/backend/assetSources/js/missing-file1.js"></script>4',
                '/backend',
            ],
            [
                'js', 'http://full-url.example.com/backend/assetSources/js/missing-file.js', true,
                '123<script src="http://full-url.example.com/backend/assetSources/js/missing-file.js"></script>4',
                '/backend',
            ],
            [
                'css', '//backend/backend/assetSources/js/missing-file.js', true,
                '1<link href="//backend/backend/assetSources/js/missing-file.js" rel="stylesheet">234',
                '/backend',
            ],
            [
                'css', '@web/assetSources/css/stub.css', false,
                '1<link href="/en/blog/backend/assetSources/css/stub.css" rel="stylesheet">234',
                '/en/blog/backend',
            ],
            // UTF-8 chars
            [
                'css', '@web/assetSources/css/stub.css', false,
                '1<link href="/рус/сайт/assetSources/css/stub.css" rel="stylesheet">234',
                '/рус/сайт',
            ],
            [
                'js', '@web/assetSources/js/jquery.js', false,
                '123<script src="/汉语/漢語/assetSources/js/jquery.js"></script>4',
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
        $originalAlias = $this->aliases->get('@web');

        if ($webAlias === null) {
            $webAlias = $originalAlias;
        }

        $this->aliases->set('@web', $webAlias);

        $path = $this->aliases->get($path);

        $this->assetManager->setAppendTimestamp($appendTimestamp);

        $method = 'register' . ucfirst($type) . 'File';

        $this->assetManager->$method($path, [], null, $this->webView);

        $this->assertEquals(
            $expected,
            $this->webView->renderFile($this->aliases->get('@view/rawlayout.php'))
        );
    }
}
