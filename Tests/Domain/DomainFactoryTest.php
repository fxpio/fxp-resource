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
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Sonatra\Component\DefaultValue\ObjectFactoryInterface;
use Sonatra\Component\Resource\Domain\Domain;
use Sonatra\Component\Resource\Domain\DomainFactory;
use Sonatra\Component\Resource\Domain\DomainInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests case for Domain Manager.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DomainFactory
     */
    protected $factory;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var ClassMetadataFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $metaFactory;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ObjectFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectFactory;

    /**
     * @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validator;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $this->objectFactory = $this->getMockBuilder(ObjectFactoryInterface::class)->getMock();
        $this->validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $this->translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $this->objectManager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $this->metaFactory = $this->getMockBuilder(ClassMetadataFactory::class)->getMock();
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();
        $this->factory = new DomainFactory(
            $this->registry,
            $this->eventDispatcher,
            $this->objectFactory,
            $this->validator,
            $this->translator
        );

        $this->objectManager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($this->metaFactory);

        $this->registry->expects($this->any())
            ->method('getManagers')
            ->willReturn(array($this->objectManager));
    }

    public function testAddResolveTargets()
    {
        $this->factory->addResolveTargets(array('FooInterface' => 'Foo'));
    }

    public function testGetShortNames()
    {
        $expected = array(
            'Foo' => 'Bar\Foo',
        );

        /* @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metaFoo */
        $metaFoo = $this->getMockBuilder(ClassMetadata::class)->getMock();
        $metaFoo->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Bar\Foo'));

        $this->metaFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn(array($metaFoo));

        $res = $this->factory->getShortNames();

        $this->assertSame($expected, $res);
    }

    public function testIsManagedClass()
    {
        $res = $this->factory->isManagedClass(\stdClass::class);

        $this->assertFalse($res);
    }

    public function testIsManagedClassWithResolveTarget()
    {
        $this->factory->addResolveTargets(array(
            's' => \stdClass::class,
        ));

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn(null);

        $this->metaFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with(\stdClass::class)
            ->willReturn(true);

        $res = $this->factory->isManagedClass('s');

        $this->assertTrue($res);
    }

    public function testGetManagedClass()
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($this->objectManager);

        /* @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $meta */
        $meta = $this->getMockBuilder(ClassMetadata::class)->getMock();
        $meta->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(\stdClass::class));

        $this->objectManager->expects($this->atLeast(2))
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($meta);

        $res = $this->factory->getManagedClass(\stdClass::class);

        $this->assertSame(\stdClass::class, $res);
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessage The "stdClass" class is not registered in doctrine
     */
    public function testGetManagedClassWithNonManagedClass()
    {
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn(null);

        $this->metaFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with(\stdClass::class)
            ->willReturn(false);

        $this->factory->getManagedClass(\stdClass::class);
    }

    /**
     * @expectedException \Sonatra\Component\Resource\Exception\InvalidArgumentException
     * @expectedExceptionMessage The "stdClass" class is not registered in doctrine
     */
    public function testGetManagedClassWithOrmMappedSuperClass()
    {
        /* @var OrmClassMetadata|\PHPUnit_Framework_MockObject_MockObject $meta */
        $meta = $this->getMockBuilder(OrmClassMetadata::class)->disableOriginalConstructor()->getMock();
        $meta->isMappedSuperclass = true;

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($meta);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($this->objectManager);

        $this->factory->getManagedClass(\stdClass::class);
    }

    public function testCreate()
    {
        $domain = $this->factory->create(\stdClass::class, 'std');

        $this->assertInstanceOf(Domain::class, $domain);
        $this->assertSame(\stdClass::class, $domain->getClass());
        $this->assertSame('std', $domain->getShortName());
    }

    public function testInjectDependencies()
    {
        /* @var DomainInterface|\PHPUnit_Framework_MockObject_MockObject $domain */
        $domain = $this->getMockBuilder(DomainInterface::class)->getMock();

        $domain->expects($this->once())
            ->method('getClass')
            ->willReturn(\stdClass::class);

        $domain->expects($this->once())
            ->method('setDebug')
            ->with(false);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($this->objectManager);

        $domain->expects($this->once())
            ->method('setObjectManager')
            ->with($this->objectManager);

        $domain->expects($this->once())
            ->method('setEventDispatcher')
            ->with($this->eventDispatcher);

        $domain->expects($this->once())
            ->method('setObjectFactory')
            ->with($this->objectFactory);

        $domain->expects($this->once())
            ->method('setValidator')
            ->with($this->validator);

        $this->factory->injectDependencies($domain);
    }
}
