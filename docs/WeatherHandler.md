# WeatherHandler

天气处理器抽象基类。所有第三方天气接口实现都必须继承它，并实现实时查询与未来预报两个方法。

- 命名空间：`Fize\Provider\Weather`
- 文件：`src/WeatherHandler.php`
- 完全限定名：`Fize\Provider\Weather\WeatherHandler`
- 修饰：`abstract`

返回目录：[index.md](index.md)

相关类：[Weather](Weather.md) · [WeatherItem](WeatherItem.md) · [JuHe](JuHe.md)

## 职责

`WeatherHandler` 约定「一个天气源要做什么」，不约定「怎么请求」：

- 构造时接收配置数组并保存。
- `getRealtime($city)` 返回当前天气，类型为单个 [WeatherItem](WeatherItem.md)。
- `getFuture($city)` 返回未来若干天天气，类型为 `WeatherItem[]`。

HTTP 客户端、URL、错误码、字段映射全部留给子类。当前唯一子类是 [`JuHe`](JuHe.md)。

## 属性

| 属性 | 可见性 | 类型 | 说明 |
| --- | --- | --- | --- |
| `$config` | `protected` | `array` | 构造时传入的配置。子类按自己的键读取，基类不做校验 |

未规定配置键名。内置 `JuHe` 使用 `key`，自定义实现可以定义任意结构。

## 构造函数

```php
public function __construct(array $config = null)
```

| 参数 | 类型 | 默认值 | 说明 |
| --- | --- | --- | --- |
| `$config` | `array\|null` | `null` | 处理器配置。形参写成 `array $config = null`，即隐式可空 |

基类只执行 `$this->config = $config;`。

注意：

- PHP 8.1 起「非空类型 + `null` 默认值」会弃用，PHP 8.4 起可能变为错误。若目标运行时较新，自定义子类可改写为 `?array $config = null` 或 `array $config = []`。
- `Weather::getInstance()` 默认传入 `[]`，因此经门面创建时 `$config` 实际是空数组而不是 `null`。
- 基类不检查必填项。缺少 `key` 之类的键会在子类真正用到时才出错（例如 `JuHe` 读取 `$this->config['key']` 时触发未定义下标）。

## 抽象方法

### `getRealtime()`

```php
abstract public function getRealtime($city): WeatherItem
```

| 参数 | 类型 | 说明 |
| --- | --- | --- |
| `$city` | `string` | 城市名或实现所支持的城市标识 |

PHPDoc 约定返回 [WeatherItem](WeatherItem.md)。源码无返回类型声明。

语义是「当前时刻的实况」。实现应尽量填充实况相关字段（温度、湿度、风力、AQI 等）。城市名如何编码、是否支持城市 ID，由子类决定。

### `getFuture()`

```php
abstract public function getFuture($city): WeatherItem[]
```

| 参数 | 类型 | 说明 |
| --- | --- | --- |
| `$city` | `string` | 城市名或实现所支持的城市标识 |

PHPDoc 约定返回 `WeatherItem[]`。天数、是否含当天、排序均由上游接口与子类决定。`JuHe` 默认对应聚合数据近 5 天预报（接口可选 5 或 7 天，但当前实现未传 `future_num`）。

## 方法契约

子类实现时建议遵守：

| 约定 | 说明 |
| --- | --- |
| 成功返回对象，失败抛异常 | 不要返回 `false` / `null` 表示失败。`JuHe` 在 `error_code != 0` 时抛 `RuntimeException` |
| 只写入有数据的属性 | `WeatherItem` 是字段全集；实况与预报来源不同，允许部分属性为 `null` |
| 不修改调用方传入的 `$city` 语义 | 城市名原样表示查询地点；编码（如 `urlencode`）应在请求层完成 |
| 保持无状态 | 基类没有会话或缓存；不要把上一次查询结果存在实例上除非有明确设计 |

基类**没有**定义超时、重试、日志、限流接口。需要这些能力时在子类内实现，或在调用方外包一层。

## 内置实现

| 类 | 短名 | 上游 | 文档 |
| --- | --- | --- | --- |
| `Fize\Provider\Weather\Handler\JuHe` | `JuHe` | 聚合数据 Simple Weather | [JuHe.md](JuHe.md) |

## 编写自定义处理器

把新类放到 `Fize\Provider\Weather\Handler` 命名空间，继承本类并实现两个抽象方法。

```php
namespace Fize\Provider\Weather\Handler;

use Fize\Provider\Weather\WeatherHandler;
use Fize\Provider\Weather\WeatherItem;
use RuntimeException;

class Example extends WeatherHandler
{
    public function getRealtime($city)
    {
        // 使用 $this->config 请求上游，失败时抛异常
        $item = new WeatherItem();
        $item->date = date('Y-m-d');
        $item->info = '晴';
        $item->temperature = 26;
        return $item;
    }

    public function getFuture($city)
    {
        $item = new WeatherItem();
        $item->date = date('Y-m-d', strtotime('+1 day'));
        $item->info = '多云';
        $item->temperatureMin = 22;
        $item->temperatureMax = 30;
        return [$item];
    }
}
```

建议文件路径：`Handler2`（目录名与命名空间段大小写一致）。

使用：

```php
use Fize\Provider\Weather\Weather;

$api = Weather::getInstance('Example', [
    'token' => '自定义配置',
]);
```

或：

```php
use Fize\Provider\Weather\Handler\Example;

$api = new Example(['token' => '自定义配置']);
```

若希望与 `JuHe` 一样走 `fize/http`，可在子类中调用 `Fize\Http\ClientOnce::get($url)`，返回值为 PSR-7 `ResponseInterface`，响应体用 `getBody()->getContents()` 读取。

## 异常

基类本身不抛异常。约定由子类在上游失败时抛出。`JuHe` 使用：

```php
throw new RuntimeException($json['reason'], (int)$json['error_code']);
```

调用方应捕获 `RuntimeException`（以及 HTTP 客户端可能抛出的网络异常）。错误码含义见 [JuHe 错误处理](JuHe.md#错误处理)。
