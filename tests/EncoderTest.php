<?php
/**
 * Created by PhpStorm.
 * User: Ha Ho
 * Date: 1/18/2016
 * Time: 2:48 PM
 */

namespace Dootech\WebProxy\Test;

use Dootech\WebProxy\Parser\Encoder;

class EncoderTest extends AbstractTestCase
{
    public function testEncoder()
    {
        $encoder = new Encoder();
        $encoder->serverKey = 'joWVexlW4fHeH/2GGNLefy8bV7JFaaTTF92AWp1k0jDsMqC8tqeAvdLo/gg';

        $exampleUrl = 'http://example.com/link?foo=bar#footer';
        $encodedUrl = $encoder->encode($exampleUrl);
        $this->assertEquals($exampleUrl, $encoder->decode($encodedUrl));
    }
}