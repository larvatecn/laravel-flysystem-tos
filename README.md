# Laravel Flysystem TOS

<p align="center">
    <a href="https://packagist.org/packages/larva/laravel-flysystem-tos"><img src="https://poser.pugx.org/larva/laravel-flysystem-tos/v/stable" alt="Stable Version"></a>
    <a href="https://packagist.org/packages/larva/laravel-flysystem-tos"><img src="https://poser.pugx.org/larva/laravel-flysystem-tos/downloads" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/larva/laravel-flysystem-tos"><img src="https://poser.pugx.org/larva/laravel-flysystem-tos/license" alt="License"></a>
</p>

适用于 Laravel 的火山引擎 TOS（对象存储）Flysystem 适配器，完整支持火山引擎 TOS 所有方法和操作。

## 要求

- PHP >= 8.2
- Laravel 12.x / 13.x
- League Flysystem ^3.0

## 安装

```bash
composer require larva/laravel-flysystem-tos
```

该包支持 Laravel 包自动发现（Package Auto-Discovery），无需手动注册服务提供者。

## 配置

在 `config/filesystems.php` 的 `disks` 中添加 TOS 磁盘配置：

```php
'tos' => [
    'driver'              => 'tos',
    'access_key'          => env('TOS_ACCESS_KEY'),
    'access_secret'       => env('TOS_ACCESS_SECRET'),
    'bucket'              => env('TOS_BUCKET'),
    'region'              => env('TOS_REGION'), // 例如 cn-beijing
    'endpoint'            => env('TOS_ENDPOINT'), // TOS 接入域名，不要使用 CName
    'url'                 => env('TOS_URL',''), // CDN 或自定义域名，末尾不要斜杠，可选
    'is_custom_domain'    => false, // 如果 endpoint 是绑定的自定义域名，设置为 true，否则为 false，同时 url 设置无效
    'root'                => env('TOS_ROOT', ''), // 存储路径前缀
    'verify_ssl'          => true, // 验证 SSL 证书
    'visibility'          => 'public', // 默认文件可见性：public 或 private
    'directory_visibility'=> 'public', // 默认目录可见性：public 或 private，可选
    'ssl'                 => true, // 是否使用 HTTPS
    'connection_timeout'  => 10000, // 连接超时时间（毫秒），可选，默认 10000
    'socket_timeout'      => 30000, // 套接字超时时间（毫秒），可选，默认 30000
    'options'             => [], // 传递给底层 TOS 适配器的额外选项，可选
    'throw'               => false,
    'report'              => false,
],
```

在 `.env` 文件中配置对应的环境变量：

```env
TOS_ACCESS_KEY=your-access-key
TOS_ACCESS_SECRET=your-access-secret
TOS_BUCKET=your-bucket
TOS_REGION=cn-beijing
TOS_ENDPOINT=tos-cn-beijing.volces.com
TOS_URL=https://cdn.example.com  # 可选，CDN 或自定义域名
TOS_ROOT=uploads                 # 可选，存储路径前缀
```

> **提示**：如果未配置 `access_key` 和 `access_secret`，将使用 TOS SDK 的环境凭证方式（基于 `TOS_ACCESS_KEY` / `TOS_ACCESS_SECRET` 环境变量或 IAM 角色临时凭证）。

如需将 TOS 设为默认存储驱动，修改 `default` 配置：

```php
'default' => 'tos',
```

## 使用

### 基本文件操作

```php
use Illuminate\Support\Facades\Storage;

// 获取磁盘实例
$disk = Storage::disk('tos');

// 写入文件
$disk->put('path/to/file.txt', 'file contents');

// 读取文件
$contents = $disk->get('path/to/file.txt');

// 检查文件是否存在
$exists = $disk->exists('path/to/file.txt');

// 删除文件
$disk->delete('path/to/file.txt');

// 复制文件
$disk->copy('source/path.txt', 'dest/path.txt');

// 移动文件
$disk->move('source/path.txt', 'dest/path.txt');

// 列出目录内容
$files = $disk->files('directory');
$allFiles = $disk->allFiles('directory');
```

### 文件上传

```php
// 上传文件
$path = $disk->putFile('uploads', $request->file('avatar'));

// 上传文件并指定可见性
$path = $disk->putFile('uploads', $request->file('avatar'), 'public');
```

### 获取文件 URL

URL 生成遵循以下优先级：

1. 若配置了 `url`（CDN/自定义域名），使用该地址拼接
2. 否则根据文件可见性判断：
   - **public**：使用 `{scheme}://{bucket}.{endpoint}/{path}` 格式
   - **private**：生成 5 分钟有效期的临时 URL

```php
// 获取文件 URL
$url = Storage::disk('tos')->url('path/to/file.txt');

// 获取文件可见性
$visibility = Storage::disk('tos')->getVisibility('path/to/file.txt');

// 设置文件可见性
Storage::disk('tos')->setVisibility('path/to/file.txt', 'private');
```

### 临时 URL

