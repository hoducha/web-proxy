<?php

require 'vendor/autoload.php';

use Dootech\WebProxy\Proxy;
use Symfony\Component\HttpFoundation\Request;


$request = Request::createFromGlobals();
$targetUrl = $request->get('_proxyTargetUrl');

//if(!empty($_POST)) {
//    var_dump($request); die;
//}

if ($targetUrl) {
    $proxy = new Proxy();
    $proxy->setAppendUrl($request->getBasePath() . '?_proxyTargetUrl=');
    $proxy->getDispatcher()->addSubscriber(new \Dootech\WebProxy\Plugin\LinkModifierPlugin());

    $response = $proxy->forward($request, $targetUrl);
    $response->send();

} else {
    $content = <<<HTML
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
    echo $content;
}



