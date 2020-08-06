<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

/**
 * AssetBundle represents a collection of asset files, such as CSS, JS, images.
 *
 * Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application. The
 * name is the [fully qualified class name](http://php.net/manual/en/language.namespaces.rules.php) of the class
 * representing it.
 *
 * An asset bundle can depend on other asset bundles. When registering an asset bundle with a view, all its dependent
 * asset bundles will be automatically registered.
 */
class AssetBundle
{
    /**
     * @var string|null the Web-accessible directory that contains the asset files in this bundle.
     *
     * If {@see sourcePath} is set, this property will be *overwritten* by {@see AssetManager} when it publishes the
     * asset files from {@see sourcePath}.
     *
     * You can use either a directory or an alias of the directory.
     */
    public ?string $basePath = null;

    /**
     * @var string|null the base URL for the relative asset files listed in {@see js} and {@see css}.
     *
     * If {@see {sourcePath} is set, this property will be *overwritten* by {@see {AssetManager} when it publishes the
     * asset files from {@see {sourcePath}.
     *
     * You can use either a URL or an alias of the URL.
     */
    public ?string $baseUrl = null;

    /**
     * @var bool Indicates whether the AssetBundle uses CDN exclusively.
     */
    public bool $cdn = false;

    /**
     * @var array list of CSS files that this bundle contains. Each CSS file can be specified in one of the three
     * formats as explained in {@see js}.
     *
     * Note that only a forward slash "/" should be used as directory separator.
     */
    public array $css = [];

    /**
     * @var array the options that will be passed to {@see \Yiisoft\View\View::registerCssFile()} when registering the CSS
     * files in this bundle.
     */
    public array $cssOptions = [];

    /**
     * @var array the options line command from converter.
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
        'less'   => null,
        'scss'   => null,
        'sass'   => null,
        'styl'   => null,
        'coffee' => null,
        'ts'     => null,
    ];

    /**
     * @var array list of bundle class names that this bundle depends on.
     *
     * For example:
     *
     * ```php
     * public $depends = [
     *    \Yiisoft\Jquery\YiiAsset::class,
     *    \Yiisoft\Bootstrap4\BootstrapAsset::class,
     * ];
     * ```
     */
    public array $depends = [];

    /**
     * @var array list of JavaScript files that this bundle contains. Each JavaScript file can be specified in one of
     * the following formats:
     *
     * - an absolute URL representing an external asset. For example,
     *   `http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js` or
     *   `//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js`.
     * - a relative path representing a local asset (e.g. `js/main.js`). The actual file path of a local asset can be
     *   determined by prefixing [[basePath]] to the relative path, and the actual URL of the asset can be determined
     *   by prefixing [[baseUrl]] to the relative path.
     * - an array, with the first entry being the URL or relative path as described before, and a list of key => value
     *   pairs that will be used to overwrite {@see jsOptions} settings for this entry.
     *
     * Note that only a forward slash "/" should be used as directory separator.
     */
    public array $js = [];

    /**
     * @var array the options that will be passed to {@see Yiisoft\View\View::registerJsFile()} when registering the JS
     * files in this bundle.
     */
    public array $jsOptions = [];

    /**
     * @var array Plain Javascript strings that will be passed to {@see Yiisoft\View\View::registerJs()}.
     */
    public array $jsStrings = [];

    /**
     * @var array Javascript variables that will be passed to {@see Yiisoft\View\View::registerJsVar()}.
     */
    public array $jsVars = [];

    /**
     * @var array the options to be passed to {@see AssetManager::publish()} when the asset bundle  is being published.
     * This property is used only when {@see sourcePath} is set.
     */
    public array $publishOptions = [];

    /**
     * @var string|null the directory that contains the source asset files for this asset bundle. A source asset file is a
     * file that is part of your source code repository of your Web application.
     *
     * You must set this property if the directory containing the source asset files is not Web accessible. By setting
     * this property, {@see AssetManager} will publish the source asset files to a Web-accessible directory automatically
     * when the asset bundle is registered on a page.
     *
     * If you do not set this property, it means the source asset files are located under {@see basePath}.
     *
     * You can use either a directory or an alias of the directory.
     *
     * {@see publishOptions}
     */
    public ?string $sourcePath = null;
}
