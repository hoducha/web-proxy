<?php
namespace Dootech\WebProxy\Plugin;

use Dootech\WebProxy\Event\ProxyEvent;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Cookie as HttpCookie;

/**
 * CookiePlugin is used to persist cookies to browsers
 */
class CookiePlugin extends AbstractPlugin
{
    const COOKIE_PREFIX = '__target_';

    private $browserCookies = array();

    public function __construct()
    {
        $proxy = isset($event['proxy']) ? $event['proxy'] : null;
        if ($proxy) {
            // Do not persist the cookies on the server side if the CookiePlugin is enabled
            $proxy->getClient()->setGuzzleCookieJar(new CookieJar());
        }
    }

    public function onBeforeSend(ProxyEvent $event)
    {
        $proxy = isset($event['proxy']) ? $event['proxy'] : null;
        if ($proxy) {
            $this->browserCookies = array_merge($this->browserCookies, $_COOKIE);
            if ($this->browserCookies) {
                $targetDomain = parse_url($proxy->getTargetUrl(), PHP_URL_HOST);

                foreach ($this->browserCookies as $name => $value) {
                    if (preg_match('/^'.self::COOKIE_PREFIX.'(.+)$/', $name, $matches)) {
                        $proxy->getClient()->getCookieJar()->set(new Cookie($matches[1], $value, null, null, $targetDomain));
                    }
                }
            }
        }
    }

    public function onRequestComplete(ProxyEvent $event)
    {
        $proxy = isset($event['proxy']) ? $event['proxy'] : null;

        if ($proxy) {
            $proxyDomain = parse_url($proxy->getAppendUrl(), PHP_URL_HOST);
            $response = $proxy->getResponse();
            $responseCookies = $response->headers->get('set-cookie', null, false);

            foreach ($responseCookies as $key => $value) {
                $c = SetCookie::fromString(urldecode($value));
                $expires = $c->getExpires() ? $c->getExpires() : 0;
                $response->headers->setCookie(
                    new HttpCookie(self::COOKIE_PREFIX . $c->getName(), $c->getValue(), $expires,
                        '/', $proxyDomain, $c->getSecure(), $c->getHttpOnly())
                );
            }
        }
    }

    public function addCookie($name, $value)
    {
        $this->browserCookies[$name] = $value;
    }


}