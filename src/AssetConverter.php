<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;

use function array_key_exists;
use function array_merge;
use function escapeshellarg;
use function fclose;
use function is_file;
use function proc_close;
use function proc_open;
use function stream_get_contents;
use function strrpos;
use function strtr;
use function substr;

/**
 * AssetConverter supports conversion of several popular script formats into JavaScript or CSS.
 *
 * It is used by {@see AssetManager} to convert files after they have been published.
 *
 * @psalm-type IsOutdatedCallback = callable(string,string,string,string,string):bool
 *
 * @psalm-import-type ConverterOptions from AssetConverterInterface
 */
final class AssetConverter implements AssetConverterInterface
{
    /**
     * @var array The commands that are used to perform the asset conversion.
     * The keys are the asset file extension names, and the values are the corresponding
     * target script types (either "css" or "js") and the commands used for the conversion.
     *
     * @psalm-var array<string, array{0:string,1:string}>
     */
    private array $commands = [
        'less' => ['css', 'lessc {from} {to} --no-color --source-map'],
        'scss' => ['css', 'sass {options} {from} {to}'],
        'sass' => ['css', 'sass {options} {from} {to}'],
        'styl' => ['css', 'stylus < {from} > {to}'],
        'coffee' => ['js', 'coffee -p {from} > {to}'],
        'ts' => ['js', 'tsc --out {to} {from}'],
    ];

    /**
     * @var callable|null A PHP callback, which should be invoked to check whether asset conversion result is
     * outdated.
     *
     * @psalm-var IsOutdatedCallback|null
     */
    private $isOutdatedCallback = null;

    /**
     * @param Aliases $aliases The aliases instance.
     * @param LoggerInterface $logger The logger instance.
     * @param array $commands The commands that are used to perform the asset
     * conversion. The keys are the asset file extension names, and the values are the corresponding target script types
     * (either "css" or "js") and the commands used for the conversion.
     *
     * You may also use a {@link https://github.com/yiisoft/docs/blob/master/guide/en/concept/aliases.md}
     * to specify the location of the command:
     *
     * ```php
     * [
     *     'styl' => ['css', '@app/node_modules/bin/stylus < {from} > {to}'],
     * ]
     * ```
     * @param bool $forceConvert Whether the source asset file should be converted even if its result already exists.
     * See {@see withForceConvert()}.
     *
     * @psalm-param array<string, array{0:string,1:string}> $commands
     */
    public function __construct(
        private readonly Aliases $aliases,
        private readonly LoggerInterface $logger,
        array $commands = [],
        private bool $forceConvert = false,
    ) {
        $this->commands = array_merge($this->commands, $commands);
    }

    public function convert(string $asset, string $basePath, array $optionsConverter = []): string
    {
        $pos = strrpos($asset, '.');

        if ($pos !== false) {
            $srcExt = substr($asset, $pos + 1);

            $commandOptions = $this->buildConverterOptions($srcExt, $optionsConverter);

            if (isset($this->commands[$srcExt])) {
                [$ext, $command] = $this->commands[$srcExt];
                $result = substr($asset, 0, $pos + 1) . $ext;
                if ($this->forceConvert || $this->isOutdated($basePath, $asset, $result, $srcExt, $ext)) {
                    $this->runCommand($command, $basePath, $asset, $result, $commandOptions);
                }

                return $result;
            }
        }

        return $asset;
    }

    /**
     * Returns a new instance with the specified command.
     *
     * Allows you to set a command that is used to perform the asset conversion {@see $commands}.
     *
     * @param string $from The file extension of the format converting from.
     * @param string $to The file extension of the format converting to.
     * @param string $command The command to execute for conversion.
     *
     * Example:
     *
     * ```php
     * $converter = $converter->withCommand('scss', 'css', 'sass {options} {from} {to}');
     *```
     */
    public function withCommand(string $from, string $to, string $command): self
    {
        $new = clone $this;
        $new->commands[$from] = [$to, $command];
        return $new;
    }

    /**
     * Returns a new instance with the specified force convert value.
     *
     * @param bool $forceConvert Whether the source asset file should be converted even if its result already exists.
     * Default is `false`. You may want to set this to be `true` during the development stage to make
     * sure the converted assets are always up-to-date. Do not set this to true on production servers
     * as it will significantly degrade the performance.
     */
    public function withForceConvert(bool $forceConvert): self
    {
        $new = clone $this;
        $new->forceConvert = $forceConvert;
        return $new;
    }

