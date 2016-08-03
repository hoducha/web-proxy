## WebProxy Library

WebProxy is an advanced web proxy built with Symfony components, Goutte and Guzzle.

## Usage
```php
$proxy = new Proxy();
$proxy->getDispatcher()->addSubscriber(new LinkModifierPlugin());
$proxy->getDispatcher()->addSubscriber(new CookiePlugin());

$response = $proxy->forward($request, $targetUrl);
```

## What's inside?

WebProxy is configured with the following defaults:

  * **CookiePlugin** - Used to modify and persist cookies to browsers

  * **LinkModifier** - Used to modify all the links in the HTML, CSS, JS documents.

  * **BrowserKit** - BrowserKit simulates the behavior of a web browser.

  * **DomCrawler** - DomCrawler eases DOM navigation for HTML and XML documents.

All libraries and bundles included in the WebProxy are released under the MIT license.

Enjoy!
