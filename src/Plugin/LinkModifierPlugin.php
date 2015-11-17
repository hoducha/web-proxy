<?php
namespace Dootech\WebProxy\Plugin;

use Dootech\WebProxy\Event\ProxyEvent;
use Dootech\WebProxy\Parser\ContentParser;
use Symfony\Component\DomCrawler\Crawler;

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
            $contentParser->setEnableInjectedAjaxFix(false);
            $content = null;

            if (strpos($contentType, 'text/html') !== FALSE) {
                $contentParser->setBaseHref($this->getBaseHref($response->getContent()));
                $content = $contentParser->parseHTML();

            } else if (strpos($contentType, 'text/css') !== FALSE) {
                $content = $contentParser->parseCss();

            } else if (strpos($contentType, 'text/javascript') !== FALSE
                || strpos($contentType, 'application/javascript') !== FALSE
                || strpos($contentType, 'application/x-javascript') !== FALSE) {
                    $content = $contentParser->parseJS();
            }

            if ($content) {
                $response->setContent($content);
                $response->headers->set('Content-Length', strlen($content));
            }
        }
    }

    /**
     * Get base url from <base> tag
     *
     * @param $html
     * @return null|string
     */
    private function getBaseHref($html)
    {
        $crawler = new Crawler($html);
        $baseNode = $crawler->filterXPath('//base')->getNode(0);
        if ($baseNode) {
            return $baseNode->getAttribute('href');
        } else {
            return null;
        }
    }
}