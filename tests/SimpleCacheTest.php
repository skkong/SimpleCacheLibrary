<?php
require dirname(__DIR__) . '/SimpleCache.php';

use PHPUnit\Framework\TestCase;

class SimpleCacheTest extends TestCase
{
    // 문자열 캐시 테스트
    public function testCacheString ()
    {
        // 사용 예제
        $cache = CacheFactory::create("array");
        $cache->load();

        // echo "문자열 캐시 test \n";
        $cache->setValue("name1", "skkong");
        $result = $cache->getValue("name1");

        // test
        $this->assertTrue($result == 'skkong');

        $cache->save();

    }

    // 배열 캐시 테스트
    public function testCacheArray ()
    {
        // 사용 예제
        $cache = CacheFactory::create("array");
        $cache->load();

        // echo "문자열 캐시 test \n";
        $cache->setValue("name2", array('skkong', 'test'));
        $result = $cache->getValue("name2");

        // test
        $this->assertTrue($result[0] == 'skkong');

        $cache->save();

    }

    // 배열 캐시 테스트
    public function testCacheAssociateArray ()
    {
        // 사용 예제
        $cache = CacheFactory::create("array");
        $cache->load();

        // echo "문자열 캐시 test \n";
        $cache->setValue("name2", ['skkong' => 46, 'test' => 36], 60);
        $result = $cache->getValue("name2");

        // test
        $this->assertTrue($result['skkong'] == 46);

        $cache->save();

    }

}

