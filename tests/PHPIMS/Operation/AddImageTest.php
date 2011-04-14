<?php
/**
 * PHPIMS
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
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Operation;

use \Mockery as m;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class AddImageTest extends OperationTests {
    protected function getNewOperation() {
        return new AddImage();
    }

    public function getExpectedOperationName() {
        return 'addImage';
    }

    public function getExpectedRequestPath() {
        return $this->hash;
    }

    public function testSuccessfullExec() {
        $_FILES['file'] = array(
            'tmp_name' => '/tmp/foobar',
        );

        $hash = md5(microtime()) . '.png';
        $metadata = array(
            'foo' => 'bar',
            'bar' => array(
                'foo' => 'bar',
            ),
        );
        $image = m::mock('PHPIMS\\Image');
        $image->shouldReceive('getMetadata')->once()->andReturn($metadata);
        $response = m::mock('PHPIMS\\Server\\Response');
        $response->shouldReceive('setCode')->once()->with(201)->andReturn($response);
        $response->shouldReceive('setBody')->once()->with(array('hash' => $hash))->andReturn($response);

        $database = m::mock('PHPIMS\\Database\\Driver');
        $database->shouldReceive('insertImage')->once()->with($hash, $image);
        $database->shouldReceive('updateMetadata')->once()->with($hash, $metadata);

        $storage = m::mock('PHPIMS\\Storage\\Driver');
        $storage->shouldReceive('store')->once()->with($hash, $_FILES['file']['tmp_name']);

        $this->operation->setStorage($storage)
                        ->setDatabase($database)
                        ->setResponse($response)
                        ->setHash($hash)
                        ->setImage($image)
                        ->exec();
    }
}