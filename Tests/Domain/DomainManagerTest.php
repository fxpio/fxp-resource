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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Sonatra\Component\DefaultValue\ObjectFactoryInterface;
use Sonatra\Component\Resource\Domain\Domain;
use Sonatra\Component\Resource\Domain\DomainFactory;
use Sonatra\Component\Resource\Domain\DomainInterface;
use Sonatra\Component\Resource\Domain\DomainManager;
use Sonatra\Component\Resource\Domain\DomainManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests case for Domain Manager.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DomainManagerInterface
     */
    protected $manager;

    protected function setUp()
    {
        /* @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metaBar */
        $metaBar = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')->getMock();
        $metaBar->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Bar'));

        /* @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metaFoo */
        $metaFoo = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')->getMock();
        $metaFoo->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Foo'));

        /* @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $om */
        $om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')->getMock();
        $om->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnCallback(function ($value) use ($metaBar, $metaFoo) {
                $ret = null;
                if ('Bar' === $value) {
                    $ret = $metaBar;
                }
                if ('Foo' === $value) {
                    $ret = $metaFoo;
                }

                return $ret;
            }));

        /* @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $or */
        $or = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')->getMock();
        $or->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnCallback(function ($value) use ($om) {
                return in_array($value, array('InvalidClass', 'FooInterface')) ? null : $om;
            }));

        /* @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $ed */
        $ed = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();

        /* @var ObjectFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $of */
        $of = $this->getMockBuilder('Sonatra\Component\DefaultValue\ObjectFactoryInterface')->getMock();

        /* @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $val */
        $val = $this->getMockBuilder('Symfony\Component\Validator\Validator\ValidatorInterface')->getMock();

        /* @var DomainInterface|\PHPUnit_Framework_MockObject_MockObject $domain */
        $domain = $this->getMockBuilder('Sonatra\Component\Resource\Domain\DomainInterface')->getMock();
        $domain->expects($this->any())
            ->method('getClass')
            ->will($this->returnValue('Foo'));
        $domain->expects($this->any())
            ->method('getShortName')
            ->will($this->returnValue('ShortFoo'));

        $df = new DomainFactory($or, $ed, $of, $val);

        $this->manager = new DomainManager(array($domain), $df);
    }

    public function testConstructor()
    {
        $this->assertCount(1, $this->manager->all());
    }

    public function testHasDomainClass()
    {
        $this->assertTrue($this->manager->has('Foo'));
        $this->assertTrue($this->manager->has('ShortFoo'));
        $this->assertTrue($this->manager->has('Bar'));
        $this->assertFalse($this->manager->has('InvalidClass'));
    }

    public function testAdd()
    {
        $this->assertCount(1, $this->manager->all());

        /* @var DomainInterface|\PHPUnit_Framework_MockObject_MockObject $domain */
        $domain = $this->getMockBuilder('Sonatra\Component\Resource\Domain\DomainInterface')->getMock();
        $domain->expects($this->any())
            ->method('getClass')
            ->will($this->returnValue('Bar'));

        $this->manager->add($domain);

        $this->assertCount(2, $this->manager->all());

        $this->assertTrue($this->manager->has('Foo'));
        $this->assertTrue($this->manager->has('Bar'));
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessage The resource domain for the class "Foo" already exist
     */
    public function testAddWithExistingClass()
    {
        $this->assertCount(1, $this->manager->all());

        /* @var DomainInterface|\PHPUnit_Framework_MockObject_MockObject $domain */
        $domain = $this->getMockBuilder('Sonatra\Component\Resource\Domain\DomainInterface')->getMock();
        $domain->expects($this->any())
            ->method('getClass')
            ->will($this->returnValue('Foo'));

        $this->manager->add($domain);
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessage The resource domain for the short name "ShortFoo" already exist
     */
    public function testAddWithExistingShortName()
    {
        $this->assertCount(1, $this->manager->all());

        /* @var DomainInterface|\PHPUnit_Framework_MockObject_MockObject $domain */
        $domain = $this->getMockBuilder('Sonatra\Component\Resource\Domain\DomainInterface')->getMock();
        $domain->expects($this->any())
            ->method('getShortName')
            ->will($this->returnValue('ShortFoo'));

        $this->manager->add($domain);
    }

    public function getRemoveTestConfig()
    {
        return array(
            array('Foo'),
            array('ShortFoo'),
        );
    }

    /**
     * @dataProvider getRemoveTestConfig
     *
     * @param string $classOrShortName
     */
    public function testRemove($classOrShortName)
    {
        $this->assertCount(1, $this->manager->all());

        $this->manager->remove($classOrShortName);

        $this->assertCount(0, $this->manager->all());
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /The "(\w+)" class is not registered in doctrine/
     */
    public function testGetNonRegisteredClass()
    {
        $this->manager->get('InvalidClass');
    }

    public function testGetDomainNotAddedManually()
    {
        $domain = $this->manager->get('Bar');

        $this->assertInstanceOf(Domain::class, $domain);
    }

    public function testGetDomainWithCache()
    {
        $dom1 = $this->manager->get('Foo');
        $this->assertInstanceOf('Sonatra\Component\Resource\Domain\DomainInterface', $dom1);

        $dom2 = $this->manager->get('ShortFoo');
        $this->assertInstanceOf('Sonatra\Component\Resource\Domain\DomainInterface', $dom2);

        $this->assertSame($dom1, $dom2);
    }

    public function testGetShortNames()
    {
        $valid = array(
            'ShortFoo' => 'Foo',
        );

        $this->assertEquals($valid, $this->manager->getShortNames());
    }

    public function testResolveTarget()
    {
        $this->assertFalse($this->manager->has('FooInterface'));

        $this->manager->addResolveTargets(array(
            'FooInterface' => 'Foo',
        ));

        $this->assertTrue($this->manager->has('FooInterface'));
        $domain = $this->manager->get('FooInterface');
        $this->assertInstanceOf(DomainInterface::class, $domain);
        $this->assertSame('Foo', $domain->getClass());
    }
}
