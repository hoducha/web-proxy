<?php
namespace Dootech\WebProxy\Test;

use Dootech\WebProxy\Proxy;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Symfony\Component\HttpFoundation\Request;

class WebProxyTest extends AbstractTestCase
{
    public function testPlainTextResponse()
    {
        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, array(), 'Example content'),
        ]);

        $proxy = new Proxy();
        $proxy->getClient()->setClient($guzzle);
        $request = Request::create('/', 'GET');
        $response = $proxy->forward($request, 'http://www.example.com/');
        $this->assertEquals('Example content', $response->getContent());
    }

}