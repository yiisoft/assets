<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use RuntimeException;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\Exception\InvalidConfigException;

use function in_array;
use function is_array;

/**
 * `AssetManager` manages asset bundle configuration and loading.
 *
 * @psalm-type CssFile = array{0: string, ...}|array{0: string, 1: int, ...}
 * @psalm-type CssString = array{0: mixed, ...}|array{0: string, 1: int, ...}
 * @psalm-type JsFile = array{0: string, ...}|array{0: string, 1: int, ...}
 * @psalm-type JsString = array{0: mixed, ...}|array{0: string, 1: int, ...}
 * @psalm-type JsVar = array{0:string,1:mixed,2?:int}
 * @psalm-type CustomizedBundles = array<string, AssetBundle|array<string, mixed>|false>
 */
final class AssetManager
{
    /**
     * @var AssetBundle[] list of the registered asset bundles.
     * The keys are the bundle names, and the values are the registered {@see AssetBundle} objects.
     *
     * {@see registerAssetBundle()}
     *
     * @psalm-var array<string, AssetBundle>
     */
    private array $registeredBundles = [];

    /**
     * @var true[] List of the asset bundles in register process. Use for detect circular dependency.
     * @psalm-var array<string, true>
     */
    private array $bundlesInRegisterProcess = [];

    /**
     * @var AssetBundle[]
     * @psalm-var array<string, AssetBundle>
     */
    private array $loadedBundles = [];

    /**
     * @var AssetBundle[]
     * @psalm-var array<string, AssetBundle>
     */
    private array $dummyBundles = [];

    private ?AssetPublisherInterface $publisher = null;
    private AssetRegistrar $registrar;

    /**
     * @param Aliases $aliases The aliases instance.
     * @param AssetLoaderInterface $loader The loader instance.
     * @param string[] $allowedBundleNames List of names of allowed asset bundles. If the array is empty, then any
     * asset bundles are allowed. If the names of allowed asset bundles were specified, only these asset bundles
     * or their dependencies can be registered {@see register()} and obtained {@see getBundle()}. Also, specifying
     * names allows to export {@see export()} asset bundles automatically without first registering them manually.
     * @param array $customizedBundles The asset bundle configurations. Provided to customize asset bundles.
     * When a bundle is being loaded by {@see getBundle()}, if it has a corresponding configuration specified
     * here, the configuration will be applied to the bundle. The array keys are the asset class bundle names
     * (without leading backslash). If a value is false, it means the corresponding asset bundle is disabled
     * and {@see getBundle()} should return an instance of the specified asset bundle with empty property values.
     *
     * @psalm-param CustomizedBundles $customizedBundles
     */
    public function __construct(
        Aliases $aliases,
        private AssetLoaderInterface $loader,
        private readonly array $allowedBundleNames = [],
        private array $customizedBundles = []
    ) {
        $this->registrar = new AssetRegistrar($aliases, $this->loader);
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
        if (!empty($this->allowedBundleNames)) {
            $this->checkAllowedBundleName($name);
        }

        $bundle = $this->loadBundle($name);
        $bundle = $this->publishBundle($bundle);

        return clone $bundle;
    }

    /**
     * Returns the actual URL for the specified asset.
     *
     * @param string $name The asset bundle name.
     * @param string $path The asset path.
     *
     * @throws InvalidConfigException If asset files are not found.
     *
     * @return string The actual URL for the specified asset.
     *
     * @deprecated Use {@see getUrl()} instead.
     */
    public function getAssetUrl(string $name, string $path): string
    {
        return $this->getUrl($name, $path);
    }

    /**
     * Returns the actual URL for the specified asset.
     *
     * @param string $name The asset bundle name.
     * @param string $path The asset path.
     *
     * @throws InvalidConfigException If asset files are not found.
     *
     * @return string The actual URL for the specified asset.
     */
    public function getUrl(string $name, string $path): string
    {
        return $this->loader->getAssetUrl($this->getBundle($name), $path);
    }

    /**
     * @return array Config array of CSS files.
     *
     * @psalm-return CssFile[]
     */
    public function getCssFiles(): array
    {
        return $this->registrar->getCssFiles();
    }

    /**
     * @return array CSS blocks.
     *
     * @psalm-return CssString[]
     */
    public function getCssStrings(): array
    {
        return $this->registrar->getCssStrings();
    }

    /**
     * @return array Config array of JavaScript files.
     *
     * @psalm-return JsFile[]
     */
    public function getJsFiles(): array
    {
        return $this->registrar->getJsFiles();
    }

