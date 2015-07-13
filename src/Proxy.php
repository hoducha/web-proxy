<?php
namespace Dootech\WebProxy;

use Dootech\WebProxy\Event\ProxyEvent;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class Proxy
{
    private $client;

    private $dispatcher;
    private $request;
    private $response;

    private $cookieJar;
    private $history;
    private $browserCookies;
    private $server = array();

    private $appendUrl;
    private $targetUrl;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
    }

    public function forward(Request $request, $targetUrl)
    {
        $this->request = $request;
        $this->targetUrl = $targetUrl;
        $this->browserCookies = ($this->browserCookies) ? array_merge($this->browserCookies, $_COOKIE) : $_COOKIE;

        $this->dispatcher->dispatch('request.before_send', new ProxyEvent(array('proxy' => $this)));

//         // Set callback options to cURL request
//         $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_HEADERFUNCTION, array($this, 'fn_CURLOPT_HEADERFUNCTION'));
//         $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_WRITEFUNCTION, array($this, 'fn_CURLOPT_WRITEFUNCTION'));

        $this->getClient()->request($this->request->getMethod(), $this->targetUrl, $_REQUEST, $_FILES, $this->server);
        $clientResponse = $this->getClient()->getResponse();
        $this->response = new Response();
        $this->response->setContent($clientResponse->getContent());
        $this->response->setStatusCode($clientResponse->getStatus());
        $this->response->headers = new ResponseHeaderBag($clientResponse->getHeaders());

        $this->dispatcher->dispatch('request.complete', new ProxyEvent(array('proxy' => $this)));

        return $this->response;
    }

    /**
     * Callback function handling header lines received in the response
     *
     * @param resource $ch The cURL resource
     * @param string $str A string with the header data to be written
     * @return number       The number of bytes written
     */
    private function fn_CURLOPT_HEADERFUNCTION($ch, $str)
    {
        return strlen($str);
    }

    /**
     * Callback function handling data received from the response
     *
     * @param resource $ch The cURL resource
     * @param string $str A string with the data to be written
     * @return number       The number of bytes written
     */
    private function fn_CURLOPT_WRITEFUNCTION($ch, $str)
    {
        strlen($str);
    }

    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    public function getClient()
    {
        if (!$this->client) {
            $this->client = new Client($this->server, $this->history, $this->cookieJar);
            $this->client->setClient(new GuzzleClient(array(
                'cookies' => true, 'verify' => false
            )));
        }

        return $this->client;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param mixed $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function getTargetUrl()
    {
        return $this->targetUrl;
    }

    /**
     * @param mixed $targetUrl
     */
    public function setTargetUrl($targetUrl)
    {
        $this->targetUrl = $targetUrl;
    }

    /**
     * @return mixed
     */
    public function getAppendUrl()
    {
        return $this->appendUrl;
    }

    /**
     * @param mixed $appendUrl
     */
    public function setAppendUrl($appendUrl)
    {
        $this->appendUrl = $appendUrl;
    }

    /**
     * @return EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @return mixed
     */
    public function getCookieJar()
    {
        return $this->cookieJar;
    }

    /**
     * @param mixed $cookieJar
     */
    public function setCookieJar($cookieJar)
    {
        $this->cookieJar = $cookieJar;
    }

    /**
     * @return mixed
     */
    public function getBrowserCookies()
    {
        return $this->browserCookies;
    }

    /**
     * @param mixed $browserCookies
     */
    public function setBrowserCookies($browserCookies)
    {
        $this->browserCookies = $browserCookies;
    }

}