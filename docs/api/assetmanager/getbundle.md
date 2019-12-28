### AssetManager::getBundle()

```php
public function getBundle(string $name): AssetBundle
```

Example:

```php
$assetManager->getBundle(\App\Assets\PopperAsset::class);
```

Return:

```php
App\Assets\PopperAsset#1
(
    [basePath] => 'D:\\git-local\\yii-extensions\\webapp/public/assets/425f4ec0'
    [baseUrl] => '/assets/425f4ec0'
    [sourcePath] => '@alternatives/popper.js/dist'
    [js] => [
        0 => 'umd/popper.js'
    ]
    [publishOptions] => [
        'only' => [
            0 => 'umd/popper.js'
        ]
    ]
    [css] => []
    [cssOptions] => []
    [depends] => []
    [jsOptions] => []
)
```
