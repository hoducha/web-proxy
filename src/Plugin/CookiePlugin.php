<?php
namespace Dootech\WebProxy\Plugin;

use Dootech\WebProxy\Plugin\AbstractPlugin;
use Dootech\WebProxy\Event\ProxyEvent;
use Dootech\WebProxy\Proxy;
use GuzzleHttp\Cookie\SetCookie;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpFoundation\Cookie as HttpCookie;

class CookiePlugin extends AbstractPlugin
{
    const COOKIE_PREFIX = '__target_';
    const COOKIE_TARGET_DOMAIN = '__targetDomain';

    public function onBeforeSend(ProxyEvent $event)
    {
        $proxy = isset($event['proxy']) ? $event['proxy'] : null;

        if ($proxy) {
            $browserCookies = $proxy->getBrowserCookies();
            if ($browserCookies) {
                $targetDomain = parse_url($proxy->getTargetUrl(), PHP_URL_HOST);

                if (isset($browserCookies[self::COOKIE_TARGET_DOMAIN]) && $browserCookies[self::COOKIE_TARGET_DOMAIN] == $targetDomain) {
                    $cookieJar = new CookieJar();
                    foreach ($browserCookies as $name => $value) {
                        if (preg_match('/^'.self::COOKIE_PREFIX.'(.+)$/', $name, $matches)) {
                            $cookieJar->set(new Cookie($matches[1], $value, $expires = null, $path = null, $domain = $targetDomain));
                        }
                    }
                    $proxy->setCookieJar($cookieJar);
                } else {
                    // Unset cookies if target domain is changed
                    foreach ($browserCookies as $name => $value) {
                        if (preg_match('/^'.self::COOKIE_PREFIX.'/', $name)) {
                            setcookie($name, '', time() - 1000);
                            setcookie($name, '', time() - 1000, '/');
                        }
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
            $targetDomain = parse_url($proxy->getTargetUrl(), PHP_URL_HOST);

            $response = $proxy->getResponse();
            $responseCookies = $response->headers->get('set-cookie', null, false);

            foreach ($responseCookies as $key => $value) {
                $c = SetCookie::fromString($value);
                $expires = $c->getExpires() ? $c->getExpires() : 0;
                $response->headers->setCookie(
                    new HttpCookie(self::COOKIE_PREFIX . $c->getName(), $c->getValue(), $expires,
                        '/', $proxyDomain, $c->getSecure(), $c->getHttpOnly())
                );
            }

            $response->headers->setCookie(new HttpCookie(self::COOKIE_TARGET_DOMAIN, $targetDomain, time() + (3600 * 24), '/', $proxyDomain));
        }
    }
}