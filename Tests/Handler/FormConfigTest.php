<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Handler;

use Fxp\Component\Resource\Handler\FormConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests case for Form Config Handler.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class FormConfigTest extends TestCase
{
    public function testWithStringType()
    {
        $type = FormType::class;
        $config = new FormConfig($type);
        $this->assertSame('json', $config->getConverter());
        $this->assertSame(Request::METHOD_POST, $config->getMethod());
        $this->assertEquals(['method' => Request::METHOD_POST], $config->getOptions());
        $this->assertSame($type, $config->getType());
        $this->assertTrue($config->getSubmitClearMissing());
    }

    public function testWithStringTypeAndPatchMethod()
    {
        $type = FormType::class;
        $config = new FormConfig($type, [], Request::METHOD_PATCH);
        $this->assertSame('json', $config->getConverter());
        $this->assertSame(Request::METHOD_PATCH, $config->getMethod());
        $this->assertEquals(['method' => Request::METHOD_PATCH], $config->getOptions());
        $this->assertSame($type, $config->getType());
        $this->assertFalse($config->getSubmitClearMissing());
    }

    public function testSetType()
    {
        $config = new FormConfig(FormType::class);

        $this->assertSame(FormType::class, $config->getType());

        $config->setType(FormType::class);
        $this->assertSame(FormType::class, $config->getType());
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessage The form type of domain form config must be an string of class name of form type
     */
    public function testSetInvalidType()
    {
        $config = new FormConfig('form_type_name');
        $config->setType(42);
    }

    public function testSetOptions()
    {
        $config = new FormConfig(FormType::class);

        $this->assertSame(Request::METHOD_POST, $config->getMethod());
        $this->assertEquals([
            'method' => Request::METHOD_POST,
        ], $config->getOptions());

        $config->setOptions([
            'method' => Request::METHOD_PATCH,
            'required' => true,
        ]);

        $this->assertEquals([
            'method' => Request::METHOD_PATCH,
            'required' => true,
        ], $config->getOptions());
        $this->assertSame(Request::METHOD_PATCH, $config->getMethod());
    }

    public function testSetMethod()
    {
        $config = new FormConfig(FormType::class);

        $this->assertSame(Request::METHOD_POST, $config->getMethod());
        $this->assertEquals([
            'method' => Request::METHOD_POST,
        ], $config->getOptions());
        $this->assertSame(Request::METHOD_POST, $config->getMethod());

        $config->setMethod(Request::METHOD_PATCH);

        $this->assertSame(Request::METHOD_PATCH, $config->getMethod());
        $this->assertEquals([
            'method' => Request::METHOD_PATCH,
        ], $config->getOptions());
    }

    public function getRequestMethod()
    {
        return [
            [null, Request::METHOD_HEAD,    true],
            [null, Request::METHOD_GET,     true],
            [null, Request::METHOD_POST,    true],
            [null, Request::METHOD_PUT,     true],
            [null, Request::METHOD_PATCH,   false],
            [null, Request::METHOD_DELETE,  true],
            [null, Request::METHOD_PURGE,   true],
            [null, Request::METHOD_OPTIONS, true],
            [null, Request::METHOD_TRACE,   true],
            [null, Request::METHOD_CONNECT, true],

            [true, Request::METHOD_HEAD,    true],
            [true, Request::METHOD_GET,     true],
            [true, Request::METHOD_POST,    true],
            [true, Request::METHOD_PUT,     true],
            [true, Request::METHOD_PATCH,   true],
            [true, Request::METHOD_DELETE,  true],
            [true, Request::METHOD_PURGE,   true],
            [true, Request::METHOD_OPTIONS, true],
            [true, Request::METHOD_TRACE,   true],
            [true, Request::METHOD_CONNECT, true],

            [false, Request::METHOD_HEAD,    false],
            [false, Request::METHOD_GET,     false],
            [false, Request::METHOD_POST,    false],
            [false, Request::METHOD_PUT,     false],
            [false, Request::METHOD_PATCH,   false],
            [false, Request::METHOD_DELETE,  false],
            [false, Request::METHOD_PURGE,   false],
            [false, Request::METHOD_OPTIONS, false],
            [false, Request::METHOD_TRACE,   false],
            [false, Request::METHOD_CONNECT, false],
        ];
    }

    /**
     * @dataProvider getRequestMethod
     *
     * @param bool|null $submitClearMissing
     * @param string    $method
     * @param bool      $validSubmitClearMissing
     */
    public function testGetSubmitClearMissing($submitClearMissing, $method, $validSubmitClearMissing)
    {
        $config = new FormConfig(FormType::class);
        $config->setMethod($method);
        $config->setSubmitClearMissing($submitClearMissing);

        $this->assertEquals($validSubmitClearMissing, $config->getSubmitClearMissing());
    }
}
