<?php
namespace Dootech\WebProxy;

use Dootech\WebProxy\Event\ProxyEvent;
use Goutte\Client;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class Proxy {
    private $dispatcher;
    private $request;
    private $response;

    private $cookieJar;
    private $history;
    private $server;

    public function __construct() {
        $this->dispatcher = new EventDispatcher();
        $this->server = array();
    }

    public function test() {
        return 'test';
    }

    public function forward(Request $request, $targetUrl){
        $this->request = $request;

//         $this->dispatcher->dispatch('request.before_send', new ProxyEvent(array('request'=>$this->request, 'cookieJar' => $this->cookieJar)));

        $client = new Client($this->server, $this->history, $this->cookieJar);

        // Turn off SSL verification
//         $client->getClient()->setDefaultOption('verify', FALSE);

//         // Set callback options to cURL request
//         $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_HEADERFUNCTION, array($this, 'fn_CURLOPT_HEADERFUNCTION'));
//         $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_WRITEFUNCTION, array($this, 'fn_CURLOPT_WRITEFUNCTION'));

        $crawler = $client->request($request->getMethod(), $targetUrl . '/' . $request->getRequestUri(), $_REQUEST, $_FILES, $server=array());

        $clientResponse = $client->getResponse();


        // Create new Http Response from BrowserKit Response
        $response = new Response();
        $response->setContent($clientResponse->getContent());
        $response->setStatusCode($clientResponse->getStatus());
        $response->headers = new ResponseHeaderBag($clientResponse->getHeaders());

//         $this->dispatcher->dispatch('request.complete', new ProxyEvent(array('response'=>$response)));

        return $response;
    }

    public function getEventDispatcher() {
        return $this->dispatcher;
    }

    /**
     * Callback function handling header lines received in the response
     *
     * @param resource $ch  The cURL resource
     * @param string $str   A string with the header data to be written
     * @return number       The number of bytes written
     */
    private function fn_CURLOPT_HEADERFUNCTION($ch, $str) {
        return strlen($str);
    }

    /**
     * Callback function handling data received from the response
     *
     * @param resource $ch  The cURL resource
     * @param string $str   A string with the data to be written
     * @return number       The number of bytes written
     */
    private function fn_CURLOPT_WRITEFUNCTION($ch, $str) {
        strlen($str);
    }

}