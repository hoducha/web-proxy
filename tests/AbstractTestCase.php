<?php
namespace Dootech\WebProxy\Test;

use Dootech\WebProxy\Plugin\LinkModifierPlugin;
use Dootech\WebProxy\Proxy;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Middleware;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;

class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    protected $history;
    protected $mock;

    protected function getGuzzle(array $responses = [])
    {
        if (empty($responses)) {
            $responses = [new GuzzleResponse(200, [], '<html><body><p>Hi</p></body></html>')];
        }
        $this->mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($this->mock);
        $this->history = [];
        $handlerStack->push(Middleware::history($this->history));
        $guzzle = new GuzzleClient(array('redirect.disable' => true, 'base_uri' => '', 'handler' => $handlerStack));

        return $guzzle;
    }

}