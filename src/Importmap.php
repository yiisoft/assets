<?php
declare(strict_types=1);

namespace Yiisoft\Assets;

use JsonSerializable;
use Yiisoft\Assets\Exception\InvalidConfigException;

/**
 * `Importmap` represents a collection of ESM modules
 */
final class Importmap implements JsonSerializable
{
    private array $imports = [];
    private array $integrity = [];
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

        if ($this->scopes)  {
            $importmap['scopes'] = $this->scopes;
        }

        return $importmap;
    }

    /**
     * @throws InvalidConfigException
     */
    public function addImport(string $key, string $url): void
    {
        if (isset($this->imports[$key])) {
            throw new InvalidConfigException('Module name should be a unique.');
        }

        $this->imports[$key] = $url;
    }

    public function addIntegrity(string $url, string $integrity): void
    {
        $this->integrity[$url] = $integrity;
    }

    public function addScope(string $scope, string $key, string $url): void
    {
        if (!isset($this->scopes[$scope])) {
            $this->scopes[$scope] = [];
        }

        $this->scopes[$scope][$key] = $url;
    }
}
