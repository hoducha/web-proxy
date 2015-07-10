<?php

namespace Dootech\WebProxy\Plugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Dootech\WebProxy\Event\ProxyEvent;

abstract class AbstractPlugin implements EventSubscriberInterface
{

    /**
     * Apply these methods only to those events whose request URL passes this filter
     * @var string
     */
    protected $urlPattern;

    /**
     * Define events emitted from a request
     * @see https://github.com/guzzle/guzzle3/blob/master/docs/http-client/request.rst
     * @return array
     */
    final public static function getSubscribedEvents()
    {
        return array(
            'request.before_send' => 'onBeforeSend',
            'request.sent' => 'onRequestSent',
            'request.complete' => 'onRequestComplete',
            'request.success' => 'onRequestSuccess',
            'request.error' => 'onRequestError',
            'request.exception' => 'onException',
            'request.receive.status_line' => 'onReceiveStatusLine',
            'curl.callback.progress' => 'onCurlProgress',
            'curl.callback.write' => 'onCurlWrite',
            'curl.callback.read' => 'onCurlRead'
        );
    }

    public function onBeforeSend(ProxyEvent $event)
    {
    }

    public function onRequestSent(ProxyEvent $event)
    {
    }

    public function onRequestComplete(ProxyEvent $event)
    {
    }

    public function onRequestSuccess(ProxyEvent $event)
    {
    }

    public function onRequestError(ProxyEvent $event)
    {
    }

    public function onException(ProxyEvent $event)
    {
    }

    public function onReceiveStatusLine(ProxyEvent $event)
    {
    }

    public function onCurlProgress(ProxyEvent $event)
    {
    }

    public function onCurlWrite(ProxyEvent $event)
    {
    }

    public function onCurlRead(ProxyEvent $event)
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

}

?>