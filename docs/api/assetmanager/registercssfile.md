### AssetManager::registerCssFile()

```php
public function registerCssFile(string $url, array $options = [], string $key = null): void
```

Example:

```php
$assetManager->registerCssFile(
    'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css',
    [
        'integrity' => 'sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh',
        'crossorigin' => 'anonymous'
    ],
    null
);
```

Return:
`void`
