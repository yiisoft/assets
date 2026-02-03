<?php

declare(strict_types=1);

namespace Yiisoft\Assets;

use JsonSerializable;
use Yiisoft\Assets\Exception\InvalidConfigException;

/**
 * `Importmap` represents a collection of ESM modules.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/script/type/importmap
 */
final class Importmap implements JsonSerializable
{
    /**
     * @var array<string, string>
     */
    private array $imports = [];

    /**
     * @var array<string, string>
     */
    private array $integrity = [];

    /**
     * @var array<string, array<string, string>>
     */
    private array $scopes = [];

    public function jsonSerialize(): array
    {
        if ($this->imports === []) {
            return [];
        }

        $importmap = [
            'imports' => $this->imports,
        ];

        if ($this->integrity) {
            $importmap['integrity'] = $this->integrity;
        }

        if ($this->scopes) {
            $importmap['scopes'] = $this->scopes;
        }

        return $importmap;
    }

    /**
     * Add ESM module to importmap.
     *
     * @throws InvalidConfigException
     */
    public function addImport(string $moduleName, string $url): void
    {
        if (isset($this->imports[$moduleName])) {
            throw new InvalidConfigException('Module name should be unique.');
        }

        $this->imports[$moduleName] = $url;
    }

    /**
     * Add optional integrity hash to url.
     */
    public function addIntegrity(string $url, string $integrity): void
    {
        $this->integrity[$url] = $integrity;
    }

    /**
     * Add optional scope to importmap.
     */
    public function addScope(string $scope, string $moduleName, string $url): void
    {
        if (!isset($this->scopes[$scope])) {
            $this->scopes[$scope] = [];
        }

        $this->scopes[$scope][$moduleName] = $url;
    }
}
