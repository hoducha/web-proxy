<?php

require 'vendor/autoload.php';

use Dootech\WebProxy\Proxy;
use Symfony\Component\HttpFoundation\Request;


$request = Request::createFromGlobals();
$targetUrl = $request->get('_proxyTargetUrl');

if ($targetUrl) {
    // Get base url
    if (strtolower(substr($_SERVER['HTTP_HOST'], 0, 4)) != 'http' && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == '')) {
        $host = 'http://' . $_SERVER['HTTP_HOST'];
    } else {
        $host = 'https://' . $_SERVER['HTTP_HOST'];
    }
    $appendUrl = $host . $_SERVER['SCRIPT_NAME'] . '?_proxyTargetUrl=';

    $proxy = new Proxy();
    $proxy->setAppendUrl($appendUrl);
    $proxy->getDispatcher()->addSubscriber(new \Dootech\WebProxy\Plugin\LinkModifierPlugin());
    $proxy->getDispatcher()->addSubscriber(new \Dootech\WebProxy\Plugin\CookiePlugin());
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