    /**
     * @return array JavaScript code blocks.
     *
     * @psalm-return JsString[]
     */
    public function getJsStrings(): array
    {
        return $this->registrar->getJsStrings();
    }

    /**
     * @return array JavaScript variables.
     *
     * @psalm-return list<JsVar>
     */
    public function getJsVars(): array
    {
        return $this->registrar->getJsVars();
    }

    /**
     * Returns a new instance with the specified converter.
     */
    public function withConverter(AssetConverterInterface $converter): self
    {
        $new = clone $this;
        $new->registrar = $new->registrar->withConverter($converter);
        return $new;
    }

    /**
     * Returns a new instance with the specified loader.
     */
    public function withLoader(AssetLoaderInterface $loader): self
    {
        $new = clone $this;
        $new->loader = $loader;
        $new->registrar = $new->registrar->withLoader($new->loader);
        return $new;
    }

    /**
     * Returns a new instance with the specified publisher.
     */
    public function withPublisher(AssetPublisherInterface $publisher): self
    {
        $new = clone $this;
        $new->publisher = $publisher;
        return $new;
    }

    /**
     * Exports registered asset bundles.
     *
     * When using the allowed asset bundles, the export result will always be the same,
     * since the asset bundles are registered before the export. If do not use the allowed asset bundles mode,
     * must register {@see register()} all the required asset bundles before exporting.
     *
     * @param AssetExporterInterface $exporter The exporter instance.
     *
     * @throws InvalidConfigException If an error occurs during registration when using allowed asset bundles.
     * @throws RuntimeException If no asset bundles were registered or an error occurred during the export.
     */
    public function export(AssetExporterInterface $exporter): void
    {
        if (!empty($this->allowedBundleNames)) {
            $this->registerAllAllowed();
        }

        if (empty($this->registeredBundles)) {
            throw new RuntimeException('Not a single asset bundle was registered.');
        }

        $exporter->export($this->registeredBundles);
    }

    /**
     * Registers asset bundle by name.
     *
     * @param string $name The class name of the asset bundle (without the leading backslash).
     * @param int|null $jsPosition {@see AssetBundle::$jsPosition}
     * @param int|null $cssPosition {@see AssetBundle::$cssPosition}
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function register(string $name, ?int $jsPosition = null, ?int $cssPosition = null): void
    {
        if (!empty($this->allowedBundleNames)) {
            $this->checkAllowedBundleName($name);
        }

        $this->registerAssetBundle($name, $jsPosition, $cssPosition);
        $this->registerFiles($name);
    }

    /**
     * Registers an asset bundle by name with custom configuration.
     *
     * This method is similar to {@see register()}, except that it allows you to customize the asset bundle
     * configuration before it is registered. It also supports registering asset bundles with virtual namespaces,
     * which means that the corresponding asset file may not physically exist.
     *
     * @param string $bundleName The class name of the asset bundle (without the leading backslash).
     * @param array $bundleConfig The customized asset bundle configuration.
     *
     * @psalm-param array<string, mixed> $bundleConfig
     */
    public function registerCustomized(string $bundleName, array $bundleConfig): void
    {
        $this->customizedBundles[$bundleName] = $bundleConfig;

        $this->register($bundleName);
    }

