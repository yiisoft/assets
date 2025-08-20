<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use RuntimeException;
use Yiisoft\Aliases\Aliases;

use function array_merge;
use function array_unique;
use function dirname;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_subclass_of;
use function is_writable;
use function mb_strlen;
use function strncmp;
use function substr_compare;

/**
 * `AssetUtil` shared functions.
 *
 * @psalm-import-type CssFile from AssetManager
 * @psalm-import-type JsFile from AssetManager
 */
final class AssetUtil
{
    /**
     * Creates a new asset bundle instance.
     *
     * If the name is a class name, an instance of this class will be created,
     * otherwise an instance of the {@see AssetBundle} will be created.
     *
     * @param string $name The asset bundle name. Usually the asset bundle class name (without leading backslash).
     * @param array $config The asset bundle instance configuration. If specified, it will be applied to the instance.
     *
     * @psalm-param array<string,mixed> $config
     *
     * @return AssetBundle The created asset bundle.
     */
    public static function createAsset(string $name, array $config = []): AssetBundle
    {
        /** @psalm-suppress UnsafeInstantiation */
        $bundle = is_subclass_of($name, AssetBundle::class) ? new $name() : new AssetBundle();

        foreach ($config as $property => $value) {
            $bundle->{$property} = $value;
        }

        return $bundle;
    }

    /**
     * Resolves the actual URL for the specified asset.
     *
     * @param AssetBundle $bundle The asset bundle which the asset file belongs to.
     * @param string $assetPath The asset path. This should be one of the assets listed
     * in {@see AssetBundle::$js} or {@see AssetBundle::$css}.
     * @param string[] $assetMap Mapping from source asset files (keys) to target asset files (values)
     * {@see AssetPublisher::$assetMap}.
     *
     * @psalm-param array<string, string> $assetMap
     *
     * @return string|null The actual URL for the specified asset, or null if there is no mapping.
     */
    public static function resolveAsset(AssetBundle $bundle, string $assetPath, array $assetMap): ?string
    {
        if (isset($assetMap[$assetPath])) {
            return $assetMap[$assetPath];
        }

        if (!empty($bundle->sourcePath) && self::isRelative($assetPath)) {
            $assetPath = $bundle->sourcePath . '/' . $assetPath;
        }

        $n = mb_strlen($assetPath, 'utf-8');

        foreach ($assetMap as $from => $to) {
            $n2 = mb_strlen($from, 'utf-8');
            if ($n2 <= $n && substr_compare($assetPath, $from, $n - $n2, $n2) === 0) {
                return $to;
            }
        }

        return null;
    }

    /**
     * Resolve path aliases for {@see AssetBundle} properties:
     *
     * - {@see AssetBundle::$basePath}
     * - {@see AssetBundle::$baseUrl}
     * - {@see AssetBundle::$sourcePath}
     *
     * @param AssetBundle $bundle The asset bundle instance to resolving path aliases.
     * @param Aliases $aliases The aliases instance to resolving path aliases.
     *
     * @return AssetBundle The asset bundle instance with resolved paths.
     */
    public static function resolvePathAliases(AssetBundle $bundle, Aliases $aliases): AssetBundle
    {
        if ($bundle->basePath !== null) {
            $bundle->basePath = $aliases->get($bundle->basePath);
        }

        if ($bundle->baseUrl !== null) {
            $bundle->baseUrl = $aliases->get($bundle->baseUrl);
        }

        if ($bundle->sourcePath !== null) {
            $bundle->sourcePath = $aliases->get($bundle->sourcePath);
        }

        return $bundle;
    }

    /**
     * Extracts the file paths to export from each asset bundle {@see AssetBundle::$export}.
     *
     * @param AssetBundle[] $bundles List of asset bundles.
     *
     * @return string[] Extracted file paths.
     */
    public static function extractFilePathsForExport(array $bundles): array
    {
        $filePaths = [];

        foreach ($bundles as $bundle) {
            if ($bundle->cdn || empty($bundle->sourcePath)) {
                continue;
            }

            if (!empty($bundle->export)) {
                foreach ($bundle->export as $filePath) {
                    /** @var string $filePath */
                    $filePaths[] = "{$bundle->sourcePath}/{$filePath}";
                }
                continue;
            }

            foreach (array_merge($bundle->css, $bundle->js) as $item) {
                /** @psalm-var CssFile|JsFile|string $item */
                $filePath = is_array($item) ? $item[0] : $item;
                $filePaths[] = "{$bundle->sourcePath}/{$filePath}";
            }
        }

        return array_unique($filePaths);
    }

    /**
     * Writes a string representation of asset bundles to the specified file.
     *
     * @param string $targetFile The full path to the target file.
     * @param string $bundles The string representation of asset bundles.
     *
     * @throws RuntimeException If an error occurred while writing to the file.
     */
    public static function exportToFile(string $targetFile, string $bundles): void
    {
        $targetDirectory = dirname($targetFile);

        if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
            throw new RuntimeException("Target directory \"{$targetDirectory}\" does not exist or is not writable.");
        }

        if (file_put_contents($targetFile, $bundles, LOCK_EX) === false) {
            throw new RuntimeException("An error occurred while writing to the \"{$targetFile}\" file.");
        }
    }

    /**
     * Returns a value indicating whether a URL is relative.
     *
     * A relative URL does not have host info part.
     *
     * @param string $url The URL to be checked.
     *
     * @return bool Whether the URL is relative.
     */
    public static function isRelative(string $url): bool
    {
        return strncmp($url, '//', 2) && !str_contains($url, '://');
    }
}
