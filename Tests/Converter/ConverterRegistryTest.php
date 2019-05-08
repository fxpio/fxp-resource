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
 *
 * @internal
 */
final class ConverterRegistryTest extends TestCase
{
    /**
     * @var ConverterRegistryInterface
     */
    protected $registry;

    protected function setUp(): void
    {
        $converter = $this->getMockBuilder('Fxp\Component\Resource\Converter\ConverterInterface')->getMock();
        $converter->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('foo'))
        ;

        $this->registry = new ConverterRegistry([
            $converter,
        ]);
    }

    public function testUnexpectedTypeException(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "Fxp\\Component\\Resource\\Converter\\ConverterInterface", "DateTime" given');

        new ConverterRegistry([
            new \DateTime(),
        ]);
    }

    public function testHas(): void
    {
        $this->assertTrue($this->registry->has('foo'));
        $this->assertFalse($this->registry->has('bar'));
    }

    public function testGetNonExistentConverter(): void
    {
        $this->expectException(\Fxp\Component\Resource\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Could not load content converter "(\\w+)"/');

        $this->registry->get('bar');
    }

    public function testGet(): void
    {
        $converter = $this->registry->get('foo');

        $this->assertInstanceOf('Fxp\Component\Resource\Converter\ConverterInterface', $converter);
    }
}