    /**
     * Registers many asset bundles by names.
     *
     * @param string[] $names The many class names of the asset bundles (without the leading backslash).
     * @param int|null $jsPosition {@see AssetBundle::$jsPosition}
     * @param int|null $cssPosition {@see AssetBundle::$cssPosition}
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function registerMany(array $names, ?int $jsPosition = null, ?int $cssPosition = null): void
    {
        foreach ($names as $name) {
            $this->register($name, $jsPosition, $cssPosition);
        }
    }

    /**
     * Registers all allowed asset bundles.
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function registerAllAllowed(): void
    {
        if (empty($this->allowedBundleNames)) {
            throw new RuntimeException('The allowed names of the asset bundles were not set.');
        }

        foreach ($this->allowedBundleNames as $name) {
            $this->registerAssetBundle($name);
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
     * Registers the named asset bundle.
     *
     * All dependent asset bundles will be registered.
     *
     * @param string $name The class name of the asset bundle (without the leading backslash).
     * @param int|null $jsPosition If set, this forces a minimum position for javascript files.
     * This will adjust depending assets javascript file position or fail if requirement can not be met.
     * If this is null, asset bundles position settings will not be changed.
     *
     * {@see AssetRegistrar::registerJsFile()} For more details on javascript position.
     *
     * @throws InvalidConfigException If the asset or the asset file paths to be published does not exist.
     * @throws RuntimeException If the asset bundle does not exist or a circular dependency is detected.
     */
    private function registerAssetBundle(string $name, ?int $jsPosition = null, ?int $cssPosition = null): void
    {
        if (isset($this->bundlesInRegisterProcess[$name])) {
            throw new RuntimeException("A circular dependency is detected for bundle \"{$name}\".");
        }

        if (!isset($this->registeredBundles[$name])) {
            $bundle = $this->publishBundle($this->loadBundle($name));

            $this->bundlesInRegisterProcess[$name] = true;

            /** @var string $dep */
            foreach ($bundle->depends as $dep) {
                $this->registerAssetBundle($dep, $bundle->jsPosition, $bundle->cssPosition);
            }

            unset(
                $this->bundlesInRegisterProcess[$name], // Remove bundle from list bundles in register process
                $this->registeredBundles[$name], // Remove bundle from registered bundles for add him to end of list in next code
            );

            $this->registeredBundles[$name] = $bundle;
        } else {
            $bundle = $this->registeredBundles[$name];
        }

        if ($jsPosition !== null || $cssPosition !== null) {
            if ($jsPosition !== null) {
                if ($bundle->jsPosition === null) {
                    $bundle->jsPosition = $jsPosition;
                } elseif ($bundle->jsPosition > $jsPosition) {
                    throw new RuntimeException(
                        "An asset bundle that depends on \"{$name}\" has a higher JavaScript file " .
                        "position configured than \"{$name}\"."
                    );
                }
            }

            if ($cssPosition !== null) {
                if ($bundle->cssPosition === null) {
                    $bundle->cssPosition = $cssPosition;
                } elseif ($bundle->cssPosition > $cssPosition) {
                    throw new RuntimeException(
                        "An asset bundle that depends on \"{$name}\" has a higher CSS file " .
                        "position configured than \"{$name}\"."
                    );
                }
            }

            // update position for all dependencies
            /** @var string $dep */
            foreach ($bundle->depends as $dep) {
                $this->registerAssetBundle($dep, $bundle->jsPosition, $bundle->cssPosition);
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
        $bundle = $this->registeredBundles[$bundleName];

        /** @var string $dep */
        foreach ($bundle->depends as $dep) {
            $this->registerFiles($dep);
        }

        $this->registrar->register($bundle);
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
            $this->validateAssetBundleClass($name);
            return $this->loadedBundles[$name] = $this->loader->loadBundle($name);
        }

        if ($this->customizedBundles[$name] instanceof AssetBundle) {
            return $this->loadedBundles[$name] = $this->customizedBundles[$name];
        }

        if (is_array($this->customizedBundles[$name])) {
            return $this->loadedBundles[$name] = $this->loader->loadBundle($name, $this->customizedBundles[$name]);
        }

        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if ($this->customizedBundles[$name] === false) {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            return $this->dummyBundles[$name] ??= $this->loader->loadBundle($name, (array) (new AssetBundle()));
        }

        // @phpstan-ignore deadCode.unreachable
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

    /**
     * Checks whether asset bundle are allowed by name {@see $allowedBundleNames}.
     *
     * @param string $name The asset bundle name to check.
     *
     * @throws InvalidConfigException For invalid asset bundle configuration.
     * @throws RuntimeException If The asset bundle name is not allowed.
     */
    public function checkAllowedBundleName(string $name): void
    {
        if (isset($this->loadedBundles[$name]) || in_array($name, $this->allowedBundleNames, true)) {
            return;
        }

        foreach ($this->allowedBundleNames as $bundleName) {
            if ($this->isAllowedBundleDependencies($name, $this->loadBundle($bundleName))) {
                return;
            }
        }

        throw new RuntimeException("The \"{$name}\" asset bundle is not allowed.");
    }

    /**
     * Recursively checks whether the asset bundle name is allowed in dependencies.
     *
     * @param string $name The asset bundle name to check.
     * @param AssetBundle $bundle The asset bundle to check.
     *
     * @throws InvalidConfigException For invalid asset bundle configuration.
     *
     * @return bool Whether the asset bundle name is allowed in dependencies.
     */
    private function isAllowedBundleDependencies(string $name, AssetBundle $bundle): bool
    {
        /** @var string $depend */
        foreach ($bundle->depends as $depend) {
            if ($name === $depend || $this->isAllowedBundleDependencies($name, $this->loadBundle($depend))) {
                return true;
            }
        }

        return false;
    }

    private function validateAssetBundleClass(string $class): void
    {
        if (!class_exists($class)) {
            throw new InvalidConfigException("The \"{$class}\" asset bundle class does not exist.");
        }
    }
}
