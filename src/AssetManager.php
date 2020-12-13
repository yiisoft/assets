<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use RuntimeException;
use Yiisoft\Assets\Exception\InvalidConfigException;

use function array_key_exists;
use function array_merge;
use function array_shift;
use function array_unshift;
use function is_array;
use function is_file;

/**
 * AssetManager manages asset bundle configuration and loading.
 */
final class AssetManager
{
    /**
     * @var array AssetBundle[] list of the registered asset bundles. The keys are the bundle names, and the values
     * are the registered {@see AssetBundle} objects.
     *
     * {@see registerAssetBundle()}
     */
    private array $assetBundles = [];

    /**
     * @var array list of asset bundle configurations. This property is provided to customize asset bundles.
     * When a bundle is being loaded by {@see getBundle()}, if it has a corresponding configuration specified here, the
     * configuration will be applied to the bundle.
     *
     * The array keys are the asset bundle names, which typically are asset bundle class names without leading
     * backslash. The array values are the corresponding configurations. If a value is false, it means the corresponding
     * asset bundle is disabled and {@see getBundle()} should return null.
     *
     * If this property is false, it means the whole asset bundle feature is disabled and {@see {getBundle()} will
     * always return null.
     */
    private array $bundles = [];
    private array $cssFiles = [];
    private array $dummyBundles = [];
    private array $jsFiles = [];
    private array $jsStrings = [];
    private array $jsVar = [];
    private ?AssetConverterInterface $converter = null;
    private AssetPublisherInterface $publisher;

    /**
     * Registers the asset manager being used by this view object.
     *
     * @return array the asset manager. Defaults to the "assetManager" application component.
     */
    public function getAssetBundles(): array
    {
        return $this->assetBundles;
    }

    /**
     * Returns the named asset bundle.
     *
     * This method will first look for the bundle in {@see bundles()}. If not found, it will treat `$name` as the class
     * of the asset bundle and create a new instance of it.
     *
     * @param string $name the class name of the asset bundle (without the leading backslash).
     *
     * @throws InvalidConfigException
     *
     * @return AssetBundle the asset bundle instance
     */
    public function getBundle(string $name): AssetBundle
    {
        if (!isset($this->bundles[$name])) {
            return $this->bundles[$name] = $this->publisher->loadBundle($name, []);
        }

        if ($this->bundles[$name] instanceof AssetBundle) {
            return $this->bundles[$name];
        }

        if (is_array($this->bundles[$name])) {
            return $this->bundles[$name] = $this->publisher->loadBundle($name, $this->bundles[$name]);
        }

        if ($this->bundles[$name] === false) {
            return $this->loadDummyBundle($name);
        }

        throw new InvalidConfigException("Invalid asset bundle configuration: $name");
    }

    public function getConverter(): ?AssetConverterInterface
    {
        return $this->converter;
    }

