<?php
namespace Dootech\WebProxy\Test;

use Dootech\WebProxy\Plugin\RedirectPlugin;
use Dootech\WebProxy\Proxy;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPluginTest extends AbstractTestCase
{
    /**
     * @runInSeparateProcess
     * This is the work around to the redirect problem because PHPUnit will print a header to the screen
     * so redirect by header('Location: http://example.com') will throw the error "headers already sent"
     */
    public function testRedirectPlugin()
    {
        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, array('Content-Type' => 'image/png')),
        ]);

        $proxy = new Proxy();
        $proxy->getClient()->setClient($guzzle);
        $proxy->getDispatcher()->addSubscriber(new RedirectPlugin(array('image')));

        $request = Request::create('/', 'GET');
        $response = $proxy->forward($request, 'http://www.example.com/');
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    }

}