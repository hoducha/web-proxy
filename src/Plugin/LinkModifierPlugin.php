<?php
namespace Dootech\WebProxy\Plugin;

use Dootech\WebProxy\Event\ProxyEvent;
use Dootech\WebProxy\Parser\ContentParser;

class LinkModifierPlugin extends AbstractPlugin
{
    public function onRequestComplete(ProxyEvent $event)
    {
        $proxy = isset($event['proxy']) ? $event['proxy'] : null;
        $response = !empty($proxy) ? $proxy->getResponse() : null;
        if ($response) {
            $contentType = $response->headers->get('Content-Type');
            $contentParser = new ContentParser($response->getContent(), $proxy->getTargetUrl(), $proxy->getAppendUrl());
            $contentParser->setEnableJavascriptParsing(true);
            $contentParser->setEnableInjectedAjaxFix(true);
            $content = null;

            if (strpos($contentType, 'text/html') !== FALSE) {
                $content = $contentParser->parseHTML();

            } else if (strpos($contentType, 'text/css') !== FALSE) {
                $content = $contentParser->parseCss();

            } else if (strpos($contentType, 'text/javascript') !== FALSE
                || strpos($contentType, 'application/javascript') !== FALSE
                || strpos($contentType, 'application/x-javascript') !== FALSE) {
                    $content = $contentParser->parseJS();

            } else if (substr($contentType,0,6) == 'image/') {
                // TODO: Create image filter
            } else if (substr($contentType,0,6) == 'video/' || substr($contentType,0,6) != 'audio/') {
                // TODO: Create streaming filter for video/audio files
            } else {
                // Do not recognize the content type.
            }

            if ($content) {
                $response->setContent($content);
                $response->headers->set('Content-Length', strlen($content));
            }
        }
    }
}