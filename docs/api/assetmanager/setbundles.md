### AssetManager::setBundles()

```php
public function setBundles(array $value): void
```

Example:

```php
$assetManager->setBundles(
    [
        \App\Assets\JqueryAsset::class => [
            'sourcePath' => null, //no publish asset bundle
            'js' => [
                [
                    'https://code.jquery.com/jquery-3.4.1.js',
                    'integrity' => 'sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=',
                    'crossorigin' => 'anonymous'
                ]
            ]
        ]
    ]
);
```

Return:
`void`
