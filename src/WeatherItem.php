<?php


namespace fize\provider\weather;

/**
 * 天气项
 */
final class WeatherItem
{

    /**
     * @var string 日期
     */
    public $date;

    /**
     * @var string 天气情况
     */
    public $info;

    /**
     * @var string 天气标识
     */
    public $wid;

    /**
     * @var string 白天天气
     */
    public $widDay;

    /**
     * @var string 夜间天气
     */
    public $widNight;

    /**
     * @var int 温度
     */
    public $temperature;

    /**
     * @var int 最高温
     */
    public $temperatureMax;

    /**
     * @var int 最高温
     */
    public $temperatureMin;

    /**
     * @var int 湿度
     */
    public $humidity;

    /**
     * @var string 风向
     */
    public $direct;

    /**
     * @var int 风力
     */
    public $power;

    /**
     * @var int 空气质量指数
     */
    public $aqi;
}
