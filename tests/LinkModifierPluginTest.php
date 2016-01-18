<?php

namespace Dootech\WebProxy\Test;

use Dootech\WebProxy\Plugin\LinkModifierPlugin;
use Dootech\WebProxy\Plugin\LinkModifierUsingCrawlerPlugin;
use Dootech\WebProxy\Proxy;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Symfony\Component\HttpFoundation\Request;

class LinkModifierPluginTest extends AbstractTestCase
{
    public function testHyperlinks()
    {
        $content = <<<HTML
<!DOCTYPE html>
<html lang="en">
<body>
    <div id="action">
        <a href='http://example.com/link/'>Test</a>
        <a href="http://example.com/link/">Test</a>
        <a href="http://www.example.com/link/">Test</a>
        <a href="http://www.example.com/link.html">Test</a>
        <a href="http://www.example.com/link.jpg">Test</a>
        <a href="/link">Test</a>
        <a href="/link.php">Test</a>
        <a id="linkId" class="linkClass" href="/link.php"/> Test </a>
        <a href="http://www.example-external.com/link.jpg">Test</a>
        <a href="http://example.com/link/">"Example quote 'Test' "</a>
    </div>
</body>
</html>
HTML;

        $expectedResult = <<<HTML
<!DOCTYPE html>
<html lang="en">
<body>
    <div id="action">
        <a href='http://proxy.local?u=http://example.com/link/' >Test</a>
        <a href="http://proxy.local?u=http://example.com/link/" >Test</a>
        <a href="http://proxy.local?u=http://www.example.com/link/" >Test</a>
        <a href="http://proxy.local?u=http://www.example.com/link.html" >Test</a>
        <a href="http://proxy.local?u=http://www.example.com/link.jpg" >Test</a>
        <a href="http://proxy.local?u=http://example.com/link" >Test</a>
        <a href="http://proxy.local?u=http://example.com/link.php" >Test</a>
        <a id="linkId" class="linkClass" href="http://proxy.local?u=http://example.com/link.php" /> Test </a>
        <a href="http://proxy.local?u=http://www.example-external.com/link.jpg" >Test</a>
        <a href="http://proxy.local?u=http://example.com/link/" >"Example quote 'Test' "</a>
    </div>
</body>
</html>
HTML;

        $this->simpleTest($content, $expectedResult);
    }

    public function testCssModifierInHtml()
    {
        $content = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="style.css"/>
    <style>
        .test {
            background: #00ff00 url("smiley.gif") no-repeat fixed center;
        }
        @font-face {
            font-family: myFirstFont;
            src: url(sansation_light.woff);
        }
    </style>
</head>
<body>
</body>
</html>
HTML;

        $expectedResult = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="http://proxy.local?u=http://example.com/style.css" />
    <style>
        .test {
            background: #00ff00 url("http://proxy.local?u=http://example.com/smiley.gif") no-repeat fixed center;
        }
        @font-face {
            font-family: myFirstFont;
            src: url(http://proxy.local?u=http://example.com/sansation_light.woff);
        }
    </style>
</head>
<body>
</body>
</html>
HTML;

        $this->simpleTest($content, $expectedResult);
    }

    public function testCssModifier()
    {
        $content = <<<CSS
.test {
    background: #00ff00 url("smiley.gif") no-repeat fixed center;
}
@font-face {
    font-family: myFirstFont;
    src: url(sansation_light.woff);
}
CSS;

        $expectedResult = <<<CSS
.test {
    background: #00ff00 url("http://proxy.local?u=http://example.com/smiley.gif") no-repeat fixed center;
}
@font-face {
    font-family: myFirstFont;
    src: url(http://proxy.local?u=http://example.com/sansation_light.woff);
}
CSS;

        $this->simpleTest($content, $expectedResult, $contentType='text/css');
    }

    private function simpleTest($content, $expectedResult, $contentType='text/html', $requestUrl='http://example.com', $urlPrefix='http://proxy.local?u=')
    {
        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, array('Content-Type'=>$contentType), $content),
        ]);

        $proxy = new Proxy();
        $proxy->getClient()->setClient($guzzle);
        $proxy->setAppendUrl($urlPrefix);
        $proxy->getDispatcher()->addSubscriber(new LinkModifierPlugin());

        $request = Request::create('/', 'GET');
        $response = $proxy->forward($request, $requestUrl);
        $this->assertEquals($expectedResult, $response->getContent());
    }
}