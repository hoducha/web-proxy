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
use GuzzleHttp\Cookie\SetCookie;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class CookiePluginTest extends AbstractTestCase
{
    public function testSaveCookies()
    {
        $cookie = new SetCookie(array(
            'Name' => 'name1',
            'Value' => 'value1',
            'Domain' => 'example.com'
        ));

        $expectedResult = array(
            CookiePlugin::COOKIE_TARGET_DOMAIN => new SetCookie(array(
                'Name' => CookiePlugin::COOKIE_TARGET_DOMAIN,
                'Value' => 'example.com',
                'Domain' => 'proxy.local'
            )),
            CookiePlugin::COOKIE_PREFIX . 'name1' => new SetCookie(array(
                'Name' => CookiePlugin::COOKIE_PREFIX . 'name1',
                'Value' => 'value1',
                'Domain' => 'proxy.local'
            )),
        );

        $guzzle = $this->getGuzzle([
            new GuzzleResponse(
                200,
                array('Set-Cookie'=> (string) $cookie)
            ),
        ]);

        $proxy = new Proxy();
        $proxy->getClient()->setClient($guzzle);
        $proxy->setAppendUrl('http://proxy.local?u=');
        $proxy->getDispatcher()->addSubscriber(new CookiePlugin());

        $request = Request::create('/', 'GET');
        $response = $proxy->forward($request, 'http://example.com');
        $responseCookies = $response->headers->get('set-cookie', null, false);

        foreach ($responseCookies as $cookieString) {
            $cookie = SetCookie::fromString($cookieString);

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
        $browserCookies = array(
            CookiePlugin::COOKIE_TARGET_DOMAIN => 'example.com',
            CookiePlugin::COOKIE_PREFIX . 'name1' => 'value1'
        );
        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, array(), ''),
        ]);

        $proxy = new Proxy();
        $proxy->getClient()->setClient($guzzle);
        $proxy->setAppendUrl('http://proxy.local?u=');
        $proxy->setBrowserCookies($browserCookies);
        $proxy->getDispatcher()->addSubscriber(new CookiePlugin());

        $request = Request::create('/', 'GET');
        $proxy->forward($request, 'http://example.com');

        $this->assertNotEmpty($cookieJar = $proxy->getCookieJar());
        if ($cookieJar) {
            $this->assertNotEmpty($cookie = $cookieJar->get('name1', '/', 'example.com'));
            if ($cookie) {
                $this->assertEquals('value1', $cookie->getValue());
            }
        }
    }
}