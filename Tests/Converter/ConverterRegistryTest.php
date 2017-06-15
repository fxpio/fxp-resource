<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests\Converter;

use PHPUnit\Framework\TestCase;
use Sonatra\Component\Resource\Converter\ConverterRegistry;
use Sonatra\Component\Resource\Converter\ConverterRegistryInterface;

/**
 * Tests case for converter registry.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ConverterRegistryTest extends TestCase
{
    /**
     * @var ConverterRegistryInterface
     */
    protected $registry;

    protected function setUp()
    {
        $converter = $this->getMockBuilder('Sonatra\Component\Resource\Converter\ConverterInterface')->getMock();
        $converter->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('foo'));

        $this->registry = new ConverterRegistry(array(
            $converter,
        ));
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Sonatra\Component\Resource\Converter\ConverterInterface", "DateTime" given
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
     * @expectedException \Sonatra\Component\Resource\Exception\UnexpectedTypeException
     * @expectedExceptionMessageRegExp /Expected argument of type "(\w+)", "(\w+)" given/
     */
    public function testGetInvalidType()
    {
        $this->registry->get(42);
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /Could not load content converter "(\w+)"/
     */
    public function testGetNonExistentConverter()
    {
        $this->registry->get('bar');
    }

    public function testGet()
    {
        $converter = $this->registry->get('foo');

        $this->assertInstanceOf('Sonatra\Component\Resource\Converter\ConverterInterface', $converter);
    }
}
