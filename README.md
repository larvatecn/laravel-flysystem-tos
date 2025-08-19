# Laravel-flysystem-tos

<p align="center">
    <a href="https://packagist.org/packages/larva/laravel-flysystem-tos"><img src="https://poser.pugx.org/larva/laravel-flysystem-tos/v/stable" alt="Stable Version"></a>
    <a href="https://packagist.org/packages/larva/laravel-flysystem-tos"><img src="https://poser.pugx.org/larva/laravel-flysystem-tos/downloads" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/larva/laravel-flysystem-tos"><img src="https://poser.pugx.org/larva/laravel-flysystem-tos/license" alt="License"></a>
</p>

适用于 Laravel 的火山引擎 TOS 适配器，完整支持火山引擎 TOS 所有方法和操作。

## 要求

- PHP >= 8.0
- Laravel >= 10.0

## 安装

```bash
composer require larva/laravel-flysystem-tos -vv
```

修改配置文件: `config/filesystems.php`

添加一个磁盘配置

```php
'tos' => [
    'driver'     => 'tos',
    'access_id' => env('TOS_ACCESS_ID', 'your id'),
    'access_key' => env('TOS_ACCESS_KEY', 'your key'),
    'bucket' => env('TOS_BUCKET', 'your bucket'),
    'endpoint' => env('TOS_ENDPOINT', 'your endpoint'),//不要用CName,经过测试，官方SDK实现不靠谱
    'url' => env('TOS_URL','cdn url'),//CNAME 写这里，可以是域名绑定或者CDN地址 如 https://www.bbb.com 末尾不要斜杠
    'root' => env('TOS_ROOT', ''), // 这个文件路径前缀，如果上传的内容全部在子目录就填写，否则为空
    'security_token' => null,
    'proxy' => null,
    'timeout' => 3600,
    'ssl' => true
],
```

修改默认存储驱动

```php
    'default' => 'tos'
```

## 使用

参见 [Laravel wiki](https://laravel.com/docs/9.x/filesystem)
