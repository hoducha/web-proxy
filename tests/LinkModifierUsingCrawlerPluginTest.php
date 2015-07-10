<?php

namespace Dootech\WebProxy\Test;

use Dootech\WebProxy\Plugin\LinkModifierPlugin;
use Dootech\WebProxy\Plugin\LinkModifierUsingCrawlerPlugin;
use Dootech\WebProxy\Proxy;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Symfony\Component\HttpFoundation\Request;

class LinkModifierUsingCrawlerPluginTest extends AbstractTestCase
{

    public function testLinkModifierFunction()
    {
        $content = <<<HTML
<body>
    <div id="action">
        <a href="http://example.com/link/">Test</a>
        <a href="http://www.example.com/link/">Test</a>
        <a href="http://www.example.com/link.html">Test</a>
        <a href="http://www.example.com/link.jpg">Test</a>
        <a href="/link">Test</a>
        <a href="/link.php">Test</a>
        <a href="http://www.example-external.com/link.jpg">Test</a>
        <a href="http://example.com/link/">"Example quote 'Test' "</a>
    </div>
</body>
HTML;

        $expectedResult = <<<HTML
<body>
    <div id="action">
        <a href="http://proxy.local?u=http%3A%2F%2Fexample.com%2Flink%2F">Test</a>
        <a href="http://proxy.local?u=http%3A%2F%2Fwww.example.com%2Flink%2F">Test</a>
        <a href="http://proxy.local?u=http%3A%2F%2Fwww.example.com%2Flink.html">Test</a>
        <a href="http://proxy.local?u=http%3A%2F%2Fwww.example.com%2Flink.jpg">Test</a>
        <a href="http://proxy.local?u=http%3A%2F%2Fexample.com%2Flink">Test</a>
        <a href="http://proxy.local?u=http%3A%2F%2Fexample.com%2Flink.php">Test</a>
        <a href="http://www.example-external.com/link.jpg">Test</a>
        <a href="http://proxy.local?u=http%3A%2F%2Fexample.com%2Flink%2F">"Example quote 'Test' "</a>
    </div>
</body>
HTML;

        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, array('Content-Type'=>'text/html'), $content),
        ]);

        $proxy = new Proxy();
        $proxy->getClient()->setClient($guzzle);
        $proxy->setAppendUrl('http://proxy.local?u=');
        $proxy->getDispatcher()->addSubscriber(new LinkModifierUsingCrawlerPlugin());

        $request = Request::create('/', 'GET');
        $response = $proxy->forward($request, 'http://example.com/');
        $this->assertEquals($expectedResult, $response->getContent());
    }
}