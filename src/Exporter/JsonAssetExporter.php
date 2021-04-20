<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Exporter;

use JsonException;
use RuntimeException;
use Yiisoft\Assets\AssetExporterInterface;
use Yiisoft\Assets\AssetUtil;
use Yiisoft\Json\Json;

/**
 * Exports the file paths of asset bundles {@see AssetBundle::$export} to a JSON file.
 */
final class JsonAssetExporter implements AssetExporterInterface
{
    /**
     * @var string The full path to the target JSON file.
     */
    private string $targetFile;

    /**
     * @param string $targetFile The full path to the target JSON file.
     */
    public function __construct(string $targetFile)
    {
        $this->targetFile = $targetFile;
    }

    /**
     * {@inheritDoc}
     *
     * @throws JsonException If an error occurred during JSON encoding of asset bundles.
     * @throws RuntimeException If an error occurred while writing to the JSON file.
     */
    public function export(array $assetBundles): void
    {
        AssetUtil::exportToFile($this->targetFile, Json::encode(AssetUtil::extractFilePathsForExport($assetBundles)));
    }
}
