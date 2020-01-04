<?php
declare(strict_types=1);

namespace Yiisoft\Assets;

/**
 * AssetUtil shared functions.
 */
final class AssetUtil
{
    /**
     * Returns a value indicating whether a URL is relative.
     *
     * A relative URL does not have host info part.
     * @param string $url the URL to be checked
     * @return bool whether the URL is relative
     */
    public static function isRelative(string $url): bool
    {
        return strncmp($url, '//', 2) && strpos($url, '://') === false;
    }

    /**
     * @param AssetBundle $bundle
     * @param string $pathAsset
     * @param array $assetMap
     *
     * @return string|null
     */
    public static function resolveAsset(AssetBundle $bundle, string $pathAsset, array $assetMap): ?string
    {
        if (isset($assetMap[$pathAsset])) {
            return $assetMap[$pathAsset];
        }

        if (!empty($bundle->sourcePath) && static::isRelative($pathAsset)) {
            $pathAsset = $bundle->sourcePath . '/' . $pathAsset;
        }

        $n = mb_strlen($pathAsset, 'utf-8');

        foreach ($assetMap as $from => $to) {
            $n2 = mb_strlen($from, 'utf-8');
            if ($n2 <= $n && substr_compare($pathAsset, $from, $n - $n2, $n2) === 0) {
                return $to;
            }
        }

        return null;
    }
}
