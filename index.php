<?php

require 'vendor/autoload.php';

use Dootech\WebProxy\Proxy;
use Symfony\Component\HttpFoundation\Request;



$request = Request::createFromGlobals();
//$request = Request::create('/', 'GET', array('_proxyTargetUrl' => 'http://vnexpress.net'));
$targetUrl = $request->get('_proxyTargetUrl');

if ($targetUrl) {
    $proxy = new Proxy();
    $proxy->setAppendUrl($request->getBasePath() . '?_proxyTargetUrl=');
//    $proxy->getDispatcher()->addSubscriber(new \Dootech\WebProxy\Plugin\LinkModifierPlugin());
    $proxy->getDispatcher()->addSubscriber(new \Dootech\WebProxy\Plugin\LinkModifierUsingCrawlerPlugin());
    $proxy->getDispatcher()->addSubscriber(new \Dootech\WebProxy\Plugin\CookiePlugin());

    $response = $proxy->forward($request, $targetUrl);
    $response->send();

} else {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<body>
    <div id="action">
        <form method="GET" action="/WebProxy">
            <input type="text" name="_proxyTargetUrl" value="" />
            <input type="submit" name="" value="Submit">
        </form>
    </div>
</body>
</html>
HTML;
}

