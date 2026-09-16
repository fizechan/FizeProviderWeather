# Weather

门面类。对外只暴露一个静态工厂方法，按处理器短名创建并缓存 `WeatherHandler` 实例。

- 命名空间：`Fize\Provider\Weather`
- 文件：`src/Weather.php`
- 完全限定名：`Fize\Provider\Weather\Weather`

返回目录：[index.md](index.md)

## 职责

`Weather` 不发起 HTTP 请求，也不解析天气字段。它只做两件事：

1. 把短名（如 `'JuHe'`）拼成 `Fize\Provider\Weather\Handler\JuHe` 并 `new`。
2. 把实例存进静态属性，后续调用直接返回已有对象。

真正的查询能力在返回的 [WeatherHandler](WeatherHandler.md) 上。

## 属性

| 属性 | 可见性 | 类型 | 说明 |
| --- | --- | --- | --- |
| `$handler` | `protected static` | [WeatherHandler](WeatherHandler.md) | 当前进程内缓存的处理器。未初始化时为 `null`（PHP 未赋值静态属性） |

没有实例属性，也没有提供构造函数；此类设计为纯静态入口。

## 方法

### `getInstance()`

```php
public static function getInstance($handler, array $config = []): WeatherHandler
```

| 参数 | 类型 | 默认值 | 说明 |
| --- | --- | --- | --- |
| `$handler` | `string` | 无 | 处理器短名，对应 `Handler` 命名空间下的类名，**区分大小写** |
| `$config` | `array` | `[]` | 传给处理器构造函数的配置。内置 `JuHe` 需要 `key` |

返回值：已构造的 [WeatherHandler](WeatherHandler.md) 实例。源码未写返回类型声明，PHPDoc 标注为 `WeatherHandler`。

实现逻辑：

```php
if (empty(self::$handler)) {
    $class = '\\' . __NAMESPACE__ . '\\Handler\\' . $handler;
    self::$handler = new $class($config);
}
return self::$handler;
```

类名拼接规则：

```text
\Fize\Provider\Weather\Handler\{$handler}
```

因此：

| `$handler` | 实际类 |
| --- | --- |
| `'JuHe'` | `Fize\Provider\Weather\Handler\JuHe` |
| `'Foo'` | `Fize\Provider\Weather\Handler\Foo`（需自行提供该类） |

传入命名空间、路径或别名都不会被规范化。`'juhe'`、`'JUHE'` 都会拼出不存在的类名，触发 PHP 自动加载失败。

## 单例语义

这是「首次成功即锁定」的静态单例，不是可配置的容器：

1. **只看 `empty(self::$handler)`。** 只要静态属性已有对象，第二次及以后的参数全部丢弃。
2. **没有重置接口。** 库内没有 `setHandler()`、`clear()` 或类似方法。要换实现或换 Key，只能在新的 PHP 进程中调用，或自行用反射清空 `Weather::$handler`（不推荐作为正式用法）。
3. **不区分配置差异。** 即使第一次传入错误 Key，后续传入正确 Key 也不会重建对象。
4. **不缓存查询结果。** 单例缓存的是处理器，每次 `getRealtime()` / `getFuture()` 仍会请求远程接口。

错误示例：第二次调用不会切换到另一个处理器。

```php
$a = Weather::getInstance('JuHe', ['key' => 'key-a']);
$b = Weather::getInstance('Other', ['key' => 'key-b']);
// $a 与 $b 是同一个 JuHe 实例，$b 并不是 Other
```

正确做法：一个进程只绑定一个处理器；若必须直接使用某实现，请 `new` 该处理器，不要走门面。

```php
use Fize\Provider\Weather\Handler2\JuHe;

$juhe = new JuHe(['key' => '你的AppKey']);
```

## 使用示例

```php
use Fize\Provider\Weather\Weather;
use Fize\Provider\Weather\WeatherItem;

$api = Weather::getInstance('JuHe', [
    'key' => '你的AppKey',
]);

$realtime = $api->getRealtime('厦门');
assert($realtime instanceof WeatherItem);

$forecast = $api->getFuture('厦门');
assert(is_array($forecast));
```

处理器短名必须与 `src/handler/` 下的类名一致。当前仓库仅有 [`JuHe`](JuHe.md)。

## 与直接实例化的对比

| 方式 | 适用 | 注意 |
| --- | --- | --- |
| `Weather::getInstance('JuHe', $config)` | 应用入口只接一家接口 | 进程内无法切换；类名拼错会在 `new` 时失败 |
| `new JuHe($config)` | 测试、多实例、或明确依赖某一家 | 绕过单例，可同时持有多个不同配置的对象 |

单元测试 `tests/TestWeather.php` 覆盖的是第一种方式，断言返回对象为 `WeatherItem`。

## 扩展时如何被发现

新增处理器时，只要同时满足：

1. 类位于命名空间 `Fize\Provider\Weather\Handler`
2. 类文件可被 PSR-4 加载（建议路径 `Handler2`）
3. 继承 [WeatherHandler](WeatherHandler.md)

就可以用短名交给门面：

```php
Weather::getInstance('YourName', $config);
```

门面**不会**扫描目录、也不会校验 `$handler` 是否继承 `WeatherHandler`。传入不能实例化的名称会在 `new $class` 处失败。
