<?php

require 'vendor/autoload.php';

use Dootech\WebProxy\Proxy;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();
$targetUrl = $request->get('_proxyTargetUrl');
$targetUrl = urldecode($targetUrl);

if ($targetUrl) {
    $appendUrl = $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo() . '?_proxyTargetUrl=';

    $proxy = new Proxy();
    $proxy->setAppendUrl($appendUrl);
    $proxy->setCookieDir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache');
    $proxy->getDispatcher()->addSubscriber(new \Dootech\WebProxy\Plugin\LinkModifierPlugin());
    $proxy->getDispatcher()->addSubscriber(new \Dootech\WebProxy\Plugin\RedirectPlugin(array('image', 'video', 'audio')));

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

