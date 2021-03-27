<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use RuntimeException;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\Exception\InvalidConfigException;

use function array_key_exists;
use function array_merge;
use function array_shift;
use function array_unshift;
use function is_array;
use function is_file;
use function is_int;

/**
 * AssetManager manages asset bundle configuration and loading.
 */
final class AssetManager
{
    /**
     * @var string[] List of names of allowed asset bundles.
     * If the array is empty, then any asset bundles are allowed. Default to empty array.
     *
     * If the names of allowed resource bundles were specified, they will be registered {@see register()}
     * when the instance is created, and it will no longer be possible to register asset bundles.
     * Also, you can get bundles using the method, only those that were specified as allowed.
     */
    private array $allowedBundleNames;

    /**
     * @var array The asset bundle configurations. This property is provided to customize asset bundles.
     * When a bundle is being loaded by {@see getBundle()}, if it has a corresponding configuration
     * specified here, the configuration will be applied to the bundle.
     *
     * The array keys are the asset class bundle names (without leading backslash).
     * If a value is false, it means the corresponding asset bundle is disabled and {@see getBundle()}
     * should return an instance of the specified asset bundle with empty property values.
     */
    private array $customizedBundles;

    /**
     * @var array AssetBundle[] list of the registered asset bundles.
     * The keys are the bundle names, and the values are the registered {@see AssetBundle} objects.
     *
     * {@see registerAssetBundle()}
     */
    private array $registeredBundles = [];

    private array $loadedBundles = [];
    private array $dummyBundles = [];
    private array $cssFiles = [];
    private array $jsFiles = [];
    private array $jsStrings = [];
    private array $jsVar = [];
    private ?AssetConverterInterface $converter = null;
    private ?AssetPublisherInterface $publisher = null;
    private AssetLoaderInterface $loader;
    private Aliases $aliases;

    /**
     * @param Aliases $aliases The aliases instance.
     * @param AssetLoaderInterface $loader The loader instance.
     * @param array $allowedBundleNames List of names of allowed asset bundles {@see $allowedBundleNames}.
     * @param array $customizedBundles The asset bundle configurations {@see $customizedBundles}.
     */
    public function __construct(
        Aliases $aliases,
        AssetLoaderInterface $loader,
        array $allowedBundleNames = [],
        array $customizedBundles = []
    ) {
        $this->aliases = $aliases;
        $this->loader = $loader;
        $this->allowedBundleNames = $allowedBundleNames;
        $this->customizedBundles = $customizedBundles;
        $this->register($this->allowedBundleNames);
    }

    /**
     * Returns a cloned named asset bundle.
     *
     * This method will first look for the bundle in {@see $customizedBundles}.
     * If not found, it will treat `$name` as the class of the asset bundle and create a new instance of it.
     * If `$name` is not a class name, an {@see AssetBundle} instance will be created.
     *
     * Cloning is used to prevent an asset bundle instance from being modified in a non-context of the asset manager.
     *
     * @param string $name The class name of the asset bundle (without the leading backslash).
     *
     * @throws InvalidConfigException For invalid asset bundle configuration.
     *
     * @return AssetBundle The asset bundle instance.
     */
    public function getBundle(string $name): AssetBundle
    {
        if (!empty($this->allowedBundleNames) && !isset($this->registeredBundles[$name])) {
            throw new RuntimeException("The \"{$name}\" asset bundle is not allowed.");
        }

        $bundle = $this->loadBundle($name);
        $bundle = $this->publishBundle($bundle);

        return clone $bundle;
    }

    public function getConverter(): ?AssetConverterInterface
    {
        return $this->converter;
    }

    public function getLoader(): AssetLoaderInterface
    {
        return $this->loader;
    }

