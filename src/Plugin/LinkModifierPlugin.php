<?php
namespace Dootech\WebProxy\Plugin;

use Dootech\WebProxy\Event\ProxyEvent;
use Dootech\WebProxy\Parser\ContentParser;

class LinkModifierPlugin extends AbstractPlugin
{
    const LINK_PATTERN = "/(<a[^>]+href=(\"|'))(.*?)((\"|')[^>]*>.*?<\/a>)/";

    public function onRequestComplete(ProxyEvent $event)
    {
        $proxy = isset($event['proxy']) ? $event['proxy'] : null;
        $response = !empty($proxy) ? $proxy->getResponse() : null;
        if ($response) {
            $content = $response->getContent();
            $contentType = $response->headers->get('Content-Type');
            $contentParser = new ContentParser($response->getContent(), $proxy->getTargetUrl(), $proxy->getAppendUrl());

            if ($contentType == 'text/html') {
                $content = $contentParser->parseHTML();
            } else if ($contentType == 'text/css') {
                $content = $contentParser->parseCss();
            } else if ($contentType == 'text/javascript' || $contentType == 'application/javascript' || $contentType == 'application/x-javascript') {
                $content = $contentParser->parseJS();
            } else if (substr($contentType,0,6) == 'image/') {
                // TODO: Create image filter
            } else if (substr($contentType,0,6) == 'video/' || substr($contentType,0,6) != 'audio/') {
                // TODO: Create streaming filter for video/audio files
            } else {
                // Do not recognize the content type.
            }

            $response->setContent($content);
            $response->headers->set('Content-Length', strlen($content));
        }
    }

    public function modifyLinks($pageUrl, $content, $appendUrl)
    {
        $siteBaseUrl = $this->getBaseUrl($pageUrl);
        $siteDomain = parse_url($pageUrl, PHP_URL_HOST);

        preg_match_all(self::LINK_PATTERN, $content, $matches);
        $count = count($matches[0]);

        for ($i = 0; $i < $count; $i++) {
            $url = $matches[3][$i];
            if ($this->isRelativeUrl($url)) {
                $url = $siteBaseUrl . $url;
            }

            if ($this->isInternalLink($siteDomain, $url)) {
                $url = $appendUrl . urlencode($url);
                $content = str_replace($matches[0][$i], $matches[1][$i] . $url . $matches[4][$i], $content);
            }
        }

        return $content;
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