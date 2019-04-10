<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Domain;

use Fxp\Component\Resource\Domain\DomainFactoryInterface;
use Fxp\Component\Resource\Domain\DomainInterface;
use Fxp\Component\Resource\Domain\DomainManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests case for Domain Manager.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DomainManagerTest extends TestCase
{
    /**
     * @var DomainFactoryInterface|MockObject
     */
    protected $factory;

    /**
     * @var DomainInterface|MockObject
     */
    protected $domain;

    /**
     * @var DomainManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->domain = $this->getMockBuilder(DomainInterface::class)->getMock();
        $this->factory = $this->getMockBuilder(DomainFactoryInterface::class)->getMock();

        $this->domain->expects($this->any())
            ->method('getClass')
            ->will($this->returnValue('Foo'));

        $this->manager = new DomainManager($this->factory);
    }

    public function testHas()
    {
        $this->factory->expects($this->any())
            ->method('isManagedClass')
            ->willReturnCallback(static function ($value) {
                return 'Foo' === $value;
            });

        $this->assertTrue($this->manager->has('Foo'));
        $this->assertFalse($this->manager->has('Bar'));
    }

    public function testGet()
    {
        $this->factory->expects($this->once())
            ->method('isManagedClass')
            ->with('FooInterface')
            ->willReturn(true);

        $this->assertTrue($this->manager->has('FooInterface'));

        $this->factory->expects($this->once())
            ->method('getManagedClass')
            ->with('FooInterface')
            ->willReturn('Foo');

        $this->factory->expects($this->once())
            ->method('create')
            ->with('Foo')
            ->willReturn($this->domain);

        $this->assertSame($this->domain, $this->manager->get('FooInterface'));
        $this->assertTrue($this->manager->has('Foo'));
    }
}
