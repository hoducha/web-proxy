<?php
use Dootech\WebProxy\Proxy;

class ProxyTest extends PHPUnit_Framework_TestCase {
    public function testProxy() {
        $proxy = new Proxy();
        $this->assertEquals('test', $proxy->test());
    }
}