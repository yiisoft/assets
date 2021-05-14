<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\Exception\InvalidConfigException;

use function array_key_exists;
use function array_values;
use function get_class;
use function gettype;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function sprintf;

/**
 * AssetRegistrar registers asset files, code blocks and variables from a bundle considering dependencies.
 *
 * @internal
 *
 * @psalm-type CssFile = array{0:string,1?:int}&array
 * @psalm-type CssString = array{0:mixed,1?:int}&array
 * @psalm-type JsFile = array{0:string,1?:int}&array
 * @psalm-type JsString = array{0:mixed,1?:int}&array
 * @psalm-type JsVar = array{0:string,1:mixed,2?:int}
 */
final class AssetRegistrar
{
    private Aliases $aliases;
    private AssetLoaderInterface $loader;
    private ?AssetConverterInterface $converter = null;

    /**
     * @psalm-var CssFile[]
     */
    private array $cssFiles = [];

    /**
     * @psalm-var CssString[]
     */
    private array $cssStrings = [];

    /**
     * @psalm-var JsFile[]
     */
    private array $jsFiles = [];

    /**
     * @psalm-var JsString[]
     */
    private array $jsStrings = [];

    private array $jsVars = [];

    public function __construct(Aliases $aliases, AssetLoaderInterface $loader)
    {
        $this->aliases = $aliases;
        $this->loader = $loader;
    }

