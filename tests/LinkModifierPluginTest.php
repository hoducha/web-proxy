<?php
/**
 * Created by PhpStorm.
 * User: Ha
 * Date: 7/3/2015
 * Time: 9:05 PM
 */

namespace Dootech\WebProxy\Test;


use Dootech\WebProxy\Plugin\LinkModifierPlugin;

class LinkModifierPluginTest extends \PHPUnit_Framework_TestCase
{

    public function testLinkModifierFunction()
    {
        $content = <<<HTML
<!DOCTYPE html>
<html lang="en">
<body>
    <div id="action">
        <a href='http://example.com/link/'>Test</a>
        <a href="http://example.com/link/">Test</a>
        <a href="http://www.example.com/link/">Test</a>
        <a href="http://www.example.com/link.html">Test</a>
        <a href="http://www.example.com/link.jpg">Test</a>
        <a href="/link">Test</a>
        <a href="/link.php">Test</a>
        <a id="linkId" class="linkClass" href="/link.php" /> Test </a>
        <a href="http://www.example-external.com/link.jpg">Test</a>
    </div>
</body>
</html>
HTML;

        $expectedResult = <<<HTML
<!DOCTYPE html>
<html lang="en">
<body>
    <div id="action">
        <a href='http://proxy.local?u=http%3A%2F%2Fexample.com%2Flink%2F'>Test</a>
        <a href="http://proxy.local?u=http%3A%2F%2Fexample.com%2Flink%2F">Test</a>
        <a href="http://proxy.local?u=http%3A%2F%2Fwww.example.com%2Flink%2F">Test</a>
        <a href="http://proxy.local?u=http%3A%2F%2Fwww.example.com%2Flink.html">Test</a>
        <a href="http://proxy.local?u=http%3A%2F%2Fwww.example.com%2Flink.jpg">Test</a>
        <a href="http://proxy.local?u=http%3A%2F%2Fexample.com%2Flink">Test</a>
        <a href="http://proxy.local?u=http%3A%2F%2Fexample.com%2Flink.php">Test</a>
        <a id="linkId" class="linkClass" href="http://proxy.local?u=http%3A%2F%2Fexample.com%2Flink.php" /> Test </a>
        <a href="http://www.example-external.com/link.jpg">Test</a>
    </div>
</body>
</html>
HTML;

        $linkModifier = new LinkModifierPlugin();
        $pageUrl = 'http://example.com/test.html';
        $appendUrl = 'http://proxy.local?u=';
        $result = $linkModifier->modifyLinks($pageUrl, $content, $appendUrl);
        $this->assertEquals($expectedResult, $result);
    }
}