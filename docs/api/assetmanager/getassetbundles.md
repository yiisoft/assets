### AssetManager::getAssetBundles()

```php
public function getAssetBundles(): array
```

Example:

```php
$assetManager->getAssetBundles();
```

Return:
```php
[
    'App\\Assets\\BootstrapAsset' => App\Assets\BootstrapAsset#1
    (
        [basePath] => 'D:\\git-local\\yii-extensions\\webapp/public/assets/88c9e467'
        [baseUrl] => '/assets/88c9e467'
        [css] => [
            0 => 'css/bootstrap.css'
        ]
        [js] => [
            0 => 'js/bootstrap.js'
        ]
        [sourcePath] => '@alternatives/bootstrap/dist'
        [publishOptions] => [
            'only' => [
                0 => 'css/bootstrap.css'
                1 => 'js/bootstrap.js'
            ]
        ]
        [depends] => [
            0 => 'App\\Assets\\JqueryAsset'
            1 => 'App\\Assets\\PopperAsset'
        ]
        [cssOptions] => []
        [jsOptions] => []
    )
    'App\\Assets\\JqueryAsset' => App\Assets\JqueryAsset#2
    (
        [basePath] => 'D:\\git-local\\yii-extensions\\webapp/public/assets/37fee1f3'
        [baseUrl] => '/assets/37fee1f3'
        [sourcePath] => '@alternatives/jquery'
        [js] => [
            0 => 'dist/jquery.js'
        ]
        [publishOptions] => [
            'only' => [
                0 => 'dist/jquery.js'
            ]
        ]
        [css] => []
        [cssOptions] => []
        [depends] => []
        [jsOptions] => []
    )
    'App\\Assets\\PopperAsset' => App\Assets\PopperAsset#3
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
]
```
<hr />
