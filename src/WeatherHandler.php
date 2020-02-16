<?php


namespace fize\provider\weather;

/**
 * 接口：天气
 */
abstract class WeatherHandler
{

    /**
     * @var array 配置
     */
    protected $config;

    /**
     *  构造
     * @param array $config 配置
     */
    public function __construct(array $config = null)
    {
        $this->config = $config;
    }

    /**
     * 获取当前天气详情情况
     * @param string $city 城市名
     * @return WeatherItem
     */
    abstract public function getRealtime($city);

    /**
     * 获取未来天气详情情况
     * @param string $city 城市名
     * @return WeatherItem[]
     */
    abstract public function getFuture($city);
}
