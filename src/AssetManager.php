<?php
declare(strict_types=1);

namespace Yiisoft\Assets;

use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\Exception\InvalidConfigException;

/**
 * AssetManager manages asset bundle configuration and loading.
 *
 * AssetManager is configured in config/web.php. You can access that instance via $container->get(AssetManager::class).
 *
 * You can modify its configuration by adding an array to your application config under `components` as shown in the
 * following example:
 *
 * ```php
 * AssetManager::class => function (ContainerInterface $container) {
 *     $aliases = $container->get(Aliases::class);
 *     $assetConverterInterface = $container->get(AssetConverterInterface::class);
 *     $fileSystem = $container->get(Filesystem::class);
 *     $logger = $container->get(LoggerInterface::class);
 *
 *     $assetManager = new AssetManager($fileSystem, $logger);
 *
 *     $assetManager->setBasePath($aliases->get('@basePath'));
 *     $assetManager->setBaseUrl($aliases->get('@baseUrl'));
 *     $assetManager->setConverter($assetConverterInterface);
 *
 *     return $assetManager;
 * },
 * ```
 */
final class AssetManager
{
    private Aliases $aliases;

    /**
     * @var array AssetBundle[] list of the registered asset bundles. The keys are the bundle names, and the values
     * are the registered {@see AssetBundle} objects.
     *
     * {@see registerAssetBundle()}
     */
    private array $assetBundles = [];

    /**
     * @var bool whether to append a timestamp to the URL of every published asset. When this is true, the URL of a
     * published asset may look like `/path/to/asset?v=timestamp`, where `timestamp` is the last modification time of
     * the published asset file. You normally would want to set this property to true when you have enabled HTTP caching
     * for assets, because it allows you to bust caching when the assets are updated.
     */
    private bool $appendTimestamp = false;

    /**
     * @var array mapping from source asset files (keys) to target asset files (values).
     *
     * This property is provided to support fixing incorrect asset file paths in some asset bundles. When an asset
     * bundle is registered with a view, each relative asset file in its {@see AssetBundle::css|css} and
     * {@see AssetBundle::js|js} arrays will be examined against this map. If any of the keys is found to be the last
     * part of an asset file (which is prefixed with {@see AssetBundle::sourcePath} if available), the corresponding
     * value will replace the asset and be registered with the view. For example, an asset file `my/path/to/jquery.js`
     * matches a key `jquery.js`.
     *
     * Note that the target asset files should be absolute URLs, domain relative URLs (starting from '/') or paths
     * relative to {@see baseUrl} and {@see basePath}.
     *
     * In the following example, any assets ending with `jquery.min.js` will be replaced with `jquery/dist/jquery.js`
     * which is relative to {@see baseUrl} and {@see basePath}.
     *
     * ```php
     * [
     *     'jquery.min.js' => 'jquery/dist/jquery.js',
     * ]
     * ```
     */
    private array $assetMap = [];

    /**
     * @var AssetPublisher published assets
     */
    private AssetPublisher $publish;

    /**
     * @var string|null the root directory storing the published asset files.
     */
    private ?string $basePath = null;

    /**
     * @var string|null the base URL through which the published asset files can be accessed.
     */
    private ?string $baseUrl = null;

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
     * AssetConverter component.
     *
     * @var AssetConverterInterface $converter
     */
    private AssetConverterInterface $converter;

    /**
     * @var array the registered CSS files.
     *
     * {@see registerCssFile()}
     */
    private array $cssFiles = [];

    /**
     * @var int the permission to be set for newly generated asset directories. This value will be used by PHP chmod()
     * function. No umask will be applied. Defaults to 0775, meaning the directory is read-writable by owner
     * and group, but read-only for other users.
     */
    private int $dirMode = 0775;

    /**
     * @var array $dummyBundles
     */
    private array $dummyBundles;

    /**
     * @var int the permission to be set for newly published asset files. This value will be used by PHP chmod()
     * function. No umask will be applied. If not set, the permission will be determined by the current
     * environment.
     */
    private int $fileMode = 0755;

