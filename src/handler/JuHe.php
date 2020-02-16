<?php


namespace fize\provider\weather\handler;

use RuntimeException;
use fize\net\Http;
use fize\crypt\Json;
use fize\provider\weather\WeatherHandler;
use fize\provider\weather\WeatherItem;

/**
 * 聚合数据
 */
class JuHe extends WeatherHandler
{

    /**
     * 获取当前天气详情情况
     * @param string $city 城市名
     * @return WeatherItem
     */
    public function getRealtime($city)
    {
        $city = urlencode($city);
        $key = $this->config['key'];
        $response = Http::get("http://apis.juhe.cn/simpleWeather/query?city={$city}&key={$key}");
        if ($response === false) {
            throw new RuntimeException(Http::getLastErrMsg(), Http::getLastErrCode());
        }

        $json = Json::decode($response);
        if (isset($json['error_code']) && $json['error_code']) {
            throw new RuntimeException($json['reason'], (int)$json['error_code']);
        }

        $result = $json['result']['realtime'];
        $item = new WeatherItem();
        $item->date = date('Y-m-d');
        $item->info = $result['info'];
        $item->wid = $result['wid'];
        $item->temperature = (int)$result['temperature'];
        $item->humidity = (int)$result['humidity'];
        $item->direct = $result['direct'];
        $item->power = (int)str_replace('级', '', $result['power']);
        $item->aqi = (int)$result['aqi'];
        return $item;
    }

    /**
     * 获取未来天气详情情况
     * @param string $city 城市名
     * @return WeatherItem[]
     */
    public function getFuture($city)
    {
        $city = urlencode($city);
        $key = $this->config['key'];
        $response = Http::get("http://apis.juhe.cn/simpleWeather/query?city={$city}&key={$key}");
        if ($response === false) {
            throw new RuntimeException(Http::getLastErrMsg(), Http::getLastErrCode());
        }

        $json = Json::decode($response);
        if (isset($json['error_code']) && $json['error_code']) {
            throw new RuntimeException($json['reason'], (int)$json['error_code']);
        }

        $items = [];
        foreach ($json['result']['future'] as $wf) {
            $temperature = str_replace('℃', '', $wf['temperature']);
            $temperature = explode('/', $temperature);

            $item = new WeatherItem();
            $item->date = $wf['date'];
            $item->temperatureMin = (int)$temperature[0];
            $item->temperatureMax = (int)$temperature[1];
            $item->info = $wf['weather'];
            $item->widDay = $wf['wid']['day'];
            $item->widNight = $wf['wid']['night'];
            $item->direct = $wf['direct'];

            $items[] = $item;
        }
        return $items;
    }
}
