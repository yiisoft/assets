<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

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
}
