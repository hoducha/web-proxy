<?php

namespace Dootech\WebProxy\Plugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Dootech\WebProxy\Event\ProxyEvent;

abstract class AbstractPlugin implements EventSubscriberInterface {

	// Apply these methods only to those events whose request URL passes this filter
	protected $urlPattern;

	public function onBeforeRequest(ProxyEvent $event) {}

	public function onCompleted(ProxyEvent $event) {}

	// Dispatch based on filter
	final public function route(ProxyEvent $event) {

		$url = $event['request']->getUri();
		if ($this->urlPattern && strpos($url, $this->urlPattern) === false) {
			return;
		}

		switch ($event->getName()) {
			case 'request.before_send':
                $this->onBeforeRequest($event);
                break;

			case 'request.complete':
				$this->onCompleted($event);
                break;
		}
	}

	final public static function getSubscribedEvents() {
		return array(
			'request.before_send' => 'route',
			'request.complete' => 'route'
		);
	}
}

?>