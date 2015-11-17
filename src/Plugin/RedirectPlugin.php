<?php
namespace Dootech\WebProxy\Plugin;

use Dootech\WebProxy\Event\ProxyEvent;

class RedirectPlugin extends AbstractPlugin
{
    private $redirectingContentTypes = array();
    private $redirectImage = false;
    private $redirectVideo = false;
    private $redirectAudio = false;

    public function __construct($options = array())
    {
        if (in_array('image', $options)) {
            $this->redirectImage = true;
        }
        if (in_array('video', $options)) {
            $this->redirectVideo = true;
        }
        if (in_array('audio', $options)) {
            $this->redirectAudio = true;
        }
    }

    public function onBeforeSend(ProxyEvent $event)
    {
        $proxy = isset($event['proxy']) ? $event['proxy'] : null;

        if ($proxy) {
            try {
//                $headers = get_headers($proxy->getTargetUrl(), 1);
//                $contentType = isset($headers['Content-Type']) ? $headers['Content-Type'] : null;

                $response = $proxy->getClient()->getClient()->head($proxy->getTargetUrl(), ['timeout' => 2]);
                $headerContentTypes = $response->getHeader('Content-Type');
                $contentType = array_shift($headerContentTypes);

                if ($contentType) {
                    if (   in_array($contentType, $this->redirectingContentTypes)
                        || ($this->redirectImage && preg_match('/^image/', $contentType))
                        || ($this->redirectVideo && preg_match('/^video/', $contentType))
                        || ($this->redirectAudio && preg_match('/^audio/', $contentType)))
                    {

                        $proxy->setRedirect(true);

                        // Stop all propagation of the event to future listeners
                        $event->stopPropagation();
                    }
                }
            } catch (\Exception $e) {

            }


        }
    }

    public function addContentTypes($contentTypes = array())
    {
        $this->redirectingContentTypes = array_merge($this->redirectingContentTypes, $contentTypes);
    }

    public function enableRedirectImage()
    {
        $this->redirectImage = true;
    }

    public function enableRedirectVideo()
    {
        $this->redirectVideo = true;
    }

    public function enableRedirectAudio()
    {
        $this->redirectAudio = true;
    }

}