    /**
     * Returns a new instance with a callback that is used to check for outdated result.
     *
     * @param callable $isOutdatedCallback A PHP callback, which should be invoked to check whether asset conversion result is outdated.
     * It will be invoked only if conversion target file exists and its modification time is older then the one of
     * source file.
     * Callback should match following signature:
     *
     * ```php
     * function (string $basePath, string $sourceFile, string $targetFile, string $sourceExtension, string $targetExtension) : bool
     * ```
     *
     * where $basePath is the asset source directory; $sourceFile is the asset source file path, relative to $basePath;
     * $targetFile is the asset target file path, relative to $basePath; $sourceExtension is the source asset file
     * extension and $targetExtension is the target asset file extension, respectively.
     *
     * It should return `true` is case asset should be reconverted.
     * For example:
     *
     * ```php
     * function ($basePath, $sourceFile, $targetFile, $sourceExtension, $targetExtension) {
     *     if (YII_ENV !== 'dev') {
     *         return false;
     *     }
     *
     *     $resultModificationTime = @filemtime("$basePath/$result");
     *     foreach (FileHelper::findFiles($basePath, ['only' => ["*.{$sourceExtension}"]]) as $filename) {
     *         if ($resultModificationTime < @filemtime($filename)) {
     *             return true;
     *         }
     *     }
     *
     *     return false;
     * }
     * ```
     *
     * @psalm-param IsOutdatedCallback $isOutdatedCallback
     */
    public function withIsOutdatedCallback(callable $isOutdatedCallback): self
    {
        $new = clone $this;
        $new->isOutdatedCallback = $isOutdatedCallback;
        return $new;
    }

    /**
     * Checks whether asset convert result is outdated, and thus should be reconverted.
     *
     * @param string $basePath The directory the $asset is relative to.
     * @param string $sourceFile The asset source file path, relative to [[$basePath]].
     * @param string $targetFile The converted asset file path, relative to [[$basePath]].
     * @param string $sourceExtension Source asset file extension.
     * @param string $targetExtension Target asset file extension.
     *
     * @return bool Whether asset is outdated or not.
     */
    private function isOutdated(
        string $basePath,
        string $sourceFile,
        string $targetFile,
        string $sourceExtension,
        string $targetExtension
    ): bool {
        if (!is_file("$basePath/$targetFile")) {
            return true;
        }

        $resultModificationTime = FileHelper::lastModifiedTime("$basePath/$targetFile");

        if ($resultModificationTime < FileHelper::lastModifiedTime("$basePath/$sourceFile")) {
            return true;
        }

        if ($this->isOutdatedCallback === null) {
            return false;
        }

        return ($this->isOutdatedCallback)(
            $basePath,
            $sourceFile,
            $targetFile,
            $sourceExtension,
            $targetExtension
        );
    }

    /**
     * Runs a command to convert asset files.
     *
     * @param string $command The command to run. If prefixed with an `@` it will be treated as a
     * {@link https://github.com/yiisoft/docs/blob/master/guide/en/concept/aliases.md}.
     * @param string $basePath Asset base path and command working directory.
     * @param string $asset The name of the asset file.
     * @param string $result The name of the file to be generated by the converter command.
     * @param string|null $options
     *
     * @return bool True on success, false on failure. Failures will be logged.
     */
    private function runCommand(
        string $command,
        string $basePath,
        string $asset,
        string $result,
        ?string $options = null
    ): bool {
        $basePath = $this->aliases->get($basePath);

        $command = $this->aliases->get($command);

        $command = strtr($command, [
            '{options}' => $options,
            '{from}' => escapeshellarg("$basePath/$asset"),
            '{to}' => escapeshellarg("$basePath/$result"),
        ]);

        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $pipes = [];

        $proc = proc_open($command, $descriptors, $pipes, $basePath);
        /**
         * @var resource $proc
         * @var resource[] $pipes
         */

        $stdout = stream_get_contents($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $status = proc_close($proc);

        if ($status === 0) {
            $this->logger->debug(
                "Converted $asset into $result:\nSTDOUT:\n$stdout\nSTDERR:\n$stderr",
                [__METHOD__]
            );
        } else {
            $this->logger->error(
                "AssetConverter command '$command' failed with exit code $status:\nSTDOUT:\n$stdout\nSTDERR:\n$stderr",
                [__METHOD__]
            );
        }

        return $status === 0;
    }

    /**
     * @psalm-param ConverterOptions $options
     */
    private function buildConverterOptions(string $srcExt, array $options): string
    {
        $commandOptions = '';

        if (isset($options[$srcExt])) {
            if (array_key_exists('command', $options[$srcExt])) {
                $commandOptions .= $options[$srcExt]['command'] . ' ';
            }

            if (array_key_exists('path', $options[$srcExt])) {
                $path = $this->aliases->get($options[$srcExt]['path']);

                $commandOptions = strtr($commandOptions, [
                    '{path}' => $path,
                ]);
            }
        }

        return $commandOptions;
    }
}
