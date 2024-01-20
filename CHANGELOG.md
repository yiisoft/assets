# Yii Assets Change Log

## 4.1.0 under development

- Bug #132: `AssetManager` doesn't work in `CLI` mode (@Gerych1984)
- Bug #123: `AssetManager` load empty `AssetBundle` when register wrong namespace bundle (@terabytesoftw)
- Enh #119, #129: Add debug collector for `yiisoft/yii-debug` (@xepozz, @vjik)

## 4.0.0 February 13, 2023

- Chg #115: Adapt configuration group names to Yii conventions (@vjik)
- Enh #116: Add support of `yiisoft/aliases` version `^3.0` (@vjik)

## 3.0.0 January 26, 2023

- Enh #101: Refactoring with PHP 8 features usage (@xepozz, @vjik)
- Enh #113: Add `Yiisoft\Assets\AssetManager::registerCustomized()` (@terabytesoftw)

## 2.1.0 July 18, 2022

- Chg #91: Change path hash logic to `filemtime + filecount` (@Gerych1984)
- Chg #94: Raise the minimum PHP version to 8.0 (@Gerych1984)

## 2.0.1 June 15, 2022

- Enh #96: Add support for `2.0`, `3.0` versions of `psr/log` (@rustamwin)

## 2.0.0 November 04, 2021

- New #88: Make `Yiisoft\Assets\AssetManager::register()` accept a single class, add
  `Yiisoft\Assets\AssetManager::registerMany()` (@devanych)

## 1.0.1 August 30, 2021

- Chg #86: Use definitions from `yiisoft/definitions` in configuration (@vjik)

## 1.0.0 May 16, 2021

- Initial release.