    /**
     * @var bool whether the directory being published should be copied even if it is found in the target directory.
     * This option is used only when publishing a directory. You may want to set this to be `true` during the
     * development stage to make sure the published directory is always up-to-date. Do not set this to true
     * on production servers as it will significantly degrade the performance.
     */
    private bool $forceCopy = false;

    /**
     * @var callable a callback that will be called to produce hash for asset directory generation. The signature of the
     * callback should be as follows:
     *
     * ```
     * function ($path)
     * ```
     *
     * where `$path` is the asset path. Note that the `$path` can be either directory where the asset files reside or a
     * single file. For a CSS file that uses relative path in `url()`, the hash implementation should use the directory
     * path of the file instead of the file path to include the relative asset files in the copying.
     *
     * If this is not set, the asset manager will use the default CRC32 and filemtime in the `hash` method.
     *
     * Example of an implementation using MD4 hash:
     *
     * ```php
     * function ($path) {
     *     return hash('md4', $path);
     * }
     * ```
     */
    private $hashCallback;

    /**
     * @var bool whether to use symbolic link to publish asset files. Defaults to false, meaning asset files are copied
     * to {@see basePath}. Using symbolic links has the benefit that the published assets will always be
     * consistent with the source assets and there is no copy operation required. This is especially useful
     * during development.
     *
     * However, there are special requirements for hosting environments in order to use symbolic links. In particular,
     * symbolic links are supported only on Linux/Unix, and Windows Vista/2008 or greater.
     *
     * Moreover, some Web servers need to be properly configured so that the linked assets are accessible to Web users.
     * For example, for Apache Web server, the following configuration directive should be added for the Web folder:
     *
     * ```apache
     * Options FollowSymLinks
     * ```
     */
    private bool $linkAssets = false;

    /**
     * @var array the registered JS files.
     *
     * {@see registerJsFile()}
     */
    private array $jsFiles = [];

    private LoggerInterface $logger;

    public function __construct(Aliases $aliases, LoggerInterface $logger)
    {
        $this->aliases = $aliases;
        $this->logger = $logger;
        $this->publish = $this->getPublish();
    }

    public function getAliases(): Aliases
    {
        return $this->aliases;
    }

    public function getAssetMap(): array
    {
        return $this->assetMap;
    }

