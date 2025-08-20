<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\Exception\InvalidConfigException;

use function array_key_exists;
use function array_values;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;

/**
 * `AssetRegistrar` registers asset files, code blocks and variables from a bundle considering dependencies.
 *
 * @internal
 *
 * @psalm-import-type CssFile from AssetManager
 * @psalm-import-type CssString from AssetManager
 * @psalm-import-type JsFile from AssetManager
 * @psalm-import-type JsString from AssetManager
 * @psalm-import-type JsVar from AssetManager
 * @psalm-import-type ConverterOptions from AssetConverterInterface
 */
final class AssetRegistrar
{
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

    /**
     * @psalm-var JsVar[]
     */
    private array $jsVars = [];

    public function __construct(
        private Aliases $aliases,
        private AssetLoaderInterface $loader
    ) {
    }

    /**
     * @return array Config array of CSS files.
     *
     * @psalm-return CssFile[]
     */
    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }

    /**
     * @return array CSS blocks.
     *
     * @psalm-return CssString[]
     */
    public function getCssStrings(): array
    {
        return $this->cssStrings;
    }

    /**
     * @return array Config array of JavaScript files.
     *
     * @psalm-return JsFile[]
     */
    public function getJsFiles(): array
    {
        return $this->jsFiles;
    }

    /**
     * @return array JavaScript code blocks.
     *
     * @psalm-return JsString[]
     */
    public function getJsStrings(): array
    {
        return $this->jsStrings;
    }

    /**
     * @return array JavaScript variables.
     *
     * @psalm-return list<JsVar>
     */
    public function getJsVars(): array
    {
        return array_values($this->jsVars);
    }

    /**
     * @return self A new instance with the specified converter.
     */
    public function withConverter(AssetConverterInterface $converter): self
    {
        $new = clone $this;
        $new->converter = $converter;
        return $new;
    }

    /**
     * @return self A new instance with the specified loader.
     */
    public function withLoader(AssetLoaderInterface $loader): self
    {
        $new = clone $this;
        $new->loader = $loader;
        return $new;
    }

    /**
     * Registers assets from a bundle considering dependencies.
     *
     * @throws InvalidConfigException If asset files are not found.
     */
    public function register(AssetBundle $bundle): void
    {
        if (isset($bundle->basePath, $bundle->baseUrl) && $this->converter !== null) {
            $this->convertCss($bundle);
            $this->convertJs($bundle);
        }

        /** @var JsFile|string $js */
        foreach ($bundle->js as $key => $js) {
            $this->registerJsFile(
                $bundle,
                is_string($key) ? $key : null,
                $js,
            );
        }

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
     */
    private function convertCss(AssetBundle $bundle): void
    {
        foreach ($bundle->css as $i => $css) {
            /** @psalm-var CssFile|string $css */
            if (is_array($css)) {
                $file = $css[0];
                if (AssetUtil::isRelative($file)) {
                    $baseFile = $this->aliases->get("{$bundle->basePath}/{$file}");
                    if (is_file($baseFile)) {
                        /** @psalm-suppress PossiblyNullArgument, PossiblyNullReference, MixedArgumentTypeCoercion */
                        // @phpstan-ignore method.nonObject
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
                    /** @psalm-suppress PossiblyNullArgument, PossiblyNullReference, MixedArgumentTypeCoercion */
                    // @phpstan-ignore method.nonObject
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
     */
    private function convertJs(AssetBundle $bundle): void
    {
        foreach ($bundle->js as $i => $js) {
            /** @psalm-var JsFile|string $js */
            if (is_array($js)) {
                $file = $js[0];
                if (AssetUtil::isRelative($file)) {
                    $baseFile = $this->aliases->get("{$bundle->basePath}/{$file}");
                    if (is_file($baseFile)) {
                        /** @psalm-suppress PossiblyNullArgument, PossiblyNullReference, MixedArgumentTypeCoercion */
                        // @phpstan-ignore method.nonObject
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
                    /** @psalm-suppress PossiblyNullArgument, PossiblyNullReference */
                    // @phpstan-ignore method.nonObject
                    $bundle->js[$i] = $this->converter->convert($js, $bundle->basePath);
                }
            }
        }
    }

    /**
     * Registers a CSS file.
     *
     * @throws InvalidConfigException
     */
    private function registerCssFile(AssetBundle $bundle, ?string $key, array|string $css): void
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
                    get_debug_type($url),
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
     * @throws InvalidConfigException
     */
    private function registerCssString(AssetBundle $bundle, ?string $key, mixed $cssString): void
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
     * Registers a JavaScript file.
     *
     * @throws InvalidConfigException
     */
    private function registerJsFile(AssetBundle $bundle, ?string $key, array|string $js): void
    {
        if (is_array($js)) {
            if (!array_key_exists(0, $js)) {
                throw new InvalidConfigException('Do not set in array JavaScript URL.');
            }
            $url = $js[0];
        } else {
            $url = $js;
        }

        if (!is_string($url)) {
            throw new InvalidConfigException(
                sprintf(
                    'JavaScript file should be string. Got %s.',
                    get_debug_type($url),
                )
            );
        }

        if ($url === '') {
            throw new InvalidConfigException('JavaScript file should be non empty string.');
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
     * Registers a JavaScript string.
     *
     * @throws InvalidConfigException
     */
    private function registerJsString(AssetBundle $bundle, ?string $key, mixed $jsString): void
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
     */
    private function registerJsVar(string $name, mixed $value, ?int $position): void
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
     * @throws InvalidConfigException
     */
    private function registerJsVarByConfig(mixed $config, ?int $bundleJsPosition): void
    {
        if (!is_array($config)) {
            throw new InvalidConfigException(
                sprintf(
                    'Without string key JavaScript variable should be array. Got %s.',
                    get_debug_type($config),
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
                    get_debug_type($name),
                )
            );
        }

        if (!array_key_exists(1, $config)) {
            throw new InvalidConfigException('Do not set JavaScript variable value.');
        }
        $value = $config[1];

        $position = $config[2] ?? $bundleJsPosition;
        if (!is_int($position)) {
            throw new InvalidConfigException(
                sprintf(
                    'JavaScript variable position should be integer. Got %s.',
                    get_debug_type($position),
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
        foreach ($options as $key => $value) {
            if (is_int($key)) {
                throw new InvalidConfigException(
                    'JavaScript or CSS options should be list of key/value pairs with string keys. Got integer key.'
                );
            }

            if (!array_key_exists($key, $array)) {
                $array[$key] = $value;
            }
        }

        return $array;
    }
}
