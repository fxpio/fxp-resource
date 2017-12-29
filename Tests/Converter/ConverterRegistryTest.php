<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Converter;

use Fxp\Component\Resource\Converter\ConverterRegistry;
use Fxp\Component\Resource\Converter\ConverterRegistryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests case for converter registry.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ConverterRegistryTest extends TestCase
{
    /**
     * @var ConverterRegistryInterface
     */
    protected $registry;

    protected function setUp()
    {
        $converter = $this->getMockBuilder('Fxp\Component\Resource\Converter\ConverterInterface')->getMock();
        $converter->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('foo'));

        $this->registry = new ConverterRegistry(array(
            $converter,
        ));
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Fxp\Component\Resource\Converter\ConverterInterface", "DateTime" given
     */
    public function testUnexpectedTypeException()
    {
        new ConverterRegistry(array(
            new \DateTime(),
        ));
    }

    public function testHas()
    {
        $this->assertTrue($this->registry->has('foo'));
        $this->assertFalse($this->registry->has('bar'));
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\UnexpectedTypeException
     * @expectedExceptionMessageRegExp /Expected argument of type "(\w+)", "(\w+)" given/
     */
    public function testGetInvalidType()
    {
        $this->registry->get(42);
    }

    /**
     * @expectedException \Fxp\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /Could not load content converter "(\w+)"/
     */
    public function testGetNonExistentConverter()
    {
        $this->registry->get('bar');
    }

    public function testGet()
    {
        $converter = $this->registry->get('foo');

        $this->assertInstanceOf('Fxp\Component\Resource\Converter\ConverterInterface', $converter);
    }
}
