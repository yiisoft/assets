<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use JsonException;
use RuntimeException;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\Exception\InvalidConfigException;

use function dirname;
use function file_put_contents;
use function json_encode;
use function is_array;
use function is_dir;
use function is_writable;

/**
 * AssetExporter provides JSON export and custom PHP callable export of the asset bundles.
 */
final class AssetExporter
{
    private Aliases $aliases;

    /**
     * @var array The asset bundle configurations. This property is provided to customize asset bundles.
     * When a bundle is being built by {@see buildBundle()}, if it has a corresponding configuration
     * specified here, the configuration will be applied to the bundle.
     *
     * The array keys are the asset bundle names, usually the asset bundle class name (without leading backslash).
     * The array values are an array of asset bundle instance configurations for changing default values.
     * If the value is null or an empty array, the default values of each asset bundle will be used.
     * Also, the array value can be an instance of a class that extends the {@see AssetBundle} class.
     *
     * All dependent asset bundles will be exported, so you don't need to specify dependencies
     * if their values don't need to be customized.
     */
    private array $bundles;

    /**
     * @var AssetBundle[] The array asset bundle instances {@see buildBundles()}.
     * The keys are the asset bundle names and the values are {@see AssetBundle} instances.
     */
    private array $builtBundles = [];

    /**
     * @param array $bundles The asset bundles to export {@see bundles}.
     * @param Aliases $aliases The aliases instance.
     */
    public function __construct(array $bundles, Aliases $aliases)
    {
        $this->bundles = $bundles;
        $this->aliases = $aliases;
    }

    /**
     * Exports asset bundles with the values of all properties using a custom PHP callable.
     *
     * @param callable $export The custom PHP callable.
     *
     * PHP callable supports the following signature:
     *
     * ```php
     * function (array $assets, Aliases $aliases): void;
     * ```
     *
     * Learn more about the `$assets` array {@see builtBundles}.
     *
     * @throws InvalidConfigException If the asset bundle configurations are invalid.
     */
    public function export(callable $export): void
    {
        $export($this->buildBundles(), $this->aliases);
    }

    /**
     * Exports asset bundles with the values of all properties {@see AssetBundle::jsonSerialize()} to a JSON file.
     *
     * @param string $targetFile The target JSON file.
     *
     * @throws InvalidConfigException If the asset bundle configurations are invalid.
     * @throws JsonException If an error occurred during JSON encoding of asset bundles.
     * @throws RuntimeException If an error occurred while writing to the JSON file.
     */
    public function exportToJsonFile(string $targetFile): void
    {
        $targetFile = $this->aliases->get($targetFile);
        $targetDirectory = dirname($targetFile);

        if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
            throw new RuntimeException("Target directory \"{$targetDirectory}\" does not exist or is not writable.");
        }

        if (file_put_contents($targetFile, $this->exportToJson(), LOCK_EX) === false) {
            throw new RuntimeException("An error occurred while writing to the \"{$targetFile}\" file.");
        }
    }

    /**
     * Exports asset bundles with the values of all properties {@see AssetBundle::jsonSerialize()} to a JSON data.
     *
     * @throws InvalidConfigException If the asset bundle configurations are invalid.
     * @throws JsonException If an error occurred during JSON encoding of asset bundles.
     */
    public function exportToJson(): string
    {
        return json_encode(
            $this->buildBundles(),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * Builds an array asset bundle instances {@see builtBundles}. All dependent asset bundles will be built.
     *
     * @throws InvalidConfigException If the asset bundle configurations are invalid.
     *
     * @return AssetBundle[] The keys are asset bundle names, and values are {@see AssetBundle} instances.
     */
    private function buildBundles(): array
    {
        if (!empty($this->builtBundles)) {
            return $this->builtBundles;
        }

        foreach ($this->bundles as $name => $config) {
            $this->builtBundles[$name] = $this->buildBundle($name, $config);
        }

        foreach ($this->builtBundles as $bundle) {
            $this->buildBundleDependencies($bundle);
        }

        return $this->builtBundles;
    }

    /**
     * Builds asset bundle dependencies.
     *
     * If need to customize the configuration of a dependent asset bundle, specify it in the {@see bundles}.
     *
     * @param AssetBundle $bundle The asset bundle instance.
     *
     * @throws InvalidConfigException If the asset bundle configurations are invalid.
     */
    private function buildBundleDependencies(AssetBundle $bundle): void
    {
        foreach ($bundle->depends as $depend) {
            if (isset($this->builtBundles[$depend])) {
                continue;
            }

            $this->builtBundles[$depend] = $this->buildBundle($depend);
            $this->buildBundleDependencies($this->builtBundles[$depend]);
        }
    }

    /**
     * Builds a new asset bundle instance.
     *
     * @param string $name The asset bundle name.
     * @param mixed $config The asset bundle instance configuration.
     *
     * @throws InvalidConfigException If the asset bundle configurations are invalid.
     *
     * @return AssetBundle The asset bundle instance.
     */
    private function buildBundle(string $name, $config = null): AssetBundle
    {
        if ($config === null) {
            return $this->resolvePathAliases(AssetUtil::createAsset($name));
        }

        if ($config instanceof AssetBundle) {
            return $this->resolvePathAliases($config);
        }

        if (is_array($config)) {
            return $this->resolvePathAliases(AssetUtil::createAsset($name, $config));
        }

        throw new InvalidConfigException("Invalid configuration of the \"{$name}\" asset bundle.");
    }

    /**
     * Resolve path aliases for {@see AssetBundle} properties:
     *
     * - {@see AssetBundle::basePath}
     * - {@see AssetBundle::baseUrl}
     * - {@see AssetBundle::sourcePath}
     *
     * @param AssetBundle $bundle The asset bundle instance to resolving path aliases.
     *
     * @return AssetBundle The asset bundle instance with resolved paths.
     */
    private function resolvePathAliases(AssetBundle $bundle): AssetBundle
    {
        if ($bundle->basePath !== null) {
            $bundle->basePath = $this->aliases->get($bundle->basePath);
        }

        if ($bundle->baseUrl !== null) {
            $bundle->baseUrl = $this->aliases->get($bundle->baseUrl);
        }

        if ($bundle->sourcePath !== null) {
            $bundle->sourcePath = $this->aliases->get($bundle->sourcePath);
        }

        return $bundle;
    }
}
