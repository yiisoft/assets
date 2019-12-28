
# AssetBundles


AssetBundle represents a collection of asset files, such as CSS, JavaScript, images.

Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application. The name is the [fully qualified class name](http://php.net/manual/en/language.namespaces.rules.php) of the class representing it.

An asset bundle can depend on other asset bundles. When registering an asset bundle with a view, all its dependent asset bundles will be automatically registered.

## 1.- Public Property:

| Name | Type | Description | Value Default |
|:----:|:----:|:-----------:|:-------------:|
|`$basePath`|`string/null`| The web public directory that contains the asset files in this bundle| `null`|
|`$baseUrl`|`string/null`| The base URL for the relative asset files listed in $js and $css.| `null`|
|`$css`|`array`| List of css files that this bundle contains.| `[]`|
|`$cssOptions`|`array`| The options that will be passed to \Yiisoft\View\WebView::setCssFiles().| `[]`|
|`$depends`|`array`| List of bundle class names that this bundle depends on.| `[]`|
|`$js`|`array`| List of javascript files that this bundle contains.| `[]`|
|`$jsOptions`|`array`| The options that will be passed to \Yiisoft\View\WebView::setJsFiles().| `[]`|
|`$publishOptions`|`array`| The options to be passed to \Yiisoft\Assets\AssetPublisher::publish() when the asset bundle is being published.|`[]`|
|`$sourcePath`|`string/null`| The directory that contains the source asset files for this assetBundle.| `null`|


## 2.- Examples:

- [AssetBundle with sourcePath](/docs/examples/assetbundlewithsourcepath.md)
