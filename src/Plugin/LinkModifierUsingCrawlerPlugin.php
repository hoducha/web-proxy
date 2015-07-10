<?php
namespace Dootech\WebProxy\Plugin;

use Dootech\WebProxy\Event\ProxyEvent;
use Symfony\Component\DomCrawler\Crawler;

class LinkModifierUsingCrawlerPlugin extends AbstractPlugin
{
    public function onRequestComplete(ProxyEvent $event)
    {
        $proxy = isset($event['proxy']) ? $event['proxy'] : null;
        $response = !empty($proxy) ? $proxy->getResponse() : null;
        if ($response) {
            $contentType = $response->headers->get('Content-Type');
            if (strpos($contentType, 'text/html') !== false || strpos($contentType, 'text/javascript') !== false) {
                $targetUrl = $proxy->getTargetUrl();
                $appendUrl = $proxy->getAppendUrl();
                $siteBaseUrl = $this->getBaseUrl($targetUrl);
                $siteDomain = parse_url($targetUrl, PHP_URL_HOST);

                $crawler = new Crawler($response->getContent());

                // Modify a href
                foreach ($crawler->filterXpath('//a') as $element) {
                    $this->modifyLink($element, 'href', $siteDomain, $siteBaseUrl, $appendUrl);
                }

                // Modify image src.
                foreach ($crawler->filterXpath('//img') as $element) {
                    $this->modifyLink($element, 'src', $siteDomain, $siteBaseUrl, null);
                }

                // Modify form action
                foreach ($crawler->filterXpath('//form') as $element) {
                    $this->modifyLink($element, 'action', $siteDomain, $siteBaseUrl, $appendUrl);
                }

                $response->setContent($this->getHtml($crawler));
            }
        }
    }

    private function getHtml(Crawler $crawler) {
        return "<!DOCTYPE html><html>" . $crawler->html() . "</html>";
    }

    private function modifyLink(\DOMElement $element, $urlAttribute, $siteDomain, $siteBaseUrl, $appendUrl=null)
    {
        $url = $element->getAttribute($urlAttribute);

        if (!preg_match("/^data:/", $url)) {
            if ($this->isRelativeUrl($url)) {
                $url = $siteBaseUrl . '/' . ltrim($url, '/');
            }

            if ($this->isInternalLink($siteDomain, $url)) {
                if ($appendUrl) {
                    $url = $appendUrl . urlencode($url);
                }
                $element->setAttribute($urlAttribute, $url);
            }
        }
    }

    private function isInternalLink($siteDomain, $url)
    {
        if (strpos($url, $siteDomain) !== false) {
            return true;
        } else {
            return false;
        }
    }

    private function isRelativeUrl($url)
    {
        if (preg_match("/^http/", $url)) {
            return false;
        } else {
            return true;
        }
    }

    private function getBaseUrl($url)
    {
        $parsedUrl = parse_url($url);
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $user = isset($parsedUrl['user']) ? $parsedUrl['user'] : '';
        $pass = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        return "$scheme$user$pass$host$port";
    }


}