    public function getAppendTimestamp(): bool
    {
        return $this->appendTimestamp;
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

    public function getBasePath(): ?string
    {
        if (!empty($this->basePath)) {
            $this->basePath = $this->aliases->get($this->basePath);
        }

        return $this->basePath;
    }

    public function getBaseUrl(): ?string
    {
        if (!empty($this->baseUrl)) {
            $this->baseUrl = $this->aliases->get($this->baseUrl);
        }

        return $this->baseUrl;
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
     * @throws \InvalidArgumentException
     * @throws InvalidConfigException
     */
    public function getBundle(string $name): AssetBundle
    {
        if (!isset($this->bundles[$name])) {
            return $this->bundles[$name] = $this->publish->loadBundle($name, []);
        }

        if ($this->bundles[$name] instanceof AssetBundle) {
            return $this->bundles[$name];
        }

        if (\is_array($this->bundles[$name])) {
            return $this->bundles[$name] = $this->publish->loadBundle($name, $this->bundles[$name]);
        }

        if ($this->bundles[$name] === false) {
            return $this->loadDummyBundle($name);
        }

        throw new \InvalidArgumentException("Invalid asset bundle configuration: $name");
    }

    /**
     * Returns the asset converter.
     *
     * @return AssetConverterInterface the asset converter.
     */
    public function getConverter(): AssetConverterInterface
    {
        return $this->converter;
    }

    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }

    public function getDirMode(): int
    {
        return $this->dirMode;
    }

    public function getFileMode(): int
    {
        return $this->fileMode;
    }

    public function getForceCopy(): bool
    {
        return $this->forceCopy;
    }

    public function getJsFiles(): array
    {
        return $this->jsFiles;
    }

    public function getLinkAssets(): bool
    {
        return $this->linkAssets;
    }

    public function getHashCallback(): ?callable
    {
        return $this->hashCallback;
    }

    public function getPublish(): AssetPublisher
    {
        if (empty($this->publish)) {
            $this->publish = new AssetPublisher($this);
        }

        return $this->publish;
    }

    public function getPublishedPath(?string $sourcePath): ?string
    {
        return $this->publish->getPublishedPath($sourcePath);
    }

    public function getPublishedUrl(?string $sourcePath): ?string
    {
        return $this->publish->getPublishedUrl($sourcePath);
    }

    /**
     * Set appendTimestamp.
     *
     * @param bool $value
     *
     * @return void
     *
     * {@see appendTimestamp}
     */
    public function setAppendTimestamp(bool $value): void
    {
        $this->appendTimestamp = $value;
    }

    /**
     * Set assetMap.
     *
     * @param array $value
     *
     * @return void
     *
     * {@see assetMap}
     */
    public function setAssetMap(array $value): void
    {
        $this->assetMap = $value;
    }

    /**
     * Set basePath.
     *
     * @param string|null $value
     *
     * @return void
     *
     * {@see basePath}
     */
    public function setBasePath(?string $value): void
    {
        $this->basePath = $value;
    }

    /**
     * Set baseUrl.
     *
     * @param string|null $value
     *
     * @return void
     *
     * {@see baseUrl}
     */
    public function setBaseUrl(?string $value): void
    {
        $this->baseUrl = $value;
    }


    /**
     * Set bundles.
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
     * Sets the asset converter.
     *
     * @param AssetConverterInterface $value the asset converter. This can be eitheran object implementing the
     * {@see AssetConverterInterface}, or a configuration array that can be used
     * to create the asset converter object.
     */
    public function setConverter(AssetConverterInterface $value): void
    {
        $this->converter = $value;
    }

    /**
     * Set hashCallback.
     *
     * @param callable $value
     *
     * @return void
     *
     * {@see hashCallback}
     */
    public function setHashCallback(callable $value): void
    {
        $this->hashCallback = $value;
    }

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
     * @param array $options the HTML attributes for the link tag. Please refer to {@see \Yiisoft\Html\Html::cssFile()}
     * for the supported options. The following options are specially handled and are not treated as HTML
     * attributes:
     *
     *   - `depends`: array, specifies the names of the asset bundles that this CSS file depends on.
     *
     * @param string $key the key that identifies the CSS script file. If null, it will use $url as the key. If two CSS
     * files are registered with the same key, the latter will overwrite the former.
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
     * - `depends`: array, specifies the names of the asset bundles that this JS file depends on.
     * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
     *     * [[POS_HEAD]]: in the head section
     *     * [[POS_BEGIN]]: at the beginning of the body section
     *     * [[POS_END]]: at the end of the body section. This is the default value.
     *
     * Please refer to {@see \Yiisoft\Html\Html::jsFile()} for other supported options.
     *
     * @param string $key the key that identifies the JS script file. If null, it will use $url as the key. If two JS
     * files are registered with the same key at the same position, the latter will overwrite the former.
     * Note that position option takes precedence, thus files registered with the same key, but different
     * position option will not override each other.
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
     * @param string $name AssetBunle name class.
     *
     * @return AssetBundle
     * @throws InvalidConfigException
     */
    private function loadDummyBundle(string $name): AssetBundle
    {
        if (!isset($this->dummyBundles[$name])) {
            $this->dummyBundles[$name] = $this->publish->loadBundle($name, [
                'sourcePath' => null,
                'js' => [],
                'css' => [],
                'depends' => [],
            ]);
        }

        return $this->dummyBundles[$name];
    }

    private function registerFiles(string $name): void
    {
        if (!isset($this->assetBundles[$name])) {
            return;
        }

        $bundle = $this->assetBundles[$name];

        foreach ($bundle->depends as $dep) {
            $this->registerFiles($dep);
        }

        $this->publish->registerAssetFiles($bundle);
    }
}
