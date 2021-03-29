<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Yiisoft\Aliases\Aliases;

use function is_subclass_of;
use function mb_strlen;
use function strncmp;
use function strpos;
use function substr_compare;

/**
 * AssetUtil shared functions.
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
     * @return AssetBundle The created asset bundle.
     *
     * @psalm-suppress UnsafeInstantiation
     */
    public static function createAsset(string $name, array $config = []): AssetBundle
    {
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
     * @param array $assetMap Mapping from source asset files (keys) to target asset files (values)
     * {@see AssetPublisher::$assetMap}.
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
     * - {@see AssetBundle::basePath}
     * - {@see AssetBundle::baseUrl}
     * - {@see AssetBundle::sourcePath}
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
        return strncmp($url, '//', 2) && strpos($url, '://') === false;
    }
}
