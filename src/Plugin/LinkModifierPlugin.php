<?php
namespace Dootech\WebProxy\Plugin;

use Dootech\WebProxy\Event\ProxyEvent;

class LinkModifierPlugin extends AbstractPlugin
{
    const LINK_PATTERN = "/(<a[^>]+href=\"|')(.*?)(\"|'[^>]*>.*?<\/a>)/";

    public function onResponse(ProxyEvent $event)
    {
        $crawler = isset($event['crawler']) ? $event['crawler'] : null;
        if ($crawler) {
            foreach ($crawler->filter('a') as $linkNode) {
                // $linkNode->getAttribute('href');
            }
        }
    }

    public function onCompleted(ProxyEvent $event)
    {
        $proxy = isset($event['proxy']) ? $event['proxy'] : null;
        $response = $proxy->getResponse();
        if ($response) {
            $content = $response->getContent();
            $contentType = $response->headers->get('Content-Type');
            if (strpos($contentType, 'text/html') !== false || strpos($contentType, 'text/javascript') !== false) {
                $content = $this->modifyLinks($proxy->getTargetUrl(), $content, $proxy->getAppendUrl());
                $response->setContent($content);
                $response->headers->set('Content-Length', strlen($content));
            }
        }
    }

    public function modifyLinks($pageUrl, $content, $appendUrl)
    {
        $siteBaseUrl = $this->getBaseUrl($pageUrl);
        $siteDomain = parse_url($pageUrl, PHP_URL_HOST);

        preg_match_all(self::LINK_PATTERN, $content, $matches);
        $count = count($matches[0]);

        for ($i = 0; $i < $count; $i++) {
            $url = $matches[2][$i];
            if ($this->isRelativeUrl($url)) {
                $url = $siteBaseUrl . $url;
            }

            if ($this->isInternalLink($siteDomain, $url)) {
                $url = $appendUrl . urlencode($url);
                $content = str_replace($matches[0][$i], $matches[1][$i] . $url . $matches[3][$i], $content);
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