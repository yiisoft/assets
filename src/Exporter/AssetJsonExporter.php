<?php

declare(strict_types=1);

namespace Yiisoft\Assets\Exporter;

use JsonException;
use RuntimeException;
use Yiisoft\Assets\AssetExporterInterface;
use Yiisoft\Json\Json;

use function dirname;
use function file_put_contents;
use function is_dir;
use function is_writable;

/**
 * Exports asset bundles with the values of all properties {@see AssetBundle::jsonSerialize()} to a JSON file.
 */
final class AssetJsonExporter implements AssetExporterInterface
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
        $targetDirectory = dirname($this->targetFile);

        if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
            throw new RuntimeException("Target directory \"{$targetDirectory}\" does not exist or is not writable.");
        }

        if (file_put_contents($this->targetFile, Json::encode($assetBundles), LOCK_EX) === false) {
            throw new RuntimeException("An error occurred while writing to the \"{$this->targetFile}\" file.");
        }
    }
}
