<?php


use fize\provider\weather\Weather;
use fize\provider\weather\WeatherItem;
use PHPUnit\Framework\TestCase;

class TestWeather extends TestCase
{

    public function testGetInstance()
    {

        $config = [
            'key' => '085d199ff4f6a6a77f096e2497f29029'
        ];
        $api = Weather::getInstance('JuHe', $config);
        $item = $api->getRealtime('厦门');
        var_dump($item);
        self::assertInstanceOf(WeatherItem::class, $item);
    }
}
