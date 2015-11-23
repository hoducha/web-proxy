<?php
/**
 * Created by PhpStorm.
 * User: Ha
 * Date: 7/11/2015
 * Time: 4:50 PM
 */

namespace Dootech\WebProxy\Test;


use Dootech\WebProxy\Plugin\CookiePlugin;
use Dootech\WebProxy\Proxy;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class CookiePluginTest extends AbstractTestCase
{
    public function testSaveCookies()
    {
        $expires = time() + 3600;

        $cookies = array(
            new Cookie('name1', 'value1', $expires, '/', 'example.com'),
            new Cookie('name2', 'value2', $expires, '/path', 'example.com')
        );

        $expectedResult = array(
            CookiePlugin::COOKIE_PREFIX . 'name1' => new Cookie(CookiePlugin::COOKIE_PREFIX . 'name1', 'value1', $expires, '/', 'proxy.local'),
            CookiePlugin::COOKIE_PREFIX . 'name2' => new Cookie(CookiePlugin::COOKIE_PREFIX . 'name2', 'value2', $expires, '/path', 'proxy.local'),
        );

        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, array('Set-Cookie' => $cookies)),
        ]);

        $proxy = new Proxy();
        $proxy->getClient()->setClient($guzzle);
        $proxy->setAppendUrl('http://proxy.local?u=');
        $proxy->getDispatcher()->addSubscriber(new CookiePlugin());

        $request = Request::create('/', 'GET');
        $response = $proxy->forward($request, 'http://example.com');
        $responseCookies = $response->headers->getCookies();

        $this->assertEquals(count($expectedResult), count($responseCookies));

        foreach ($responseCookies as $cookie) {
            // The cookie has to be in the expected result
            $this->assertArrayHasKey($cookie->getName(), $expectedResult);

            // Compare value, domain...
            if (array_key_exists($cookie->getName(), $expectedResult)) {
                $this->assertEquals($expectedResult[$cookie->getName()]->getValue(), $cookie->getValue());
                $this->assertEquals($expectedResult[$cookie->getName()]->getDomain(), $cookie->getDomain());
            }
        }
    }

    public function testSendCookies()
    {
        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, array(), ''),
        ]);

        $proxy = new Proxy();
        $proxy->getClient()->setClient($guzzle);
        $proxy->setAppendUrl('http://proxy.local?u=');

        $cookiePlugin = new CookiePlugin();
        $cookiePlugin->addCookie(CookiePlugin::COOKIE_PREFIX . 'name1', 'value1');
        $cookiePlugin->addCookie(CookiePlugin::COOKIE_PREFIX . 'name2', 'value2');

        $proxy->getDispatcher()->addSubscriber($cookiePlugin);

        $request = Request::create('/', 'GET');
        $proxy->forward($request, 'http://example.com');

        $this->assertNotEmpty($cookieJar = $proxy->getClient()->getCookieJar());
        if ($cookieJar) {
            $this->assertNotEmpty($cookie = $cookieJar->get('name1', '/', 'example.com'));
            if ($cookie) {
                $this->assertEquals('value1', $cookie->getValue());
            }
        }
    }
}