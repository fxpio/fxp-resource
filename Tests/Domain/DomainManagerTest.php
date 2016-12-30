<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests\Domain;

use Sonatra\Component\Resource\Domain\DomainAwareInterface;
use Sonatra\Component\Resource\Domain\DomainFactoryInterface;
use Sonatra\Component\Resource\Domain\DomainInterface;
use Sonatra\Component\Resource\Domain\DomainManager;

/**
 * Tests case for Domain Manager.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DomainFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var DomainInterface|\PHPUnit_Framework_MockObject_MockObject
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

        $this->domain->expects($this->any())
            ->method('getShortName')
            ->will($this->returnValue('ShortFoo'));

        $this->factory->expects($this->atLeastOnce())
            ->method('injectDependencies')
            ->willReturn($this->domain);

        $this->factory->expects($this->atLeastOnce())
            ->method('getShortNames')
            ->willReturn(array(
                'std' => \stdClass::class,
            ));

        $this->manager = new DomainManager(array($this->domain), $this->factory);
    }

    public function testHas()
    {
        $this->factory->expects($this->at(0))
            ->method('isManagedClass')
            ->with('Bar')
            ->willReturn(false);

        $this->assertTrue($this->manager->has('Foo'));
        $this->assertTrue($this->manager->has('ShortFoo'));
        $this->assertFalse($this->manager->has('Bar'));
    }

    public function testAddDomainAware()
    {
        /* @var DomainAwareInterface|\PHPUnit_Framework_MockObject_MockObject $domain */
        $domain = $this->getMockBuilder(DomainAwareInterface::class)->getMock();

        $domain->expects($this->once())
            ->method('setDomainManager')
            ->with($this->manager);

        $this->manager->add($domain);
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessage The resource domain for the class "Foo" already exist
     */
    public function testAddAlreadyExistingClass()
    {
        /* @var DomainInterface|\PHPUnit_Framework_MockObject_MockObject $domain */
        $domain = $this->getMockBuilder(DomainInterface::class)->getMock();
        $domain->expects($this->once())
            ->method('getClass')
            ->willReturn('Foo');

        $this->manager->add($domain);
    }

    public function testRemove()
    {
        $this->assertTrue($this->manager->has('Foo'));

        $this->factory->expects($this->once())
            ->method('getManagedClass')
            ->with('Foo')
            ->willReturn('Foo');

        $this->manager->remove('Foo');

        $this->assertFalse($this->manager->has('Foo'));
    }

    public function testAll()
    {
        $domain = $this->getMockBuilder(DomainInterface::class)->getMock();
        $domain->expects($this->once())
            ->method('getClass')
            ->willReturn(\stdClass::class);

        $this->factory->expects($this->once())
            ->method('create')
            ->with(\stdClass::class)
            ->willReturn($domain);

        $expected = array(
            'Foo' => $this->domain,
            \stdClass::class => $domain,
        );

        $this->assertSame($expected, $this->manager->all());
    }

    public function testGetShortNames()
    {
        $expected = array(
            'std' => \stdClass::class,
            'ShortFoo' => 'Foo',
        );

        $this->assertSame($expected, $this->manager->getShortNames());
    }

    public function testGet()
    {
        $this->assertTrue($this->manager->has('Foo'));

        $this->factory->expects($this->once())
            ->method('getManagedClass')
            ->with('Foo')
            ->willReturn('Foo');

        $this->assertSame($this->domain, $this->manager->get('Foo'));
    }

    public function testGetWithoutExistingDomain()
    {
        $this->assertFalse($this->manager->has(\stdClass::class));

        $domain = $this->getMockBuilder(DomainInterface::class)->getMock();
        $domain->expects($this->once())
            ->method('getClass')
            ->willReturn(\stdClass::class);

        $this->factory->expects($this->once())
            ->method('getManagedClass')
            ->with(\stdClass::class)
            ->willReturn(\stdClass::class);

        $this->factory->expects($this->once())
            ->method('create')
            ->with(\stdClass::class)
            ->willReturn($domain);

        $this->assertSame($domain, $this->manager->get(\stdClass::class));
    }
}
