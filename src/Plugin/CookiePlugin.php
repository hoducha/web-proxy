<?php
namespace Dootech\WebProxy\Plugin;

use Dootech\WebProxy\Plugin\AbstractPlugin;
use Dootech\WebProxy\Event\ProxyEvent;

class CookiePlugin extends AbstractPlugin
{
    const COOKIE_PREFIX = 'webproxy_';
    const COOKIE_TARGET_DOMAIN = '__targetDomain';

    public function onBeforeRequest(ProxyEvent $event)
    {
        $request = $event['request'];
        $cookieJar = $event['cookieJar'];

        if ($_COOKIE) {
            if (isset($_COOKIE[self::COOKIE_TARGET_DOMAIN]) && $_COOKIE[self::COOKIE_TARGET_DOMAIN] == $targetDomain) {
                $cookieJar = new CookieJar();
                foreach ($_COOKIE as $name => $value) {
                    if (preg_match('/^__target_(.+)$/', $name, $matches)) {
                        $cookieJar->set(new Cookie($matches[1], $value, $expires = null, $path = null, $domain = $targetDomain));
                    }
                }
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