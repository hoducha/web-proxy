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
    const COOKIE_PREFIX = 'webproxy_';
    const COOKIE_TARGET_DOMAIN = '__targetDomain';

    public function onBeforeSend(ProxyEvent $event)
    {
        $proxy = isset($event['proxy']) ? $event['proxy'] : null;

        if ($proxy) {
            if ($_COOKIE) {
                $targetDomain = parse_url($proxy->getTargetUrl(), PHP_URL_HOST);

                if (isset($_COOKIE[self::COOKIE_TARGET_DOMAIN]) && $_COOKIE[self::COOKIE_TARGET_DOMAIN] == $targetDomain) {
                    $cookieJar = new CookieJar();
                    foreach ($_COOKIE as $name => $value) {
                        if (preg_match('/^__target_(.+)$/', $name, $matches)) {
                            $cookieJar->set(new Cookie($matches[1], $value, $expires = null, $path = null, $domain = $targetDomain));
                        }
                    }
                    $proxy->setCookieJar($cookieJar);
                } else {
                    // Unset cookies if target domain is changed
                    foreach ($_COOKIE as $name => $value) {
                        if (preg_match('/^__target_/', $name)) {
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
                    $c->setName('__target_' . $c->getName());
                    $responseCookies[$key] = (string)$c;
                }
            }

            $response->headers->set('set-cookie', $responseCookies);
            $response->headers->setCookie(new HttpCookie('__targetDomain', $targetDomain, time() + (3600 * 24)));
        }
    }
}