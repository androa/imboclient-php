<?php
/**
 * ImboClient
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package ImboClient
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imboclient-php
 */

namespace ImboClient;

/**
 * @package ImboClient
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imboclient-php
 */
class ClientTest extends \PHPUnit_Framework_TestCase {
    /**
     * Client instance
     *
     * @var ImboClient\Client
     */
    private $client;

    /**
     * Public key
     *
     * @var string
     */
    private $publicKey;

    /**
     * Private key
     *
     * @var string
     */
    private $privateKey;

    /**
     * The server url passed to the constructor
     *
     * @var string
     */
    private $serverUrl = 'http://host';

    /**
     * Image identifier used for tests
     *
     * @var string
     */
    private $imageIdentifier;

    /**
     * Pattern used in the mock matchers when url is signed
     *
     * @var string
     */
    private $signedUrlPattern = '|^http://host/[a-f0-9]{32}/[a-f0-9]{32}\.png(/meta)?\?signature=(.*?)&timestamp=\d\d\d\d-\d\d-\d\dT\d\d%3A\d\dZ$|';

    /**
     * Pattern used in the mock matchers with regular urls
     *
     * @var string
     */
    private $urlPattern = '|^http://host/[a-f0-9]{32}/[a-f0-9]{32}\.png(/meta)?$|';

    /**
     * Set up method
     */
    public function setUp() {
        $this->publicKey = md5(microtime());
        $this->privateKey = md5(microtime());
        $this->imageIdentifier = md5(microtime()) . '.png';
        $this->driver = $this->getMock('ImboClient\Client\Driver\DriverInterface');

        $this->client = new Client($this->serverUrl, $this->publicKey, $this->privateKey, $this->driver);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->client = null;
    }

    /**
     * @expectedException ImboClient\Exception
     * @expectedExceptionMessage File does not exist: foobar
     */
    public function testAddImageWhenImageDoesNotExist() {
        $this->client->addImage('foobar');
    }

    public function testAddImageWithMetadata() {
        $imagePath = __DIR__ . '/_files/image.png';
        $metadata = array(
            'foo' => 'bar',
            'bar' => 'foo',
            'filename' => 'image.png',
        );

        $response = $this->getMock('ImboClient\Client\Response');
        $response->expects($this->once())->method('isSuccess')->will($this->returnValue(true));

        $this->driver->expects($this->once())->method('put')->with($this->matchesRegularExpression($this->signedUrlPattern), $imagePath)->will($this->returnValue($response));
        $this->driver->expects($this->once())->method('post')->with($this->matchesRegularExpression($this->signedUrlPattern), $metadata)->will($this->returnValue($response));

        $result = $this->client->addImage($imagePath, $metadata);

        $this->assertSame($result, $response);
    }

    public function testAddImageWithNoMetadata() {
        $imagePath = __DIR__ . '/_files/image.png';

        $response = $this->getMock('ImboClient\Client\Response');
        $response->expects($this->once())->method('isSuccess')->will($this->returnValue(true));

        $this->driver->expects($this->once())->method('put')->with($this->matchesRegularExpression($this->signedUrlPattern), $imagePath)->will($this->returnValue($response));
        $this->driver->expects($this->once())->method('post')->with($this->matchesRegularExpression($this->signedUrlPattern), array('filename' => 'image.png'))->will($this->returnValue($response));

        $result = $this->client->addImage($imagePath);

        $this->assertSame($result, $response);
    }

    public function testDeleteImage() {
        $response = $this->getMock('ImboClient\Client\Response');
        $this->driver->expects($this->once())->method('delete')->with($this->matchesRegularExpression($this->signedUrlPattern))->will($this->returnValue($response));

        $result = $this->client->deleteImage($this->imageIdentifier);

        $this->assertSame($result, $response);
    }

    public function testEditMetadata() {
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );

        $response = $this->getMock('ImboClient\Client\Response');
        $this->driver->expects($this->once())->method('post')->with($this->matchesRegularExpression($this->signedUrlPattern), $data)->will($this->returnValue($response));
        $result = $this->client->editMetadata($this->imageIdentifier, $data);

        $this->assertSame($result, $response);
    }

    public function testDeleteMetadata() {
        $response = $this->getMock('ImboClient\Client\Response');
        $this->driver->expects($this->once())->method('delete')->with($this->matchesRegularExpression($this->signedUrlPattern))->will($this->returnValue($response));
        $result = $this->client->deleteMetadata($this->imageIdentifier);

        $this->assertSame($result, $response);
    }

    public function testGetMetadata() {
        $response = $this->getMock('ImboClient\Client\Response');
        $this->driver->expects($this->once())->method('get')->with($this->matchesRegularExpression($this->urlPattern))->will($this->returnValue($response));
        $result = $this->client->getMetadata($this->imageIdentifier);

        $this->assertSame($result, $response);
    }
}