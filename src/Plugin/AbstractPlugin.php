<?php

namespace Dootech\WebProxy\Plugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Dootech\WebProxy\Event\ProxyEvent;

abstract class AbstractPlugin implements EventSubscriberInterface
{

    // Apply these methods only to those events whose request URL passes this filter
    protected $urlPattern;

    public function onRequest(ProxyEvent $event)
    {
    }

    public function onResponse(ProxyEvent $event)
    {
    }

    public function onCompleted(ProxyEvent $event)
    {
    }

    public function validateUrlPattern(ProxyEvent $event)
    {
        $request = isset($event['request']) ? $event['request'] : null;
        if ($request) {
            $url = $event['request']->getUri();
            if (preg_match($this->urlPattern, $url)) {
                return true;
            }
        }

        return false;
    }

    final public static function getSubscribedEvents()
    {
        return array(
            'proxy.on_request' => 'onRequest',
            'proxy.on_response' => 'onResponse',
            'proxy.on_completed' => 'onCompleted'
        );
    }
}

?>