    /**
     * Return config array CSS AssetBundle.
     *
     * @return array
     */
    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }

    /**
     * Return config array JS AssetBundle.
     *
     * @return array
     */
    public function getJsFiles(): array
    {
        return $this->jsFiles;
    }

    /**
     * Return JS code blocks.
     *
     * @return array
     */
    public function getJsStrings(): array
    {
        return $this->jsStrings;
    }

    /**
     * Return JS variables.
     *
     * @return array
     */
    public function getJsVar(): array
    {
        return $this->jsVar;
    }

    public function getPublisher(): AssetPublisherInterface
    {
        return $this->publisher;
    }

    /**
     * This property is provided to customize asset bundles.
     *
     * @param array $value
     *
     * {@see bundles}
     */
    public function setBundles(array $value): void
    {
        $this->bundles = $value;
    }

    /**
     * AssetConverter component.
     *
     * @param AssetConverterInterface $value the asset converter. This can be either an object implementing the
     * {@see AssetConverterInterface}, or a configuration array that can be used to create the asset converter object.
     */
    public function setConverter(AssetConverterInterface $value): void
    {
        $this->converter = $value;
    }

    /**
     * AssetPublisher component.
     *
     * @param AssetPublisherInterface $value
     *
     * {@see publisher}
     */
    public function setPublisher(AssetPublisherInterface $value): void
    {
        $this->publisher = $value;
    }

    /**
     * Generate the array configuration of the AssetBundles
     *
     * @param array $names
     * @param int|null $position
     *
     * @throws InvalidConfigException
     */
    public function register(array $names, ?int $position = null): void
    {
        foreach ($names as $name) {
            $this->registerAssetBundle($name, $position);
            $this->registerFiles($name);
        }
    }

    /**
     * Registers a CSS file.
     *
     * This method should be used for simple registration of CSS files. If you want to use features of
     * {@see AssetManager} like appending timestamps to the URL and file publishing options, use {@see AssetBundle}
     * and {@see registerAssetBundle()} instead.
     *
     * @param string $url the CSS file to be registered.
     * @param array $options the HTML attributes for the link tag.
     * @param string|null $key
     */
    public function registerCssFile(string $url, array $options = [], string $key = null): void
    {
        $key = $key ?: $url;

        $this->cssFiles[$key]['url'] = $url;
        $this->cssFiles[$key]['attributes'] = $options;
    }

    /**
     * Registers a JS file.
     *
     * This method should be used for simple registration of JS files. If you want to use features of
     * {@see AssetManager} like appending timestamps to the URL and file publishing options, use {@see AssetBundle}
     * and {@see registerAssetBundle()} instead.
     *
     * @param string $url the JS file to be registered.
     * @param array $options the HTML attributes for the script tag. The following options are specially handled and
     * are not treated as HTML attributes:
     *
     * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
     *     * {@see \Yiisoft\View\WebView::POSITION_HEAD} in the head section
     *     * {@see \Yiisoft\View\WebView::POSITION_BEGIN} at the beginning of the body section
     *     * {@see \Yiisoft\View\WebView::POSITION_END} at the end of the body section. This is the default value.
     * @param string|null $key
     */
    public function registerJsFile(string $url, array $options = [], string $key = null): void
    {
        $key = $key ?: $url;

        if (!array_key_exists('position', $options)) {
            $options = array_merge(['position' => 3], $options);
        }

        $this->jsFiles[$key]['url'] = $url;
        $this->jsFiles[$key]['attributes'] = $options;
    }

    /**
     * Registers a JavaScript code block.
     *
     * This method should be used for simple registration of JavaScript code blocks.
     *
     * @param string $jsString the JavaScript code block to be registered.
     * @param array $options the HTML attributes for the script tag. The following options are specially handled and
     * are not treated as HTML attributes:
     *
     * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
     *     * {@see \Yiisoft\View\WebView::POSITION_HEAD} in the head section
     *     * {@see \Yiisoft\View\WebView::POSITION_BEGIN} at the beginning of the body section
     *     * {@see \Yiisoft\View\WebView::POSITION_END} at the end of the body section. This is the default value.
     *
     * @param string|null $key the key that identifies the JS code block. If null, it will use $jsString as the key. If two JS code
     * blocks are registered with the same key, the latter will overwrite the former.
     *
     * @return void
     */
    public function registerJsString(string $jsString, array $options = [], string $key = null): void
    {
        $key = $key ?: $jsString;

        if (!\array_key_exists('position', $options)) {
            $options = array_merge(['position' => 3], $options);
        }

        $this->jsStrings[$key]['string'] = $jsString;
        $this->jsStrings[$key]['attributes'] = $options;
    }

    /**
     * Registers a JS variable.
     *
     * This method should be used for simple registration of JS files. If you want to use features of
     * {@see AssetManager} like appending timestamps to the URL and file publishing options, use {@see AssetBundle}
     * and {@see registerAssetBundle()} instead.
     *
     * @parem string $varName the variable name
     * @param array|string $jsVar the JS code block to be registered.
     * @param array $options the HTML attributes for the script tag. The following options are specially handled and
     * are not treated as HTML attributes:
     *
     * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
     *     * {@see \Yiisoft\View\WebView::POSITION_HEAD} in the head section. This is the default value.
     *     * {@see \Yiisoft\View\WebView::POSITION_BEGIN} at the beginning of the body section
     *     * {@see \Yiisoft\View\WebView::POSITION_END} at the end of the body section
     *
     * @return void
     */
    public function registerJsVar(string $varName, $jsVar, array $options = []): void
    {
        if (!\array_key_exists('position', $options)) {
            $options = array_merge(['position' => 1], $options);
        }

        $this->jsVar[$varName]['variables'] = $jsVar;
        $this->jsVar[$varName]['attributes'] = $options;
    }

    /**
     * Converter SASS, SCSS, Stylus and other formats to CSS.
     *
     * @param AssetBundle $bundle
     *
     * @return AssetBundle
     */
    private function convertCss(AssetBundle $bundle): AssetBundle
    {
        foreach ($bundle->css as $i => $css) {
            if (is_array($css)) {
                $file = array_shift($css);
                if (AssetUtil::isRelative($file)) {
                    $css = array_merge($bundle->cssOptions, $css);

                    if (is_file("$bundle->basePath/$file")) {
                        /**
                         * @psalm-suppress PossiblyNullArgument
                         * @psalm-suppress PossiblyNullReference
                         */
                        array_unshift($css, $this->converter->convert(
                            $file,
                            $bundle->basePath,
                            $bundle->converterOptions
                        ));

                        $bundle->css[$i] = $css;
                    }
                }
            } elseif (AssetUtil::isRelative($css)) {
                if (is_file("$bundle->basePath/$css")) {
                    /**
                     * @psalm-suppress PossiblyNullArgument
                     * @psalm-suppress PossiblyNullReference
                     */
                    $bundle->css[$i] = $this->converter->convert(
                        $css,
                        $bundle->basePath,
                        $bundle->converterOptions
                    );
                }
            }
        }

        return $bundle;
    }

    /**
     * Convert files from TypeScript and other formats into JavaScript.
     *
     * @param AssetBundle $bundle
     *
     * @return AssetBundle
     */
    private function convertJs(AssetBundle $bundle): AssetBundle
    {
        foreach ($bundle->js as $i => $js) {
            if (is_array($js)) {
                $file = array_shift($js);
                if (AssetUtil::isRelative($file)) {
                    $js = array_merge($bundle->jsOptions, $js);

                    if (is_file("$bundle->basePath/$file")) {
                        /**
                         * @psalm-suppress PossiblyNullArgument
                         * @psalm-suppress PossiblyNullReference
                         */
                        array_unshift($js, $this->converter->convert(
                            $file,
                            $bundle->basePath,
                            $bundle->converterOptions
                        ));

                        $bundle->js[$i] = $js;
                    }
                }
            } elseif (AssetUtil::isRelative($js)) {
                if (is_file("$bundle->basePath/$js")) {
                    /**
                     * @psalm-suppress PossiblyNullArgument
                     * @psalm-suppress PossiblyNullReference
                     */
                    $bundle->js[$i] = $this->converter->convert($js, $bundle->basePath);
                }
            }
        }

        return $bundle;
    }

    /**
     * Registers the named asset bundle.
     *
     * All dependent asset bundles will be registered.
     *
     * @param string $name the class name of the asset bundle (without the leading backslash)
     * @param int|null $position if set, this forces a minimum position for javascript files. This will adjust depending
     * assets javascript file position or fail if requirement can not be met. If this is null, asset
     * bundles position settings will not be changed.
     *
     * {@see registerJsFile()} for more details on javascript position.
     *
     * @throws RuntimeException if the asset bundle does not exist or a circular dependency
     * is detected.
     *
     * @return AssetBundle the registered asset bundle instance.
     */
    private function registerAssetBundle(string $name, int $position = null): AssetBundle
    {
        if (!isset($this->assetBundles[$name])) {
            $bundle = $this->getBundle($name);

            $this->assetBundles[$name] = false;

            $pos = $bundle->jsOptions['position'] ?? null;

            foreach ($bundle->depends as $dep) {
                $this->registerAssetBundle($dep, $pos);
            }

            $this->assetBundles[$name] = $bundle;
        } elseif ($this->assetBundles[$name] === false) {
            throw new RuntimeException("A circular dependency is detected for bundle '$name'.");
        } else {
            $bundle = $this->assetBundles[$name];
        }

        if ($position !== null) {
            $pos = $bundle->jsOptions['position'] ?? null;

            if ($pos === null) {
                $bundle->jsOptions['position'] = $pos = $position;
            } elseif ($pos > $position) {
                throw new RuntimeException(
                    "An asset bundle that depends on '$name' has a higher javascript file " .
                    "position configured than '$name'."
                );
            }

            // update position for all dependencies
            foreach ($bundle->depends as $dep) {
                $this->registerAssetBundle($dep, $pos);
            }
        }
        return $bundle;
    }

    /**
     * Loads dummy bundle by name.
     *
     * @param string $bundleName AssetBunle name
     *
     * @return AssetBundle
     */
    private function loadDummyBundle(string $bundleName): AssetBundle
    {
        if (!isset($this->dummyBundles[$bundleName])) {
            $this->dummyBundles[$bundleName] = $this->publisher->loadBundle($bundleName, [
                'sourcePath' => null,
                'js' => [],
                'css' => [],
                'depends' => [],
            ]);
        }

        return $this->dummyBundles[$bundleName];
    }

    /**
     * Register assets from a named bundle and its dependencies
     *
     * @param string $bundleName
     *
     * @throws InvalidConfigException
     */
    private function registerFiles(string $bundleName): void
    {
        if (!isset($this->assetBundles[$bundleName])) {
            return;
        }

        $bundle = $this->assetBundles[$bundleName];

        foreach ($bundle->depends as $dep) {
            $this->registerFiles($dep);
        }

        $this->registerAssetFiles($bundle);
    }

    /**
     * Registers asset files from a bundle considering dependencies
     *
     * @param AssetBundle $bundle
     */
    private function registerAssetFiles(AssetBundle $bundle): void
    {
        if (isset($bundle->basePath, $bundle->baseUrl) && null !== $this->converter) {
            $this->convertCss($bundle);
            $this->convertJs($bundle);
        }

        foreach ($bundle->js as $js) {
            if (is_array($js)) {
                $file = array_shift($js);
                $options = array_merge($bundle->jsOptions, $js);
                $this->registerJsFile($this->publisher->getAssetUrl($bundle, $file), $options);
            } elseif ($js !== null) {
                $this->registerJsFile($this->publisher->getAssetUrl($bundle, $js), $bundle->jsOptions);
            }
        }

        foreach ($bundle->jsStrings as $key => $jsString) {
            $key = is_int($key) ? $jsString : $key;
            if (\is_array($jsString)) {
                $string = array_shift($jsString);
                $this->registerJsString($string, $jsString, $key);
            } elseif ($jsString !== null) {
                $this->registerJsString($jsString, $bundle->jsOptions, $key);
            }
        }

        foreach ($bundle->jsVar as $key => $jsVar) {
            $this->registerJsVar($key, $jsVar, $jsVar);
        }

        foreach ($bundle->css as $css) {
            if (is_array($css)) {
                $file = array_shift($css);
                $options = array_merge($bundle->cssOptions, $css);
                $this->registerCssFile($this->publisher->getAssetUrl($bundle, $file), $options);
            } elseif ($css !== null) {
                $this->registerCssFile($this->publisher->getAssetUrl($bundle, $css), $bundle->cssOptions);
            }
        }
    }
}
