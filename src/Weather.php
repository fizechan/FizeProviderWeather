<?php

namespace fize\provider\weather;

/**
 * 天气查询
 */
class Weather
{
    /**
     * @var WeatherHandler 接口处理器
     */
    protected static $handler;

    /**
     * 取得单例
     * @param string $handler 使用的实际接口名称
     * @param array $config 配置项
     * @return WeatherHandler
     */
    public static function getInstance($handler, array $config = [])
    {
        if (empty(self::$handler)) {
            $class = '\\' . __NAMESPACE__ . '\\handler\\' . $handler;
            self::$handler = new $class($config);
        }
        return self::$handler;
    }
}
