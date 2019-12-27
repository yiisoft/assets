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
                '/repeat/assetSources/repeat/css/stub.css',
                '/repeat',
            ],
            [
                'js', '@web/assetSources/repeat/js/jquery.js', false,
                '/repeat/assetSources/repeat/js/jquery.js',
                '/repeat',
            ],
            // JS files registration
            [
                'js', '@web/assetSources/js/missing-file.js', true,
                '/baseUrl/assetSources/js/missing-file.js'
            ],
            [
                'js', '@web/assetSources/js/jquery.js', false,
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
                'css', '@web/assetSources/css/missing-file.css', true,
                '/baseUrl/assetSources/css/missing-file.css',
            ],
            [
                'css', '@web/assetSources/css/stub.css', false,
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
            // Custom `@web` aliases
            [
                'js', '@web/assetSources/js/missing-file1.js', true,
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
                'css', '@web/assetSources/css/stub.css', false,
                '/en/blog/backend/assetSources/css/stub.css',
                '/en/blog/backend',
            ],
            // UTF-8 chars
            [
                'css', '@web/assetSources/css/stub.css', false,
                '/рус/сайт/assetSources/css/stub.css',
                '/рус/сайт',
            ],
            [
                'js', '@web/assetSources/js/jquery.js', false,
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
        $originalAlias = $this->aliases->get('@web');

        if ($webAlias === null) {
            $webAlias = $originalAlias;
        }

        $this->aliases->set('@web', $webAlias);

        $path = $this->aliases->get($path);

        $this->assetManager->setAppendTimestamp($appendTimestamp);

        $method = 'register' . ucfirst($type) . 'File';

        $this->assetManager->$method($path, [], null);

        $this->assertStringContainsString(
            $expected,
            $type === 'css' ? $this->assetManager->getCssFiles()[$expected]['url']
                : $this->assetManager->getJsFiles()[$expected]['url']
        );
    }
}
