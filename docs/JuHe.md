# JuHe

聚合数据天气预报处理器。继承 [WeatherHandler](WeatherHandler.md)，把 Simple Weather 接口的 JSON 映射为 [WeatherItem](WeatherItem.md)。

- 命名空间：`Fize\Provider\Weather\Handler`
- 文件：`src/handler/JuHe.php`
- 完全限定名：`Fize\Provider\Weather\Handler\JuHe`
- 门面短名：`'JuHe'`
- 上游文档：[天气预报（聚合数据）](https://www.juhe.cn/docs/api/id/73)

返回目录：[index.md](index.md)

相关类：[Weather](Weather.md) · [WeatherHandler](WeatherHandler.md) · [WeatherItem](WeatherItem.md)

## 职责

`JuHe` 是当前仓库唯一的具体处理器，负责：

1. 用配置中的 `key` 请求聚合数据「根据城市查询天气」接口。
2. 检查 `error_code`，非 0 时抛 `RuntimeException`。
3. 将 `result.realtime` / `result.future` 转为 `WeatherItem`。

生活指数、天气种类列表等聚合数据的其他子接口**没有**封装。

## 配置

构造函数继承自基类，使用配置数组：

```php
$juhe = new \Fize\Provider\Weather\Handler2\JuHe([
    'key' => '你的AppKey',
]);
```

| 键 | 必填 | 说明 |
| --- | --- | --- |
| `key` | 是 | 聚合数据个人中心对应接口的 AppKey |

没有其他已识别的配置项（无超时、无 `future_num`、无 HTTPS 开关）。`key` 会拼进 URL 查询串。

AppKey 在 [聚合数据](https://www.juhe.cn/docs/api/id/73) 申请「天气预报」能力后获取。不要把测试或生产密钥写进公开文档。

## 上游接口

实现固定请求：

```text
GET http://apis.juhe.cn/simpleWeather/query?city={urlencode(city)}&key={key}
```

| 项 | 本库实际行为 | 官方接口说明 |
| --- | --- | --- |
| 协议 | `http://` | 文档同时给出 `https://apis.juhe.cn/simpleWeather/query` |
| 方法 | GET（`Fize\Http\ClientOnce::get`） | GET / POST 均可 |
| `city` | `urlencode($city)` 后写入查询串 | 城市中文名或城市 ID，UTF-8 URL 编码 |
| `key` | `$this->config['key']` | AppKey |
| `future_num` | **未传** | 可选 `5` 或 `7`，默认 5 |

`getRealtime()` 与 `getFuture()` 使用**同一 URL**。一次成功响应里同时带有 `realtime` 与 `future`，但本类每次方法调用都会重新请求，不会复用上一次 body。

HTTP 由 [`fize/http`](https://packagist.org/packages/fize/http) 的 `ClientOnce::get()` 发出，返回 PSR-7 响应。响应体按 JSON 解码为关联数组。

## 方法

### `getRealtime($city)`

```php
public function getRealtime($city): WeatherItem
```

查询指定城市当前实况。

处理步骤：

1. `urlencode($city)`，读取 `$this->config['key']`。
2. GET 上述接口，`json_decode(..., true)`。
3. 若存在非 0 的 `error_code`，抛出 `RuntimeException`。
4. 读取 `$json['result']['realtime']`，写入新的 `WeatherItem`。

字段映射：

| WeatherItem | 来源 | 转换 |
| --- | --- | --- |
| `$date` | 本地 `date('Y-m-d')` | 不是接口字段 |
| `$info` | `realtime.info` | 原样 |
| `$wid` | `realtime.wid` | 原样 |
| `$temperature` | `realtime.temperature` | `(int)` |
| `$humidity` | `realtime.humidity` | `(int)` |
| `$direct` | `realtime.direct` | 原样 |
| `$power` | `realtime.power` | 去掉 `'级'` 后 `(int)` |
| `$aqi` | `realtime.aqi` | `(int)` |

不赋值：`$widDay`、`$widNight`、`$temperatureMax`、`$temperatureMin`。

### `getFuture($city)`

```php
public function getFuture($city): WeatherItem[]
```

查询指定城市近期预报，返回 `WeatherItem` 数组（接口默认约 5 天）。

前三步与 `getRealtime()` 相同，随后遍历 `$json['result']['future']`。

温度字符串处理：

```php
$temperature = str_replace('℃', '', $wf['temperature']); // 如 "1/7℃" → "1/7"
$temperature = explode('/', $temperature);
// $temperature[0] → temperatureMin
// $temperature[1] → temperatureMax
```

字段映射：

| WeatherItem | 来源 | 转换 |
| --- | --- | --- |
| `$date` | `future[].date` | 原样 |
| `$temperatureMin` | `future[].temperature` 左段 | 去 `℃`，按 `/` 拆分后 `(int)` |
| `$temperatureMax` | `future[].temperature` 右段 | 同上 |
| `$info` | `future[].weather` | 原样（注意接口字段名是 `weather` 不是 `info`） |
| `$widDay` | `future[].wid.day` | 原样 |
| `$widNight` | `future[].wid.night` | 原样 |
| `$direct` | `future[].direct` | 原样 |

不赋值：`$wid`、`$temperature`、`$humidity`、`$power`、`$aqi`。

若某天 `temperature` 不是 `最低/最高℃` 形式（缺测、空串、无斜杠），`explode` 后访问 `[1]` 可能产生未定义下标，`(int)` 后得到 `0`。

## 使用示例

经门面：

```php
use Fize\Provider\Weather\Weather;

$api = Weather::getInstance('JuHe', [
    'key' => '你的AppKey',
]);

$now = $api->getRealtime('厦门');
$days = $api->getFuture('厦门');
```

直接实例化（可同时持有不同 Key，也不受门面单例限制）：

```php
use Fize\Provider\Weather\Handler2\JuHe;

$api = new JuHe(['key' => '你的AppKey']);
$now = $api->getRealtime('110000'); // 也可用城市 ID（以聚合数据支持为准）
```

捕获接口错误：

```php
use Fize\Provider\Weather\Handler2\JuHe;
use RuntimeException;

$api = new JuHe(['key' => '你的AppKey']);

try {
    $now = $api->getRealtime('不存在的城市');
} catch (RuntimeException $e) {
    // $e->getMessage() 为 reason，如「查询不到该城市的相关信息」
    // $e->getCode() 为 error_code，如 207302
}
```

## 成功响应形态

上游成功时 `error_code` 为 `0`，结构与下面类似（数值会随城市与时间变化）：

```json
{
  "reason": "查询成功",
  "result": {
    "city": "苏州",
    "realtime": {
      "temperature": "4",
      "humidity": "82",
      "info": "阴",
      "wid": "02",
      "direct": "西北风",
      "power": "3级",
      "aqi": "80"
    },
    "future": [
      {
        "date": "2019-02-22",
        "temperature": "1/7℃",
        "weather": "小雨转多云",
        "wid": { "day": "07", "night": "01" },
        "direct": "北风转西北风"
      }
    ]
  },
  "error_code": 0
}
```

`result.city` 未被写入 `WeatherItem`。若业务需要回显规范化城市名，要自行保留原始 JSON 或再包一层。

## 错误处理

当 `isset($json['error_code']) && $json['error_code']` 为真时：

```php
throw new RuntimeException($json['reason'], (int)$json['error_code']);
```

`error_code` 为 `0` 视为成功（在 PHP 中 `0` 为假，不会进入该分支）。

聚合数据常见错误码（以官方文档为准）：

服务级：

| error_code | 说明 |
| --- | --- |
| 207301 | 错误的查询城市名 |
| 207302 | 查询不到该城市的相关信息 |
| 207303 | 网络错误，请重试 |

系统级（节选）：

| error_code | 说明 |
| --- | --- |
| 10001 | 错误的请求 KEY |
| 10002 | 该 KEY 无请求权限 |
| 10003 | KEY 过期 |
| 10008 | 被禁止的 IP |
| 10009 | 被禁止的 KEY |
| 10011 | 当前 IP 请求超过限制 |
| 10012 | 请求超过次数限制 |
| 10013 | 测试 KEY 超过请求限制 |
| 10014 | 系统内部异常 |
| 10020 | 接口维护 |
| 10021 | 接口停用 |

下列情况**不会**被上述 `error_code` 分支接住，可能表现为 PHP 警告、类型错误或 HTTP 库异常：

- 网络失败、DNS 失败、非 JSON 响应
- JSON 无法解码（`$json` 为 `null`）
- `error_code` 为 0 但缺少 `result.realtime` / `result.future`
- 配置缺少 `key`

## 天气标识（wid）

`wid` / `widDay` / `widNight` 对应聚合数据「天气种类列表」接口（`/simpleWeather/wids`）。本库不调用该接口，代码也不内置表。常见取值如下，完整列表以官方接口为准。

| 代码 | 天气 |
| --- | --- |
| 00 | 晴 |
| 01 | 多云 |
| 02 | 阴 |
| 03 | 阵雨 |
| 04 | 雷阵雨 |
| 05 | 雷阵雨伴有冰雹 |
| 06 | 雨夹雪 |
| 07 | 小雨 |
| 08 | 中雨 |
| 09 | 大雨 |
| 10 | 暴雨 |
| 11 | 大暴雨 |
| 12 | 特大暴雨 |
| 13 | 阵雪 |
| 14 | 小雪 |
| 15 | 中雪 |
| 16 | 大雪 |
| 17 | 暴雪 |
| 18 | 雾 |
| 19 | 冻雨 |
| 20 | 沙尘暴 |
| 21 | 小到中雨 |
| 22 | 中到大雨 |
| 23 | 大到暴雨 |
| 24 | 暴雨到大暴雨 |
| 25 | 大暴雨到特大暴雨 |
| 26 | 小到中雪 |
| 27 | 中到大雪 |
| 28 | 大到暴雪 |
| 29 | 浮尘 |
| 30 | 扬沙 |
| 31 | 强沙尘暴 |
| 53 | 霾 |
| 99 | 无 |

## 实现注意

1. **密钥出现在 URL 中。** GET 查询串包含 `key=`，明文 HTTP 下可能被日志或中间人看到。生产环境更稳妥的做法是改用 HTTPS，并避免把完整 URL 打进日志。
2. **实况日期取自运行环境。** 与被查询城市的当地日期可能不一致。
3. **预报温度解析较脆。** 依赖 `℃` 与 `/` 分隔；格式变化时最低/最高温会变成 `0` 或告警。
4. **风力只取整。** `"3级"` → `3`；区间字符串会被 `(int)` 截断。
5. **两次查询两次 HTTP。** 需要同时展示实况和预报时，仍会打两次相同接口。若要省配额，需要自行缓存响应或改实现一次解析两份数据。
6. **PSR-4 目录大小写。** 文件在 `src/handler/JuHe.php`，命名空间是 `Handler`。Linux CI 上建议把目录改为 `Handler2`。
7. **测试。** `tests/Handler/TestJuHe.php` 会真实调用接口，并 `var_dump` 结果；`use` 路径里的 `handler` 为小写。

## 测试对照

| 方法 | 测试 |
| --- | --- |
| `getRealtime()` | `tests/Handler/TestJuHe.php::testGetRealtime`、`tests/TestWeather.php::testGetInstance` |
| `getFuture()` | `tests/Handler/TestJuHe.php::testGetFuture` |

断言较弱：实时只检查返回值为 `WeatherItem` 实例，预报只检查返回值为数组，没有校验具体气象字段。
