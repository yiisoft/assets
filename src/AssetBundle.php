<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

/**
 * AssetBundle represents a collection of asset files, such as CSS, JS, images.
 *
 * Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application.
 * The name is the fully qualified class name {@see https://www.php.net/manual/en/language.namespaces.rules.php}
 * of the class representing it.
 *
 * An asset bundle can depend on other asset bundles. When registering an asset bundle with a view, all its dependent
 * asset bundles will be automatically registered.
 *
 * @psalm-type CssFile = string|array{0:string,1?:int}&array
 * @psalm-type CssString = string|array{0:mixed,1?:int}&array
 * @psalm-type JsFile = string|array{0:string,1?:int}&array
 * @psalm-type JsString = string|array{0:mixed,1?:int}&array
 */
class AssetBundle
{
    /**
     * @var string|null The Web-accessible directory that contains the asset files in this bundle.
     *
     * If {@see $sourcePath} is set, this property will be *overwritten* by {@see AssetManager} when it publishes the
     * asset files from {@see $sourcePath}.
     *
     * You can use either a directory or an alias of the directory.
     */
    public ?string $basePath = null;

    /**
     * @var string|null The base URL for the relative asset files listed in {@see $js} and {@see $css}.
     *
     * If {@see $sourcePath} is set, this property will be *overwritten* by {@see AssetManager} when it publishes the
     * asset files from {@see $sourcePath}.
     *
     * You can use either a URL or an alias of the URL.
     */
    public ?string $baseUrl = null;

    /**
     * @var bool Indicates whether the AssetBundle uses CDN exclusively.
     */
    public bool $cdn = false;

    /**
     * @var array List of CSS files. Each CSS file can be specified in one of the following formats:
     *
     * - An absolute URL representing an external asset. For example,
     *   `https://cdn.jsdelivr.net/npm/bulma@0.9.2/css/bulma.min.css` or
     *   `//cdn.jsdelivr.net/npm/bulma@0.9.2/css/bulma.min.css`.
     * - A relative path representing a local asset (e.g. `css/main.css`). The actual file path of a local asset can be
     *   determined by prefixing {@see $basePath} to the relative path, and the actual URL of the asset can be
     *   determined by prefixing {@see $baseUrl} to the relative path.
     * - An array, with the first entry being the URL or relative path as described before, and a list of key/value
     *   pairs that will be used to overwrite {@see $cssOptions} settings for this entry.
     *
     * Note that only a forward slash `/` should be used as directory separator.
     *
     * Optionally, use string keys for identifies the CSS file.
     * If key not set, it will use full CSS file URL as the key.
     *
     * Example:
     *
     * ```php
     * public array $css = [
     *     'https://cdn.jsdelivr.net/npm/bulma@0.9.2/css/bulma.min.css',
     *     'css/main.css',
     *     ['css/a.css'],
     *     ['css/b.css', 3],
     *     ['css/c.css', 3, 'crossorigin' => 'any'],
     *     'key' => 'css/d.css',
     * ]
     * ```
     *
     * @psalm-var CssFile[]
     */
    public array $css = [];

    /**
     * @var array List of CSS blocks. Each CSS block can be specified in one of the following formats:
     *
     *  - CSS block. For example, `a { color: red; }`.
     *  - An array, with the first entry is CSS block, second entry is position on page (integer, optional) that
     *    will be used to overwrite {@see $cssPosition} setting and a list of key/value pairs
     *    that will be used to overwrite {@see $cssOptions} settings for this CSS block.
     *
     * Optionally, use string keys for identifies the CSS block.
     *
     * Example:
     *
     * ```php
     * public array $cssString = [
     *     'a { color: red; }',
     *     ['a { color: red; }'],
     *     ['a { color: red; }', 3],
     *     ['a { color: red; }', 3, 'crossorigin' => 'any'],
     *     'key1' => 'a { color: red; }',
     *     'key2' => ['a { color: red; }'],
     *     'key3' => ['a { color: red; }', 3],
     *     'key4' => ['a { color: red; }', 3, 'crossorigin' => 'any'],
     * ];
     * ```
     *
     * @psalm-var CssString[]
     */
    public array $cssStrings = [];

    /**
     * @var array The options that will be passed to {@see \Yiisoft\View\WebView::registerCssFile()}
     * when registering the CSS files in this bundle.
     */
    public array $cssOptions = [];

    /**
     * @var int|null The CSS file position.
     */
    public ?int $cssPosition = null;

