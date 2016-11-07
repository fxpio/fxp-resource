<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests\Handler;

use Sonatra\Component\Resource\Handler\FormConfig;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests case for Form Config Handler.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class FormConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testWithStringType()
    {
        $type = FormType::class;
        $config = new FormConfig($type);
        $this->assertSame('json', $config->getConverter());
        $this->assertSame(Request::METHOD_POST, $config->getMethod());
        $this->assertEquals(array('method' => Request::METHOD_POST), $config->getOptions());
        $this->assertSame($type, $config->getType());
        $this->assertTrue($config->getSubmitClearMissing());
    }

    public function testWithStringTypeAndPatchMethod()
    {
        $type = FormType::class;
        $config = new FormConfig($type, array(), Request::METHOD_PATCH);
        $this->assertSame('json', $config->getConverter());
        $this->assertSame(Request::METHOD_PATCH, $config->getMethod());
        $this->assertEquals(array('method' => Request::METHOD_PATCH), $config->getOptions());
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
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidArgumentException
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
        $this->assertEquals(array(
            'method' => Request::METHOD_POST,
        ), $config->getOptions());

        $config->setOptions(array(
            'method' => Request::METHOD_PATCH,
            'required' => true,
        ));

        $this->assertEquals(array(
            'method' => Request::METHOD_PATCH,
            'required' => true,
        ), $config->getOptions());
        $this->assertSame(Request::METHOD_PATCH, $config->getMethod());
    }

    public function testSetMethod()
    {
        $config = new FormConfig(FormType::class);

        $this->assertSame(Request::METHOD_POST, $config->getMethod());
        $this->assertEquals(array(
            'method' => Request::METHOD_POST,
        ), $config->getOptions());
        $this->assertSame(Request::METHOD_POST, $config->getMethod());

        $config->setMethod(Request::METHOD_PATCH);

        $this->assertSame(Request::METHOD_PATCH, $config->getMethod());
        $this->assertEquals(array(
            'method' => Request::METHOD_PATCH,
        ), $config->getOptions());
    }

    public function getRequestMethod()
    {
        return array(
            array(null, Request::METHOD_HEAD,    true),
            array(null, Request::METHOD_GET,     true),
            array(null, Request::METHOD_POST,    true),
            array(null, Request::METHOD_PUT,     true),
            array(null, Request::METHOD_PATCH,   false),
            array(null, Request::METHOD_DELETE,  true),
            array(null, Request::METHOD_PURGE,   true),
            array(null, Request::METHOD_OPTIONS, true),
            array(null, Request::METHOD_TRACE,   true),
            array(null, Request::METHOD_CONNECT, true),

            array(true, Request::METHOD_HEAD,    true),
            array(true, Request::METHOD_GET,     true),
            array(true, Request::METHOD_POST,    true),
            array(true, Request::METHOD_PUT,     true),
            array(true, Request::METHOD_PATCH,   true),
            array(true, Request::METHOD_DELETE,  true),
            array(true, Request::METHOD_PURGE,   true),
            array(true, Request::METHOD_OPTIONS, true),
            array(true, Request::METHOD_TRACE,   true),
            array(true, Request::METHOD_CONNECT, true),

            array(false, Request::METHOD_HEAD,    false),
            array(false, Request::METHOD_GET,     false),
            array(false, Request::METHOD_POST,    false),
            array(false, Request::METHOD_PUT,     false),
            array(false, Request::METHOD_PATCH,   false),
            array(false, Request::METHOD_DELETE,  false),
            array(false, Request::METHOD_PURGE,   false),
            array(false, Request::METHOD_OPTIONS, false),
            array(false, Request::METHOD_TRACE,   false),
            array(false, Request::METHOD_CONNECT, false),
        );
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
