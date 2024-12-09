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
     * @param string $targetFile The full path to the target JSON file.
     */
    public function __construct(
        private readonly string $targetFile,
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException If an error occurred while writing to the JSON file.
     */
    public function export(array $assetBundles): void
    {
        try {
            $data = Json::encode(AssetUtil::extractFilePathsForExport($assetBundles));
        } catch (JsonException $e) {
            throw new RuntimeException('An error occurred during JSON encoding of asset bundles.', 0, $e);
        }

        AssetUtil::exportToFile($this->targetFile, $data);
    }
}