    /**
     * @var array The options line command from converter.
     *
     * Example: Dart SASS minify css.
     *
     * public array $converterOptions = [
     *      'scss' => [
     *          'command' => '-I {path} --style compressed',
     *          'path' => '@root/tests/public/sourcepath/sass'
     *      ],
     * ];
     */
    public array $converterOptions = [
        'less' => null,
        'scss' => null,
        'sass' => null,
        'styl' => null,
        'coffee' => null,
        'ts' => null,
    ];

    /**
     * @var array List of asset bundle class names that this bundle depends on.
     *
     * For example:
     *
     * ```php
     * public array $depends = [
     *     Yiisoft\Yii\Bootstrap5\Assets\BootstrapAsset:class,
     *     Yiisoft\Yii\Bulma\Asset\BulmaAsset:class,
     * ]:
     * ```
     */
    public array $depends = [];

    /**
     * @var array List of JavaScript files that this bundle contains.
     *
     * Each JavaScript file can be specified in one of the following formats:
     *
     * - an absolute URL representing an external asset. For example,
     *   `http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js` or
     *   `//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js`.
     * - a relative path representing a local asset (e.g. `js/main.js`). The actual file path of a local asset can be
     *   determined by prefixing {@see $basePath} to the relative path, and the actual URL of the asset can be
     *   determined by prefixing {@see $baseUrl} to the relative path.
     * - an array, with the first entry being the URL or relative path as described before, and a list of key => value
     *   pairs that will be used to overwrite {@see $jsOptions} settings for this entry.
     *
     * Note that only a forward slash "/" should be used as directory separator.
     *
     * @psalm-var JsFile[]
     */
    public array $js = [];

    /**
     * @var array List of JS blocks. Each JS block can be specified in one of the following formats:
     *
     *  - JS block. For example, `alert(42);`.
     *  - An array, with the first entry is JS block, second entry is position on page (integer, optional) that
     *    will be used to overwrite {@see $jsPosition} setting and a list of key/value pairs
     *    that will be used to overwrite {@see $jsOptions} settings for this CSS block.
     *
     * Optionally, use string keys for identifies the JS block.
     *
     * Example:
     *
     * ```php
     * public array $jsStrings = [
     *     'alert(1);',
     *     ['alert(2);'],
     *     ['alert(3);', 3],
     *     ['alert(4);', 3, 'id' => 'main'],
     *     'key1' => 'alert(5);',
     *     'key2' => ['alert(6);'],
     *     'key3' => ['alert(7);', 3],
     *     'key4' => ['alert(8);', 3, 'id' => 'second'],
     * ];
     * ```
     *
     * @psalm-var JsString[]
     */
    public array $jsStrings = [];

    /**
     * @var array The options that will be passed to {@see \Yiisoft\View\WebView::registerJsFile()}
     * when registering the JS files in this bundle.
     */
    public array $jsOptions = [];

    /**
     * @var int|null The JS file position.
     */
    public ?int $jsPosition = null;

    /**
     * @var array JavaScript variables to be passed to {@see \Yiisoft\View\WebView::registerJsVar()}.
     */
    public array $jsVars = [];

    /**
     * @var array The options to be passed to {@see AssetPublisherInterface::publish()} when the asset bundle
     * is being published. This property is used only when {@see $sourcePath} is set.
     */
    public array $publishOptions = [];

    /**
     * @var string[] List of file paths to export into a format readable
     * by third party tools such as Webpack. See {@see AssetManager::export()}.
     *
     * If the array is empty, the file paths from the {@see $css} and {@see $js}
     * will be exported. See {@see AssetUtil::extractFilePathsForExport()}.
     *
     * For example:
     *
     * ```php
     * public array $export = [
     *     'img/image.png',
     *     'css/style.css',
     *     'js/script.js',
     * ]:
     * ```
     */
    public array $export = [];

    /**
     * @var string|null The directory that contains the source asset files for this asset bundle.
     * A source asset file is a file that is part of your source code repository of your Web application.
     * You must set this property if the directory containing the source asset files is not Web accessible.
     *
     * If a publisher is set via {@see AssetManager::withPublisher()}, {@see AssetManager} will publish the source
     * asset files to a Web-accessible directory automatically when the asset bundle is registered on a page.
     *
     * If you do not set this property, it means the source asset files are located under {@see $basePath}.
     *
     * You can use either a directory or an alias of the directory.
     *
     * {@see $publishOptions}
     */
    public ?string $sourcePath = null;
}
