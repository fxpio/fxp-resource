<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Object;

use Fxp\Component\DefaultValue\ObjectFactoryInterface as DefaultValueObjectFactoryInterface;
use Fxp\Component\Resource\Object\DefaultValueObjectFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests case for default value object factory.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class DefaultValueObjectFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        /** @var DefaultValueObjectFactoryInterface|MockObject $dvof */
        $dvof = $this->getMockBuilder(DefaultValueObjectFactoryInterface::class)->getMock();
        $of = new DefaultValueObjectFactory($dvof);

        $dvof->expects(static::once())
            ->method('create')
            ->with(\stdClass::class, null, [])
            ->willReturn(new \stdClass())
        ;

        $val = $of->create(\stdClass::class);

        static::assertInstanceOf(\stdClass::class, $val);
    }
}
