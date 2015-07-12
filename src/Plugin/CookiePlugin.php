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
                //     $cookieJar->setCookie(new SetCookie(array("Name"=>$name, "Value"=>$value, "Domain"=>$targetDomain, 'Path'=>'/' . $request->getRequestUri())));
                //     $cookie = new Cookie($cookieJar);
                //     $client->getClient()->getEmitter()->attach($cookie);
            }
        }
    }

    public function onRequestComplete(ProxyEvent $event)
    {
        $proxy = isset($event['proxy']) ? $event['proxy'] : null;

        if ($proxy) {
            $proxyDomain = parse_url($proxy->getAppendUrl(), PHP_URL_HOST);
            $targetDomain = parse_url($proxy->getTargetUrl(), PHP_URL_HOST);
            $targetRootDomain = implode('.', array_slice(explode('.', $targetDomain), -2, 2));

            $response = $proxy->getResponse();
            $responseCookies = $response->headers->get('set-cookie', null, false);

            foreach ($responseCookies as $key => $value) {
                $c = SetCookie::fromString($value);
                if ($c->matchesDomain($targetDomain) || $c->matchesDomain($targetRootDomain)) {
                    $c->setDomain($proxyDomain);
                    $c->setName(self::COOKIE_PREFIX . $c->getName());
                    $responseCookies[$key] = (string)$c;
                }
            }

            $response->headers->set('set-cookie', $responseCookies);
            $response->headers->setCookie(new HttpCookie(self::COOKIE_TARGET_DOMAIN, $targetDomain, time() + (3600 * 24)));
        }
    }
}