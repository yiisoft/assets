### AssetManager::registerJsFile()

```php
public function registerJsFile(string $url, array $options = [], string $key = null): void
```

Example:

```php
$assetManager->registerJsFile(
    'https://code.jquery.com/jquery-3.4.1.slim.min.j',
    [
        'integrity' => 'sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n',
        'crossorigin' => 'anonymous'
    ],
    null
);
```

Return:
`void`
