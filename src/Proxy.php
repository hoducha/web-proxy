<?php
namespace Dootech\WebProxy;

use Dootech\WebProxy\Event\ProxyEvent;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Cookie\SessionCookieJar;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class Proxy
{
    private $client;

    private $dispatcher;
    private $request;
    private $response;
    private $redirect = false;

    private $appendUrl;
    private $targetUrl;

    private $cookieDir;

    public function __construct($cookieDir = null)
    {
        $this->dispatcher = new EventDispatcher();

        if ($cookieDir) {
            $this->setCookieDir($cookieDir);
        }
    }

    public function forward(Request $request, $targetUrl)
    {
        $this->request = $request;
        $this->targetUrl = $targetUrl;

        $this->dispatcher->dispatch('request.before_send', new ProxyEvent(array('proxy' => $this)));

        if ($this->redirect) {
            $this->redirect($this->targetUrl, true);

        } else {
            // Set callback options to cURL request
            // $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_HEADERFUNCTION, array($this, 'fn_CURLOPT_HEADERFUNCTION'));
            // $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_WRITEFUNCTION, array($this, 'fn_CURLOPT_WRITEFUNCTION'));

            $this->getClient()->request($this->request->getMethod(), $this->targetUrl, $_REQUEST, $_FILES);
            $clientResponse = $this->getClient()->getResponse();

            $this->response = new HttpResponse((string) $clientResponse->getContent(), $clientResponse->getStatus(), $clientResponse->getHeaders());
            $this->dispatcher->dispatch('request.complete', new ProxyEvent(array('proxy' => $this)));

            return $this->response;
        }

    }

    private function redirect($url, $permanent = false)
    {
        header('Location: ' . $url, true, $permanent ? 301 : 302);
        exit();
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
            $targetDomain = parse_url($this->getTargetUrl(), PHP_URL_HOST);
            if ($targetDomain) {
                if ($this->cookieDir) {
                    $guzzleCookieJar = new FileCookieJar($this->cookieDir . DIRECTORY_SEPARATOR . $targetDomain, TRUE);
                } else {
                    $guzzleCookieJar = new SessionCookieJar($targetDomain, TRUE);
                }
            }

            $this->client = new Client();
            $this->client->setServerParameter('HTTP_USER_AGENT', $_SERVER['HTTP_USER_AGENT']);
            if (!empty($guzzleCookieJar)) {
                $this->client->setGuzzleCookieJar($guzzleCookieJar);
            }

            $this->client->setClient(new GuzzleClient(array('allow_redirects' => false, 'cookies' => true, 'verify' => false)));
        }

        return $this->client;
    }

    public function setCookieDir($cookieDir)
    {
        $cookieDir = rtrim($cookieDir, DIRECTORY_SEPARATOR);
        if (!file_exists($cookieDir)) {
            if (!mkdir($cookieDir, 0777, true)) {
                throw new \Exception('Failed to create cookies directory');
            }
        }
        $this->cookieDir = $cookieDir;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function getTargetUrl()
    {
        return $this->targetUrl;
    }

    public function setTargetUrl($targetUrl)
    {
        $this->targetUrl = $targetUrl;
    }

    public function getAppendUrl()
    {
        return $this->appendUrl;
    }

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
     * @return boolean
     */
    public function isRedirect()
    {
        return $this->redirect;
    }

    /**
     * @param boolean $redirect
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }
}