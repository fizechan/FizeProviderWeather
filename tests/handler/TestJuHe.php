<?php

namespace handler;

use fize\provider\weather\handler\JuHe;
use fize\provider\weather\WeatherItem;
use PHPUnit\Framework\TestCase;

class TestJuHe extends TestCase
{

    public function testGetRealtime()
    {
        $config = [
            'key' => '085d199ff4f6a6a77f096e2497f29029'
        ];
        $juhe = new JuHe($config);
        $item = $juhe->getRealtime('厦门');
        var_dump($item);
        self::assertInstanceOf(WeatherItem::class, $item);
    }

    public function testGetFuture()
    {
        $config = [
            'key' => '085d199ff4f6a6a77f096e2497f29029'
        ];
        $juhe = new JuHe($config);
        $items = $juhe->getFuture('厦门');
        var_dump($items);
        self::assertIsArray($items);
    }
}