    public function getPublisher(): ?AssetPublisherInterface
    {
        return $this->publisher;
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
     * Returns config array JS AssetBundle.
     *
     * @return array
     */
    public function getJsFiles(): array
    {
        return $this->jsFiles;
    }

    /**
     * Returns JS code blocks.
     *
     * @return array
     */
    public function getJsStrings(): array
    {
        return $this->jsStrings;
    }

    /**
     * Returns JS variables.
     *
     * @return array
     */
    public function getJsVar(): array
    {
        return $this->jsVar;
    }

    /**
     * Sets the asset converter.
     *
     * @param AssetConverterInterface $converter The asset converter. This can be either an object implementing the
     * {@see AssetConverterInterface}, or a configuration array that can be used to create the asset converter object.
     */
    public function setConverter(AssetConverterInterface $converter): void
    {
        $this->converter = $converter;
    }

    /**
     * Sets the asset publisher.
     *
     * @param AssetPublisherInterface $publisher
     */
    public function setPublisher(AssetPublisherInterface $publisher): void
    {
        $this->publisher = $publisher;
    }

    /**
     * Generates the array configuration of the asset bundles {@see $registeredBundles}.
     *
     * @param string[] $names
     * @param int|null $position
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function register(array $names, ?int $position = null): void
    {
        if (!empty($this->allowedBundleNames) && !empty($this->registeredBundles)) {
            throw new RuntimeException('The allowed asset bundles are already registered and cannot be re-registered.');
        }

        foreach ($names as $name) {
            $this->registerAssetBundle($name, $position);
            $this->registerFiles($name);
        }
    }

    /**
     * Returns whether the asset bundle is registered.
     *
     * @param string $name The class name of the asset bundle (without the leading backslash).
     *
     * @return bool Whether the asset bundle is registered.
     */
    public function isRegisteredBundle(string $name): bool
    {
        return isset($this->registeredBundles[$name]);
    }

    /**
     * Registers a CSS file.
     *
     * @param string $url The CSS file to be registered.
     * @param array $options The HTML attributes for the link tag.
     * @param string|null $key The key that identifies the CSS file.
     */
    private function registerCssFile(string $url, array $options = [], string $key = null): void
    {
        $key = $key ?: $url;

        $this->cssFiles[$key]['url'] = $url;
        $this->cssFiles[$key]['attributes'] = $options;
    }

    /**
     * Registers a JS file.
     *
     * @param string $url The JS file to be registered.
     * @param array $options The HTML attributes for the script tag. The following options are specially handled and
     * are not treated as HTML attributes:
     *
     * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
     *     * {@see \Yiisoft\View\WebView::POSITION_HEAD} In the head section.
     *     * {@see \Yiisoft\View\WebView::POSITION_BEGIN} At the beginning of the body section.
     *     * {@see \Yiisoft\View\WebView::POSITION_END} At the end of the body section. This is the default value.
     * @param string|null $key The key that identifies the JS file.
     */
    private function registerJsFile(string $url, array $options = [], string $key = null): void
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
     * @param string $jsString The JavaScript code block to be registered.
     * @param array $options The HTML attributes for the script tag. The following options are specially handled and
     * are not treated as HTML attributes:
     *
     * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
     *     * {@see \Yiisoft\View\WebView::POSITION_HEAD} In the head section.
     *     * {@see \Yiisoft\View\WebView::POSITION_BEGIN} At the beginning of the body section.
     *     * {@see \Yiisoft\View\WebView::POSITION_END} At the end of the body section. This is the default value.
     * @param string|null $key The key that identifies the JS code block. If null, it will use $jsString as the key.
     * If two JS code blocks are registered with the same key, the latter will overwrite the former.
     */
    private function registerJsString(string $jsString, array $options = [], string $key = null): void
    {
        $key = $key ?: $jsString;

        if (!array_key_exists('position', $options)) {
            $options = array_merge(['position' => 3], $options);
        }

        $this->jsStrings[$key]['string'] = $jsString;
        $this->jsStrings[$key]['attributes'] = $options;
    }

    /**
     * Registers a JS variable.
     *
     * @param string $varName The variable name.
     * @param array|string $jsVar The JS code block to be registered.
     * @param array $options The HTML attributes for the script tag. The following options are specially handled and
     * are not treated as HTML attributes:
     *
     * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
     *     * {@see \Yiisoft\View\WebView::POSITION_HEAD} In the head section. This is the default value.
     *     * {@see \Yiisoft\View\WebView::POSITION_BEGIN} At the beginning of the body section.
     *     * {@see \Yiisoft\View\WebView::POSITION_END} At the end of the body section.
     */
    private function registerJsVar(string $varName, $jsVar, array $options = []): void
    {
        if (!array_key_exists('position', $options)) {
            $options = array_merge(['position' => 1], $options);
        }

        $this->jsVar[$varName]['variables'] = $jsVar;
        $this->jsVar[$varName]['attributes'] = $options;
    }

    /**
     * Converter SASS, SCSS, Stylus and other formats to CSS.
     *
     * @param AssetBundle $bundle
     */
    private function convertCss(AssetBundle $bundle): void
    {
        foreach ($bundle->css as $i => $css) {
            if (is_array($css)) {
                $file = array_shift($css);
                if (AssetUtil::isRelative($file)) {
                    $css = array_merge($bundle->cssOptions, $css);
                    $baseFile = $this->aliases->get("{$bundle->basePath}/{$file}");
                    if (is_file($baseFile)) {
                        /**
                         * @psalm-suppress PossiblyNullArgument
                         * @psalm-suppress PossiblyNullReference
                         */
                        array_unshift($css, $this->converter->convert(
                            $file,
                            $bundle->basePath,
                            $bundle->converterOptions,
                        ));

                        $bundle->css[$i] = $css;
                    }
                }
            } elseif (AssetUtil::isRelative($css)) {
                $baseCss = $this->aliases->get("{$bundle->basePath}/{$css}");
                if (is_file("$baseCss")) {
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
    }

    /**
     * Convert files from TypeScript and other formats into JavaScript.
     *
     * @param AssetBundle $bundle
     */
    private function convertJs(AssetBundle $bundle): void
    {
        foreach ($bundle->js as $i => $js) {
            if (is_array($js)) {
                $file = array_shift($js);
                if (AssetUtil::isRelative($file)) {
                    $js = array_merge($bundle->jsOptions, $js);
                    $baseFile = $this->aliases->get("{$bundle->basePath}/{$file}");
                    if (is_file($baseFile)) {
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
                $baseJs = $this->aliases->get("{$bundle->basePath}/{$js}");
                if (is_file($baseJs)) {
                    /**
                     * @psalm-suppress PossiblyNullArgument
                     * @psalm-suppress PossiblyNullReference
                     */
                    $bundle->js[$i] = $this->converter->convert($js, $bundle->basePath);
                }
            }
        }
    }

    /**
     * Registers the named asset bundle.
     *
     * All dependent asset bundles will be registered.
     *
     * @param string $name The class name of the asset bundle (without the leading backslash).
     * @param int|null $position If set, this forces a minimum position for javascript files.
     * This will adjust depending assets javascript file position or fail if requirement can not be met.
     * If this is null, asset bundles position settings will not be changed.
     *
     * {@see registerJsFile()} For more details on javascript position.
     *
     * @throws RuntimeException If the asset bundle does not exist or a circular dependency is detected.
     */
    private function registerAssetBundle(string $name, int $position = null): void
    {
        if (!isset($this->registeredBundles[$name])) {
            $bundle = $this->publishBundle($this->loadBundle($name));

            $this->registeredBundles[$name] = false;

            $pos = $bundle->jsOptions['position'] ?? null;

            foreach ($bundle->depends as $dep) {
                $this->registerAssetBundle($dep, $pos);
            }

            $this->registeredBundles[$name] = $bundle;
        } elseif ($this->registeredBundles[$name] === false) {
            throw new RuntimeException("A circular dependency is detected for bundle \"{$name}\".");
        } else {
            $bundle = $this->registeredBundles[$name];
        }

        if ($position !== null) {
            $pos = $bundle->jsOptions['position'] ?? null;

            if ($pos === null) {
                $bundle->jsOptions['position'] = $pos = $position;
            } elseif ($pos > $position) {
                throw new RuntimeException(
                    "An asset bundle that depends on \"{$name}\" has a higher JavaScript file " .
                    "position configured than \"{$name}\"."
                );
            }

            // update position for all dependencies
            foreach ($bundle->depends as $dep) {
                $this->registerAssetBundle($dep, $pos);
            }
        }
    }

    /**
     * Register assets from a named bundle and its dependencies.
     *
     * @param string $bundleName The asset bundle name.
     *
     * @throws InvalidConfigException If asset files are not found.
     */
    private function registerFiles(string $bundleName): void
    {
        if (!isset($this->registeredBundles[$bundleName])) {
            return;
        }

        $bundle = $this->registeredBundles[$bundleName];

        foreach ($bundle->depends as $dep) {
            $this->registerFiles($dep);
        }

        $this->registerAssetFiles($bundle);
    }

    /**
     * Registers asset files from a bundle considering dependencies.
     *
     * @param AssetBundle $bundle
     *
     * @throws InvalidConfigException If asset files are not found.
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
                $this->registerJsFile($this->loader->getAssetUrl($bundle, $file), $options);
            } elseif ($js !== null) {
                $this->registerJsFile($this->loader->getAssetUrl($bundle, $js), $bundle->jsOptions);
            }
        }

        foreach ($bundle->jsStrings as $key => $jsString) {
            $key = is_int($key) ? $jsString : $key;
            if (is_array($jsString)) {
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
                $this->registerCssFile($this->loader->getAssetUrl($bundle, $file), $options);
            } elseif ($css !== null) {
                $this->registerCssFile($this->loader->getAssetUrl($bundle, $css), $bundle->cssOptions);
            }
        }
    }

    /**
     * Loads an asset bundle class by name.
     *
     * @param string $name The asset bundle name.
     *
     * @throws InvalidConfigException For invalid asset bundle configuration.
     *
     * @return AssetBundle The asset bundle instance.
     */
    private function loadBundle(string $name): AssetBundle
    {
        if (isset($this->loadedBundles[$name])) {
            return $this->loadedBundles[$name];
        }

        if (!isset($this->customizedBundles[$name])) {
            return $this->loadedBundles[$name] = $this->loader->loadBundle($name);
        }

        if ($this->customizedBundles[$name] instanceof AssetBundle) {
            return $this->loadedBundles[$name] = $this->customizedBundles[$name];
        }

        if (is_array($this->customizedBundles[$name])) {
            return $this->loadedBundles[$name] = $this->loader->loadBundle($name, $this->customizedBundles[$name]);
        }

        if ($this->customizedBundles[$name] === false) {
            return $this->dummyBundles[$name] ??= $this->loader->loadBundle($name, (array) (new AssetBundle()));
        }

        throw new InvalidConfigException("Invalid configuration of the \"{$name}\" asset bundle.");
    }

    /**
     * Publishes a asset bundle.
     *
     * @param AssetBundle $bundle The asset bundle to publish.
     *
     * @throws InvalidConfigException If the asset or the asset file paths to be published does not exist.
     *
     * @return AssetBundle The published asset bundle.
     */
    private function publishBundle(AssetBundle $bundle): AssetBundle
    {
        if (!$bundle->cdn && $this->publisher !== null && !empty($bundle->sourcePath)) {
            [$bundle->basePath, $bundle->baseUrl] = $this->publisher->publish($bundle);
        }

        return $bundle;
    }
}
