<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Psr\Log\LoggerInterface;
use Yiisoft\Assets\Exception\InvalidConfigException;

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

    private AssetConverterInterface $converter;
    private AssetPublisher $publisher;

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

    /**
     * @var array the registered CSS files.
     *
     * {@see registerCssFile()}
     */
    private array $cssFiles = [];

    /**
     * @var array $dummyBundles
     */
    private array $dummyBundles;

    /**
     * @var array the registered JS files.
     *
     * {@see registerJsFile()}
     */
    private array $jsFiles = [];

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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
     * @return AssetBundle the asset bundle instance
     *
     * @throws InvalidConfigException
     */
    public function getBundle(string $name): AssetBundle
    {
        if (!isset($this->bundles[$name])) {
            return $this->bundles[$name] = $this->publisher->loadBundle($name, []);
        }

        if ($this->bundles[$name] instanceof AssetBundle) {
            return $this->bundles[$name];
        }

        if (\is_array($this->bundles[$name])) {
            return $this->bundles[$name] = $this->publisher->loadBundle($name, $this->bundles[$name]);
        }

        if ($this->bundles[$name] === false) {
            return $this->loadDummyBundle($name);
        }

        throw new InvalidConfigException("Invalid asset bundle configuration: $name");
    }

    public function getConverter(): AssetConverterInterface
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

    public function getPublisher(): AssetPublisherInterface
    {
        return $this->publisher;
    }

    /**
     * This property is provided to customize asset bundles.
     *
     * @param array $value
     *
     * @return void
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
     * @param AssetConverterInterface $value the asset converter. This can be eitheran object implementing the
     * {@see AssetConverterInterface}, or a configuration array that can be used to create the asset converter object.
     */
    public function setConverter(AssetConverterInterface $value): void
    {
        $this->converter = $value;
    }

    /**
     * AssetPublisher component.
     *
     * @param AssetPublisher $value
     *
     * @return void
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
     * @param integer|null $position
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
     */
    public function registerJsFile(string $url, array $options = [], string $key = null): void
    {
        $key = $key ?: $url;

        if (!\array_key_exists('position', $options)) {
            $options = array_merge(['position' => 3], $options);
        }

        $this->jsFiles[$key]['url'] = $url;
        $this->jsFiles[$key]['attributes'] = $options;
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
            if (\is_array($css)) {
                $file = \array_shift($css);
                if (AssetUtil::isRelative($file)) {
                    $css = \array_merge($bundle->cssOptions, $css);

                    if (is_file("$bundle->basePath/$file")) {
                        \array_unshift($css, $this->converter->convert(
                            $file,
                            $bundle->basePath,
                            $bundle->converterOptions,
                            $bundle->converterLoadPath
                        ));

                        $bundle->css[$i] = $css;
                    }
                }
            } elseif (AssetUtil::isRelative($css)) {
                if (is_file("$bundle->basePath/$css")) {
                    $bundle->css[$i] = $this->converter->convert(
                        $css,
                        $bundle->basePath,
                        $bundle->converterOptions,
                        $bundle->converterLoadPath
                    );
                }
            }
        }

        return $bundle;
    }

    /**
     * Convert files CoffeScript, TypeScript and other formats to JavaScript.
     *
     * @param AssetBundle $bundle
     *
     * @return AssetBundle
     */
    private function convertJs(AssetBundle $bundle): AssetBundle
    {
        foreach ($bundle->js as $i => $js) {
            if (\is_array($js)) {
                $file = \array_shift($js);
                if (AssetUtil::isRelative($file)) {
                    $js = \array_merge($bundle->jsOptions, $js);

                    if (is_file("$bundle->basePath/$file")) {
                        \array_unshift($js, $this->converter->convert(
                            $file,
                            $bundle->basePath,
                            $bundle->converterOptions
                        ));

                        $bundle->js[$i] = $js;
                    }
                }
            } elseif (AssetUtil::isRelative($js)) {
                if (is_file("$bundle->basePath/$js")) {
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
     * @return AssetBundle the registered asset bundle instance
     * @throws InvalidConfigException
     *
     * @throws \RuntimeException if the asset bundle does not exist or a circular dependency is detected
     */
    private function registerAssetBundle(string $name, ?int $position = null): AssetBundle
    {
        if (!isset($this->assetBundles[$name])) {
            $bundle = $this->getBundle($name);

            $this->assetBundles[$name] = false;

            // register dependencies
            $pos = $bundle->jsOptions['position'] ?? null;

            foreach ($bundle->depends as $dep) {
                $this->registerAssetBundle($dep, $pos);
            }

            $this->assetBundles[$name] = $bundle;
        } elseif ($this->assetBundles[$name] === false) {
            throw new \RuntimeException("A circular dependency is detected for bundle '$name'.");
        } else {
            $bundle = $this->assetBundles[$name];
        }

        if ($position !== null) {
            $pos = $bundle->jsOptions['position'] ?? null;

            if ($pos === null) {
                $bundle->jsOptions['position'] = $pos = $position;
            } elseif ($pos > $position) {
                throw new \RuntimeException(
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
     * @throws InvalidConfigException
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
     * @return void
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
     */
    private function registerAssetFiles(AssetBundle $bundle): void
    {
        if (isset($bundle->basePath, $bundle->baseUrl) && !empty($this->converter)) {
            $this->convertCss($bundle);
            $this->convertJs($bundle);
        }

        foreach ($bundle->js as $js) {
            if (\is_array($js)) {
                $file = array_shift($js);
                $options = array_merge($bundle->jsOptions, $js);
                $this->registerJsFile($this->publisher->getAssetUrl($bundle, $file), $options);
            } elseif ($js !== null) {
                $this->registerJsFile($this->publisher->getAssetUrl($bundle, $js), $bundle->jsOptions);
            }
        }

        foreach ($bundle->css as $css) {
            if (\is_array($css)) {
                $file = array_shift($css);
                $options = array_merge($bundle->cssOptions, $css);
                $this->registerCssFile($this->publisher->getAssetUrl($bundle, $file), $options);
            } elseif ($css !== null) {
                $this->registerCssFile($this->publisher->getAssetUrl($bundle, $css), $bundle->cssOptions);
            }
        }
    }
}