    /**
     * Return config array CSS AssetBundle.
     *
     * @psalm-return CssFile[]
     */
    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }

    /**
     * Returns CSS blocks.
     *
     * @return array
     * @psalm-return CssString[]
     */
    public function getCssStrings(): array
    {
        return $this->cssStrings;
    }

    /**
     * Returns config array JS AssetBundle.
     *
     * @psalm-return JsFile[]
     */
    public function getJsFiles(): array
    {
        return $this->jsFiles;
    }

    /**
     * Returns JS code blocks.
     *
     * @return array
     * @psalm-return JsString[]
     */
    public function getJsStrings(): array
    {
        return $this->jsStrings;
    }

    /**
     * Returns JS variables.
     *
     * @return array
     */
    public function getJsVars(): array
    {
        return array_values($this->jsVars);
    }

    /**
     * Returns a new instance with the specified converter.
     *
     * @param AssetConverterInterface $converter
     *
     * @return self
     */
    public function withConverter(AssetConverterInterface $converter): self
    {
        $new = clone $this;
        $new->converter = $converter;
        return $new;
    }

    /**
     * Returns a new instance with the specified loader.
     *
     * @param AssetLoaderInterface $loader
     *
     * @return self
     */
    public function withLoader(AssetLoaderInterface $loader): self
    {
        $new = clone $this;
        $new->loader = $loader;
        return $new;
    }

    /**
     * Registers asset files from a bundle considering dependencies.
     *
     * @param AssetBundle $bundle
     *
     * @throws InvalidConfigException If asset files are not found.
     */
    public function register(AssetBundle $bundle): void
    {
        if (isset($bundle->basePath, $bundle->baseUrl) && $this->converter !== null) {
            $this->convertCss($bundle);
            $this->convertJs($bundle);
        }

        foreach ($bundle->js as $key => $js) {
            $this->registerJsFile(
                $bundle,
                is_string($key) ? $key : null,
                $js,
            );
        }

        /** @var mixed $jsString */
        foreach ($bundle->jsStrings as $key => $jsString) {
            $this->registerJsString(
                $bundle,
                is_string($key) ? $key : null,
                $jsString,
            );
        }

        /** @var JsVar|string $jsVar */
        foreach ($bundle->jsVars as $name => $jsVar) {
            if (is_string($name)) {
                $this->registerJsVar($name, $jsVar, $bundle->jsPosition);
            } else {
                $this->registerJsVarByConfig($jsVar, $bundle->jsPosition);
            }
        }

        /** @var CssFile|string $css */
        foreach ($bundle->css as $key => $css) {
            $this->registerCssFile(
                $bundle,
                is_string($key) ? $key : null,
                $css,
            );
        }

        /** @var mixed $cssString */
        foreach ($bundle->cssStrings as $key => $cssString) {
            $this->registerCssString(
                $bundle,
                is_string($key) ? $key : null,
                $cssString,
            );
        }
    }

    /**
     * Converter SASS, SCSS, Stylus and other formats to CSS.
     *
     * @param AssetBundle $bundle
     */
    private function convertCss(AssetBundle $bundle): void
    {
        /** @var CssFile|string $css */
        foreach ($bundle->css as $i => $css) {
            if (is_array($css)) {
                $file = $css[0];
                if (AssetUtil::isRelative($file)) {
                    $baseFile = $this->aliases->get("{$bundle->basePath}/{$file}");
                    if (is_file($baseFile)) {
                        /**
                         * @psalm-suppress PossiblyNullArgument
                         * @psalm-suppress PossiblyNullReference
                         */
                        $css[0] = $this->converter->convert(
                            $file,
                            $bundle->basePath,
                            $bundle->converterOptions,
                        );

                        $bundle->css[$i] = $css;
                    }
                }
            } elseif (AssetUtil::isRelative($css)) {
                $baseCss = $this->aliases->get("{$bundle->basePath}/{$css}");
                if (is_file("$baseCss")) {
                    /**
                     * @psalm-suppress PossiblyNullArgument
                     * @psalm-suppress PossiblyNullReference
                     */
                    $bundle->css[$i] = $this->converter->convert(
                        $css,
                        $bundle->basePath,
                        $bundle->converterOptions
                    );
                }
            }
        }
    }

    /**
     * Convert files from TypeScript and other formats into JavaScript.
     *
     * @param AssetBundle $bundle
     */
    private function convertJs(AssetBundle $bundle): void
    {
        /** @var JsFile|string $js */
        foreach ($bundle->js as $i => $js) {
            if (is_array($js)) {
                $file = $js[0];
                if (AssetUtil::isRelative($file)) {
                    $baseFile = $this->aliases->get("{$bundle->basePath}/{$file}");
                    if (is_file($baseFile)) {
                        /**
                         * @psalm-suppress PossiblyNullArgument
                         * @psalm-suppress PossiblyNullReference
                         */
                        $js[0] = $this->converter->convert(
                            $file,
                            $bundle->basePath,
                            $bundle->converterOptions
                        );

                        $bundle->js[$i] = $js;
                    }
                }
            } elseif (AssetUtil::isRelative($js)) {
                $baseJs = $this->aliases->get("{$bundle->basePath}/{$js}");
                if (is_file($baseJs)) {
                    /**
                     * @psalm-suppress PossiblyNullArgument
                     * @psalm-suppress PossiblyNullReference
                     */
                    $bundle->js[$i] = $this->converter->convert($js, $bundle->basePath);
                }
            }
        }
    }

    /**
     * Registers a CSS file.
     *
     * @param array|string $css
     *
     * @throws InvalidConfigException
     */
    private function registerCssFile(AssetBundle $bundle, ?string $key, $css): void
    {
        if (is_array($css)) {
            if (!array_key_exists(0, $css)) {
                throw new InvalidConfigException('Do not set in array CSS URL.');
            }
            $url = $css[0];
        } else {
            $url = $css;
        }

        if (!is_string($url)) {
            throw new InvalidConfigException(
                sprintf(
                    'CSS file should be string. Got %s.',
                    $this->getType($url),
                )
            );
        }

        if ($url === '') {
            throw new InvalidConfigException('CSS file should be non empty string.');
        }

        $url = $this->loader->getAssetUrl($bundle, $url);

        if (is_array($css)) {
            $css[0] = $url;
        } else {
            $css = [$url];
        }

        if ($bundle->cssPosition !== null && !isset($css[1])) {
            $css[1] = $bundle->cssPosition;
        }

        /** @psalm-var CssFile */
        $css = $this->mergeOptionsWithArray($bundle->cssOptions, $css);

        $this->cssFiles[$key ?: $url] = $css;
    }

    /**
     * Registers a CSS string.
     *
     * @param mixed $cssString
     *
     * @throws InvalidConfigException
     */
    private function registerCssString(AssetBundle $bundle, ?string $key, $cssString): void
    {
        if (is_array($cssString)) {
            $config = $cssString;
            if (!array_key_exists(0, $config)) {
                throw new InvalidConfigException('CSS string do not set in array.');
            }
        } else {
            $config = [$cssString];
        }

        if ($bundle->cssPosition !== null && !isset($config[1])) {
            $config[1] = $bundle->cssPosition;
        }

        /** @psalm-var CssString */
        $config = $this->mergeOptionsWithArray($bundle->cssOptions, $config);

        if ($key === null) {
            $this->cssStrings[] = $config;
        } else {
            $this->cssStrings[$key] = $config;
        }
    }

    /**
     * Registers a JS file.
     *
     * @param array|string $js
     *
     * @throws InvalidConfigException
     */
    private function registerJsFile(AssetBundle $bundle, ?string $key, $js): void
    {
        if (is_array($js)) {
            if (!array_key_exists(0, $js)) {
                throw new InvalidConfigException('Do not set in array JS URL.');
            }
            $url = $js[0];
        } else {
            $url = $js;
        }

        if (!is_string($url)) {
            throw new InvalidConfigException(
                sprintf(
                    'JS file should be string. Got %s.',
                    $this->getType($url),
                )
            );
        }

        if ($url === '') {
            throw new InvalidConfigException('JS file should be non empty string.');
        }

        $url = $this->loader->getAssetUrl($bundle, $url);

        if (is_array($js)) {
            $js[0] = $url;
        } else {
            $js = [$url];
        }

        if ($bundle->jsPosition !== null && !isset($js[1])) {
            $js[1] = $bundle->jsPosition;
        }

        /** @psalm-var JsFile */
        $js = $this->mergeOptionsWithArray($bundle->jsOptions, $js);

        $this->jsFiles[$key ?: $url] = $js;
    }

    /**
     * Registers a JS string.
     *
     * @param array|string $jsString
     *
     * @throws InvalidConfigException
     */
    private function registerJsString(AssetBundle $bundle, ?string $key, $jsString): void
    {
        if (is_array($jsString)) {
            if (!array_key_exists(0, $jsString)) {
                throw new InvalidConfigException('JavaScript string do not set in array.');
            }
        } else {
            $jsString = [$jsString];
        }

        if ($bundle->jsPosition !== null && !isset($jsString[1])) {
            $jsString[1] = $bundle->jsPosition;
        }

        /** @psalm-var JsString */
        $jsString = $this->mergeOptionsWithArray($bundle->jsOptions, $jsString);

        if ($key === null) {
            $this->jsStrings[] = $jsString;
        } else {
            $this->jsStrings[$key] = $jsString;
        }
    }

    /**
     * Registers a JavaScript variable.
     *
     * @param mixed $value
     */
    private function registerJsVar(string $name, $value, ?int $position): void
    {
        $config = [$name, $value];

        if ($position !== null) {
            $config[2] = $position;
        }

        $this->jsVars[$name] = $config;
    }

    /**
     * Registers a JavaScript variable by config.
     *
     * @param mixed $config
     *
     * @throws InvalidConfigException
     */
    private function registerJsVarByConfig($config, ?int $bundleJsPosition): void
    {
        if (!is_array($config)) {
            throw new InvalidConfigException(
                sprintf(
                    'Without string key JavaScript variable should be array. Got %s.',
                    $this->getType($config),
                )
            );
        }

        if (!array_key_exists(0, $config)) {
            throw new InvalidConfigException('Do not set JavaScript variable name.');
        }
        $name = $config[0];

        if (!is_string($name)) {
            throw new InvalidConfigException(
                sprintf(
                    'JavaScript variable name should be string. Got %s.',
                    $this->getType($name),
                )
            );
        }

        if (!array_key_exists(1, $config)) {
            throw new InvalidConfigException('Do not set JavaScript variable value.');
        }
        /** @var mixed */
        $value = $config[1];

        $position = $config[2] ?? $bundleJsPosition;
        if (!is_int($position)) {
            throw new InvalidConfigException(
                sprintf(
                    'JavaScript variable position should be integer. Got %s.',
                    $this->getType($position),
                )
            );
        }

        $this->registerJsVar($name, $value, $position);
    }

    /**
     * @throws InvalidConfigException
     */
    private function mergeOptionsWithArray(array $options, array $array): array
    {
        /** @var mixed $value */
        foreach ($options as $key => $value) {
            if (is_int($key)) {
                throw new InvalidConfigException(
                    'JavaScript or CSS options should be list of key/value pairs with string keys. Got integer key.'
                );
            }

            if (!array_key_exists($key, $array)) {
                /** @var mixed */
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * @param mixed $value
     */
    private function getType($value): string
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}