```php
use Carbon\Carbon;

// 生成临时下载 URL（默认 5 分钟，可自定义）
$tempUrl = Storage::disk('tos')->temporaryUrl(
    'path/to/private-file.txt',
    Carbon::now()->addMinutes(30)
);

// 生成临时上传 URL
$result = Storage::disk('tos')->temporaryUploadUrl(
    'path/to/upload.txt',
    Carbon::now()->addMinutes(10)
);
// $result['url']     — 预签名上传 URL
// $result['headers'] — 上传请求所需的签名头（需与请求一同发送）
```

### 获取 TOS 客户端

如需直接调用 TOS SDK 的完整功能，可获取底层客户端实例：

```php
use Larva\Flysystem\Volc\TOSAdapter;

/** @var TOSAdapter $adapter */
$adapter = Storage::disk('tos')->getAdapter();
$client = $adapter->getClient(); // Tos\TosClient 实例
```

### 签名 URL

```php
$adapter = Storage::disk('tos')->getAdapter();

// 生成签名 URL，可指定 HTTP 方法和备用 endpoint
$signedUrl = $adapter->signUrl('path/to/file.txt', 3600, [], 'GET');
```

## 前端直传：使用预签名 URL 上传

在 Web 应用中，通常需要让浏览器直接上传文件到 TOS，而不经过服务器中转。通过后端生成预签名 URL，前端使用 [TOS Browser.js SDK](https://www.volcengine.com/docs/6349/1109237) 或 `axios` 即可实现直传。

这种方式的优势是 AccessKey 不会暴露给前端，且文件无需经过应用服务器。

### 后端：生成预签名上传 URL

定义一个 API 路由，返回预签名 URL：

```php
// routes/api.php
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

Route::post('/tos/upload-url', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'filename' => 'required|string',
    ]);

    $path = 'uploads/' . $request->input('filename');

    $result = Storage::disk('tos')->temporaryUploadUrl(
        $path,
        Carbon::now()->addMinutes(10)
    );

    return response()->json([
        'url'     => $result['url'],
        'headers' => $result['headers'],
        'path'    => $path,
    ]);
});
```

### 前端：使用 axios 上传

```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.4.0/axios.min.js"></script>
<script>
async function uploadToTOS(file) {
    // 1. 从后端获取预签名 URL
    const { data } = await axios.post('/api/tos/upload-url', {
        filename: file.name,
    });

    // 2. 使用预签名 URL 直接上传到 TOS
    await axios.put(data.url, file, {
        headers: data.headers,
    });

    console.log('上传成功，文件路径：', data.path);
}
</script>
```

### 前端：使用 TOS Browser.js SDK 上传

如果不使用预签名 URL，也可以通过 STS 临时凭证初始化 TOS Client，直接在浏览器端生成预签名 URL 并上传：

```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <title>TOS 直传示例</title>
    <!-- 导入 TOS Browser.js SDK -->
    <script src="https://tos-public.volccdn.com/obj/volc-tos-public/@volcengine/tos-sdk@latest/browser/tos.umd.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.4.0/axios.min.js"></script>
</head>
<body>
    <input type="file" id="fileInput" />
    <button onclick="upload()">上传</button>

    <script>
        // 从后端 STS 接口获取临时凭证（推荐）
        const client = new TOS({
            region: 'cn-beijing',           // Bucket 所在地域
            endpoint: 'tos-cn-beijing.volces.com',
            accessKeyId: 'your-sts-ak',     // STS 临时 AccessKey ID
            accessKeySecret: 'your-sts-sk', // STS 临时 AccessKey Secret
            stsToken: 'your-sts-token',     // STS 安全令牌
            bucket: 'your-bucket',
        });

        async function upload() {
            const file = document.getElementById('fileInput').files[0];
            if (!file) return;

            const objectName = 'uploads/' + file.name;

            // 生成预签名上传 URL
            const url = client.getPreSignedUrl({
                method: 'PUT',
                bucket: 'your-bucket',
                key: objectName,
            });

            // 使用预签名 URL 上传文件
            const uploadResult = await axios.put(url, file);
            console.log('上传成功，状态码：', uploadResult.status);
        }
    </script>
</body>
</html>
```

> **安全提示**：Browser.js SDK 方式需要在前端暴露临时凭证，请务必通过 STS 服务获取临时凭证而非直接使用永久 AccessKey。推荐使用后端生成预签名 URL 的方式，前端无需任何凭证。

## 关于 `endpoint` 配置

`endpoint` 应使用 TOS 接入域名（如 `tos-cn-beijing.volces.com`），**不要使用 CName**。如需使用自定义域名或 CDN，请通过 `url` 配置项指定。

## 开发

### 代码风格检查

本项目使用 [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) 统一代码风格。

```bash
# 检查代码风格（仅报告，不修改）
composer check-style

# 自动修复代码风格问题
composer fix-style
```

## 相关文档

- [Laravel 文件系统文档](https://laravel.com/docs/filesystem)
- [火山引擎 TOS 文档](https://www.volcengine.com/docs/6349)
- [Flysystem 文档](https://flysystem.thephpleague.com/)

## License

[MIT](LICENSE)
