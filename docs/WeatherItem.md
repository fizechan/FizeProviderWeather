# WeatherItem

统一天气数据项。实时查询返回一个实例，预报查询返回实例数组。属性全部公开，没有 getter / setter。

- 命名空间：`Fize\Provider\Weather`
- 文件：`src/WeatherItem.php`
- 完全限定名：`Fize\Provider\Weather\WeatherItem`
- 修饰：`final class`（不可再被继承）

返回目录：[index.md](index.md)

相关类：[WeatherHandler](WeatherHandler.md) · [JuHe](JuHe.md)

## 职责

`WeatherItem` 是简单数据对象（DTO），用来抹平各家天气接口的字段差异。调用方应只读取这些公开属性，不要依赖上游 JSON 结构。

类本身不包含单位换算、天气代码翻译或校验逻辑。数值字段在 `JuHe` 中会尽量转为 `int`；字符串字段保持上游文本。

## 属性一览

源码中所有属性均为 `public`，未赋默认值，未使用前为 `null`。

| 属性 | PHPDoc 类型 | 含义 | 典型单位 / 格式 |
| --- | --- | --- | --- |
| `$date` | `string` | 日期 | `Y-m-d`，如 `2026-09-16` |
| `$info` | `string` | 天气情况文字 | 如 `晴`、`多云`、`小雨转多云` |
| `$wid` | `string` | 天气标识 | 两位数字字符串，如 `00`、`01` |
| `$widDay` | `string` | 白天天气标识 | 同 `$wid` |
| `$widNight` | `string` | 夜间天气标识 | 同 `$wid` |
| `$temperature` | `int` | 当前温度 | 摄氏度 |
| `$temperatureMax` | `int` | 最高气温 | 摄氏度 |
| `$temperatureMin` | `int` | 最低气温 | 摄氏度 |
| `$humidity` | `int` | 相对湿度 | 百分数，如 `82` 表示 82% |
| `$direct` | `string` | 风向 | 如 `西北风`、`北风转东北风` |
| `$power` | `int` | 风力等级 | 整数级数，已去掉「级」 |
| `$aqi` | `int` | 空气质量指数 | AQI 数值 |

源码里 `$temperatureMin` 的注释误写为「最高温」，与 `$temperatureMax` 重复。按字段名与 `JuHe` 的赋值方向，`$temperatureMin` 是最低温，`$temperatureMax` 是最高温。

## 字段填充对照

`WeatherItem` 是超集。同一次查询不会填满全部属性，未赋值的保持 `null`。以内置 [`JuHe`](JuHe.md) 为例：

| 属性 | `getRealtime()` | `getFuture()` |
| --- | --- | --- |
| `$date` | 填：PHP 运行环境当天 `date('Y-m-d')` | 填：接口返回的预报日期 |
| `$info` | 填：`realtime.info` | 填：`future[].weather` |
| `$wid` | 填：`realtime.wid` | 不填 |
| `$widDay` | 不填 | 填：`future[].wid.day` |
| `$widNight` | 不填 | 填：`future[].wid.night` |
| `$temperature` | 填：当前气温 | 不填 |
| `$temperatureMax` | 不填 | 填：温度区间上界 |
| `$temperatureMin` | 不填 | 填：温度区间下界 |
| `$humidity` | 填 | 不填 |
| `$direct` | 填 | 填 |
| `$power` | 填（去掉「级」后转 int） | 不填 |
| `$aqi` | 填 | 不填 |

读取前先确认当前对象来自哪一个方法，例如：

```php
$now = $handler->getRealtime('厦门');
$now->temperature;      // 有值
$now->temperatureMax;   // 一般为 null

$days = $handler->getFuture('厦门');
$days[0]->temperatureMin; // 有值
$days[0]->aqi;            // 一般为 null
```

自定义处理器可以自行决定填充哪些字段，但建议与上表语义保持一致，避免调用方难以复用。

## 取值含义

### `$date`

- 实况：`JuHe` **不使用**接口返回值，而是用服务器/PHP 进程的本地日历日。若 PHP 时区与被查询城市不一致，日期可能对不上当地「今天」。
- 预报：来自接口，格式为 `Y-m-d`。

### `$info`

人类可读的天气描述。实况通常是单一现象（`阴`、`多云`）；预报可能是转折描述（`小雨转多云`）。

### `$wid` / `$widDay` / `$widNight`

天气现象代码，便于图标映射。聚合数据以字符串形式返回，例如 `"02"`。常用对照见 [JuHe 天气标识](JuHe.md#天气标识-wid)。

实况只有一个 `$wid`；预报把白天/夜间拆成 `$widDay` 与 `$widNight`。

### `$temperature` / `$temperatureMin` / `$temperatureMax`

- 实况当前气温 → `$temperature`
- 预报区间字符串如 `1/7℃` → 去掉 `℃` 后按 `/` 拆成最低 / 最高

`JuHe` 使用 `(int)` 转换，空字符串会变成 `0`，并不是 `null`。若上游缺测，需要调用方自行判断业务含义。

### `$humidity`

相对湿度整数。仅实况有值。

### `$direct`

风向文本，预报里可能包含「转」，如 `北风转西北风`。

### `$power`

`JuHe` 对 `realtime.power`（如 `3级`）执行 `str_replace('级', '', ...)` 再转 int。若上游返回 `3-4级` 这类区间，`(int)'3-4'` 会得到 `3`。预报不提供风力。

### `$aqi`

空气质量指数，仅实况有值。数值含义遵循常规 AQI 分段（优 / 良 / 轻度污染等），本库不做分级。

## 使用示例

```php
use Fize\Provider\Weather\Weather;
use Fize\Provider\Weather\WeatherItem;

$handler = Weather::getInstance('JuHe', ['key' => '你的AppKey']);

/** @var WeatherItem $now */
$now = $handler->getRealtime('苏州');

printf(
    "%s %s %d℃ 湿度%d%% %s%d级 AQI %d\n",
    $now->date,
    $now->info,
    $now->temperature,
    $now->humidity,
    $now->direct,
    $now->power,
    $now->aqi
);

foreach ($handler->getFuture('苏州') as $day) {
    printf(
        "%s %s %d/%d℃ %s\n",
        $day->date,
        $day->info,
        $day->temperatureMin,
        $day->temperatureMax,
        $day->direct
    );
}
```

## 设计备注

- **`final`。** 不能通过继承给数据项加字段。若需要额外信息（日出日落、生活指数等），应新建自己的结构，或在处理器外保留原始 JSON。
- **无构造函数。** 使用 `new WeatherItem()` 后逐个赋属性。属性未声明类型，赋值不受 PHP 类型约束。
- **不是 PSR 标准对象。** 没有 `JsonSerializable`、`ArrayAccess` 或 `toArray()`。若要输出 JSON，需自行读取属性。
- **可变。** 所有属性公开可写。当作只读 DTO 使用即可，不要在业务层改写后再传给其他人。
